<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentValidatedNotification;
use App\Services\InvoiceIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controller validasi pembayaran untuk Admin (Fase 5):
 * - Memverifikasi kesesuaian nominal transfer dengan transfer_total (grand_total + kode unik).
 * - Menandai tagihan lunas, acara terkonfirmasi, pesanan terkonfirmasi, dan menerbitkan nota lunas.
 * - Menolak pembayaran dengan alasan/catatan bila nominal tidak sesuai atau bukti tidak valid.
 * - Menyediakan tampilan cetak print-friendly HTML untuk tagihan dan nota lunas.
 */
class PaymentController extends Controller
{
    /**
     * Tampilkan daftar antrean dan riwayat tagihan & pembayaran.
     */
    public function index(Request $request): View
    {
        $statusTab = $request->query('status', 'semua');
        $q = $request->query('q');

        $query = Invoice::query()->with([
            'event.client',
            'latestPayment.companyBankAccount',
        ])->latest();

        if ($statusTab === 'menunggu-validasi') {
            $query->where('status', InvoiceStatus::MenungguValidasi);
        } elseif ($statusTab === 'menunggu-pembayaran') {
            $query->where('status', InvoiceStatus::BelumDibayar);
        } elseif ($statusTab === 'lunas') {
            $query->where('status', InvoiceStatus::Lunas);
        } elseif ($statusTab === 'kedaluwarsa') {
            $query->where('status', InvoiceStatus::Kedaluwarsa);
        }

        if (! empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('event', fn ($eq) => $eq->where('name', 'like', "%{$q}%"));
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        $countMenungguPembayaran = Invoice::where('status', InvoiceStatus::BelumDibayar)->count();
        $countMenungguValidasi = Invoice::where('status', InvoiceStatus::MenungguValidasi)->count();
        $countLunasBulanIni = Invoice::where('status', InvoiceStatus::Lunas)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->count();

        return view('admin.pembayaran', compact(
            'invoices',
            'statusTab',
            'q',
            'countMenungguPembayaran',
            'countMenungguValidasi',
            'countLunasBulanIni'
        ));
    }

    /**
     * Tampilkan detail validasi pembayaran untuk satu tagihan.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        $invoice->load([
            'event.client',
            'items.orderItem.subject',
            'payments.companyBankAccount',
            'payments.proof',
            'payments.confirmedBy',
            'latestPayment.proof',
            'latestPayment.companyBankAccount',
        ]);

        return view('admin.pembayaran-detail', compact('invoice'));
    }

    /**
     * Validasi pembayaran (valid atau tolak) oleh Admin.
     */
    public function validatePayment(Request $request, Payment $payment, InvoiceIssuer $issuer): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:valid,reject'],
            'admin_note' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ], [
            'action.required' => 'Pilih tindakan validasi pembayaran.',
            'admin_note.required_if' => 'Catatan penolakan wajib diisi untuk memberitahu klien.',
        ]);

        return DB::transaction(function () use ($payment, $validated, $request, $issuer) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->where('id', $payment->id)->lockForUpdate()->firstOrFail();

            // Validasi ganda harus ditolak
            if ($lockedPayment->status !== PaymentStatus::MenungguValidasi) {
                throw ValidationException::withMessages([
                    'action' => "Pembayaran ini telah diproses sebelumnya (status: {$lockedPayment->status->label()}). Validasi ganda ditolak.",
                ]);
            }

            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::query()->where('id', $lockedPayment->invoice_id)->lockForUpdate()->firstOrFail();

            /** @var Event $lockedEvent */
            $lockedEvent = Event::query()->where('id', $lockedInvoice->event_id)->lockForUpdate()->firstOrFail();

            if ($validated['action'] === 'valid') {
                // Aturan EMS §1.5: Admin hanya boleh valid jika jumlah = total transfer (total + kode unik).
                if ($lockedPayment->amount !== $lockedInvoice->transfer_total) {
                    $amtFmt = number_format($lockedPayment->amount, 0, ',', '.');
                    $totFmt = number_format($lockedInvoice->transfer_total, 0, ',', '.');

                    throw ValidationException::withMessages([
                        'amount' => "Jumlah transfer pada bukti (Rp {$amtFmt}) tidak sama dengan total yang harus dibayar (Rp {$totFmt}). Pembayaran tidak dapat dinyatakan valid.",
                    ]);
                }

                $receiptNumber = $issuer->generateReceiptNumber($lockedInvoice);

                $lockedPayment->update([
                    'status' => PaymentStatus::Valid,
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'admin_note' => $validated['admin_note'] ?? null,
                ]);

                $lockedInvoice->update([
                    'status' => InvoiceStatus::Lunas,
                    'paid_at' => now(),
                    'receipt_number' => $receiptNumber,
                ]);

                $lockedEvent->update([
                    'status' => EventStatus::Terkonfirmasi,
                ]);

                // Seluruh item pesanan yang disetujui kini terkonfirmasi
                $lockedEvent->orderItems()
                    ->where('status', OrderItemStatus::Disetujui)
                    ->update([
                        'status' => OrderItemStatus::Terkonfirmasi,
                    ]);

                AuditLog::create([
                    'actor_id' => Auth::id(),
                    'action' => 'payment_validated',
                    'subject_type' => $lockedPayment->getMorphClass(),
                    'subject_id' => $lockedPayment->id,
                    'from_status' => PaymentStatus::MenungguValidasi->value,
                    'to_status' => PaymentStatus::Valid->value,
                    'reason' => "Pembayaran tagihan {$lockedInvoice->invoice_number} diverifikasi valid oleh Admin. Nota lunas {$receiptNumber} diterbitkan.",
                    'ip' => $request->ip(),
                ]);

                $lockedEvent->client?->notify(new PaymentValidatedNotification($lockedInvoice));

                // Beri tahu penyedia terkait bahwa pesanan telah terkonfirmasi
                $confirmedItems = $lockedEvent->orderItems()
                    ->where('status', OrderItemStatus::Terkonfirmasi)
                    ->get();

                $notifiedUserIds = [];
                foreach ($confirmedItems as $item) {
                    $providerUser = $item->provider?->user;
                    if ($providerUser && ! isset($notifiedUserIds[$providerUser->id])) {
                        $notifiedUserIds[$providerUser->id] = true;
                        $providerUser->notify(new OrderConfirmedNotification($item));
                    }
                }

                return redirect()
                    ->route('admin.pembayaran.show', $lockedInvoice)
                    ->with('success', "Pembayaran tagihan {$lockedInvoice->invoice_number} berhasil divalidasi. Nota lunas {$receiptNumber} telah terbit.");
            }

            // Aksi penolakan pembayaran
            $lockedPayment->update([
                'status' => PaymentStatus::Ditolak,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'admin_note' => $validated['admin_note'],
            ]);

            // Hitung total penolakan pembayaran pada tagihan ini
            $totalRejections = $lockedInvoice->payments()
                ->where('status', PaymentStatus::Ditolak)
                ->count();

            // Batasi perpanjangan due_at akibat penolakan bukti:
            // Maksimal 2 kali per tagihan ATAU tidak melewati 7 hari sejak issued_at;
            // setelah itu tagihan tetap bisa kedaluwarsa via invoices:expire.
            $baseIssuedAt = $lockedInvoice->issued_at ?? $lockedInvoice->created_at ?? now();
            $maxAllowedDueAt = $baseIssuedAt->copy()->addDays(7);

            $currentDueAt = $lockedInvoice->due_at;
            $newDueAt = $currentDueAt;

            if ($totalRejections <= 2) {
                $candidateDueAt = now()->addHours(24);
                if (! $currentDueAt || $currentDueAt->lt($candidateDueAt)) {
                    $newDueAt = $candidateDueAt;
                }

                if ($newDueAt && $newDueAt->gt($maxAllowedDueAt)) {
                    $newDueAt = $maxAllowedDueAt;
                }
            }

            // Tagihan kembali ke status belum_dibayar agar klien dapat mengunggah bukti baru
            $lockedInvoice->update([
                'status' => InvoiceStatus::BelumDibayar,
                'due_at' => $newDueAt,
            ]);

            AuditLog::create([
                'actor_id' => Auth::id(),
                'action' => 'payment_rejected',
                'subject_type' => $lockedPayment->getMorphClass(),
                'subject_id' => $lockedPayment->id,
                'from_status' => PaymentStatus::MenungguValidasi->value,
                'to_status' => PaymentStatus::Ditolak->value,
                'reason' => "Bukti pembayaran ditolak: {$validated['admin_note']}. Batas bayar disetel hingga {$newDueAt}.",
                'ip' => $request->ip(),
            ]);

            $lockedEvent->client?->notify(new PaymentRejectedNotification($lockedPayment, $validated['admin_note']));

            return redirect()
                ->route('admin.pembayaran.show', $lockedInvoice)
                ->with('success', "Bukti pembayaran ditolak dengan catatan. Batas bayar tagihan disetel hingga {$newDueAt->format('d/m/Y H:i')} WIB.");
        });
    }

    /**
     * Tampilan cetak print-friendly HTML untuk tagihan.
     */
    public function print(Request $request, Invoice $invoice): View
    {
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
        abort_unless($invoice->status === InvoiceStatus::Lunas, 404, 'Nota lunas hanya tersedia untuk tagihan yang telah lunas.');

        $invoice->load([
            'event.client',
            'items.orderItem.subject',
            'payments.companyBankAccount',
            'latestPayment',
        ]);

        return view('invoices.receipt', compact('invoice'));
    }
}
