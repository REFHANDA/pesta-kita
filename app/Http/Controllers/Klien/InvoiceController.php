<?php

namespace App\Http\Controllers\Klien;

use App\Enums\InvoiceStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanyBankAccount;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controller tagihan dan pembayaran untuk portal Klien (Fase 5):
 * - Menampilkan rincian tagihan, rekening tujuan berurut sort_order, kode unik, sisa waktu.
 * - Menerima unggahan bukti transfer manual (JPG/PNG/PDF, maks 5 MB, disk private).
 * - Menampilkan view cetak (print-friendly HTML) untuk tagihan dan nota lunas.
 */
class InvoiceController extends Controller
{
    /**
     * Tampilkan halaman rincian tagihan klien.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'event.client',
            'items.orderItem.subject',
            'payments.companyBankAccount',
            'latestPayment.proof',
        ]);

        $activeBankAccounts = CompanyBankAccount::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($invoice->status === InvoiceStatus::Lunas) {
            return view('klien.tagihan-lunas', compact('invoice', 'activeBankAccounts'));
        }

        return view('klien.tagihan', compact('invoice', 'activeBankAccounts'));
    }

    /**
     * Kirim bukti transfer manual dari klien.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('pay', $invoice);

        $validated = $request->validate([
            'company_bank_account_id' => ['required', 'exists:company_bank_accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'sender_name' => ['required', 'string', 'max:255'],
            'transfer_date' => ['required', 'date'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'company_bank_account_id.required' => 'Pilih rekening tujuan transfer.',
            'amount.required' => 'Jumlah transfer wajib diisi.',
            'sender_name.required' => 'Nama pemilik rekening pengirim wajib diisi.',
            'transfer_date.required' => 'Tanggal transfer wajib diisi.',
            'proof.required' => 'Unggah file bukti transfer.',
            'proof.mimes' => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        DB::transaction(function () use ($request, $invoice, $validated) {
            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::query()->where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            // Aturan EMS Fase 5: Hanya satu pembayaran aktif (menunggu_validasi) per tagihan
            $hasPendingPayment = $lockedInvoice->payments()
                ->where('status', PaymentStatus::MenungguValidasi)
                ->lockForUpdate()
                ->exists();

            if ($hasPendingPayment) {
                throw ValidationException::withMessages([
                    'proof' => 'Tagihan ini telah memiliki bukti pembayaran yang sedang menunggu validasi Admin. Anda tidak dapat mengunggah bukti baru sebelum keputusan diberikan.',
                ]);
            }

            $file = $request->file('proof');
            $path = $file->store('payments', 'private');

            $media = Media::create([
                'owner_type' => 'payment',
                'owner_id' => 0,
                'kind' => MediaKind::BuktiPembayaran,
                'doc_type' => null,
                'path' => $path,
                'disk' => 'private',
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
                'visibility' => MediaVisibility::Privat,
                'sort_order' => 0,
            ]);

            $payment = Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'company_bank_account_id' => $validated['company_bank_account_id'],
                'amount' => (int) $validated['amount'],
                'sender_name' => $validated['sender_name'],
                'transfer_date' => $validated['transfer_date'],
                'proof_media_id' => $media->id,
                'status' => PaymentStatus::MenungguValidasi,
                'admin_note' => null,
            ]);

            $media->update([
                'owner_id' => $payment->id,
            ]);

            $lockedInvoice->update([
                'status' => InvoiceStatus::MenungguValidasi,
            ]);

            AuditLog::create([
                'actor_id' => Auth::id(),
                'action' => 'payment_submitted',
                'subject_type' => $payment->getMorphClass(),
                'subject_id' => $payment->id,
                'from_status' => null,
                'to_status' => PaymentStatus::MenungguValidasi->value,
                'reason' => 'Klien mengunggah bukti pembayaran sebesar Rp '.number_format($payment->amount, 0, ',', '.').'.',
                'ip' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('klien.tagihan.show', $invoice)
            ->with('success', 'Bukti pembayaran berhasil dikirim dan saat ini berstatus menunggu validasi Admin.');
    }

    /**
     * Tampilan cetak print-friendly HTML untuk tagihan.
     */
    public function print(Request $request, Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'event.client',
            'items.orderItem.subject',
        ]);

        return view('invoices.print', compact('invoice'));
    }

    /**
     * Tampilan cetak print-friendly HTML untuk nota lunas.
     */
    public function receipt(Request $request, Invoice $invoice): View
    {
        Gate::authorize('viewReceipt', $invoice);

        $invoice->load([
            'event.client',
            'items.orderItem.subject',
            'payments.companyBankAccount',
            'latestPayment',
        ]);

        return view('invoices.receipt', compact('invoice'));
    }
}
