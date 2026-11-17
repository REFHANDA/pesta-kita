<?php

namespace App\Http\Controllers\Klien;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StorePaymentRequest;
use App\Models\AuditLog;
use App\Models\CompanyBankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    /**
     * Show an invoice to its owning client.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->event()->where('client_id', $request->user()->id)->exists(), 404);

        return view('klien.tagihan', [
            'invoice' => $invoice->load(['event', 'lines.orderItem', 'latestPayment']),
            'bankAccounts' => CompanyBankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
            'refundRequest' => $invoice->event->refundRequest,
        ]);
    }

    /**
     * Submit a transfer proof without changing the invoice to paid.
     */
    public function storePayment(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->event()->where('client_id', $request->user()->id)->exists(), 404);
        $data = $request->validated();
        $proofPath = $request->file('proof')->store('payment-proofs', 'private');
        if ($proofPath === false) {
            throw ValidationException::withMessages([
                'proof' => 'Bukti transfer gagal disimpan. Coba unggah ulang.',
            ]);
        }

        $saved = false;
        try {
            $result = DB::transaction(function () use ($request, $invoice, $data, $proofPath): string {
                $lockedInvoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if ($lockedInvoice->status !== 'terbit') {
                    throw ValidationException::withMessages([
                        'invoice' => 'Tagihan ini tidak menerima bukti pembayaran baru.',
                    ]);
                }

                if (! $lockedInvoice->due_at->isFuture()) {
                    $lockedInvoice->update([
                        'status' => 'kedaluwarsa',
                        'active_code' => null,
                    ]);

                    return 'expired';
                }

                $account = CompanyBankAccount::query()
                    ->whereKey($data['company_bank_account_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();
                if ($account === null) {
                    throw ValidationException::withMessages([
                        'company_bank_account_id' => 'Rekening tujuan sudah tidak aktif.',
                    ]);
                }

                if ($lockedInvoice->payments()->where('status', 'menunggu_verifikasi')->exists()) {
                    throw ValidationException::withMessages([
                        'invoice' => 'Bukti pembayaran untuk tagihan ini sedang menunggu verifikasi Admin.',
                    ]);
                }

                $payment = $lockedInvoice->payments()->create([
                    'active_invoice_id' => $lockedInvoice->id,
                    'company_bank_account_id' => $account->id,
                    'bank_name_snapshot' => $account->bank_name,
                    'account_number_snapshot' => $account->account_number,
                    'account_name_snapshot' => $account->account_name,
                    'sender_name' => $data['sender_name'],
                    'amount' => $data['amount'],
                    'transferred_at' => $data['transferred_at'],
                    'proof_disk' => 'private',
                    'proof_path' => $proofPath,
                    'status' => 'menunggu_verifikasi',
                ]);

                AuditLog::create([
                    'actor_id' => $request->user()->id,
                    'action' => 'payment_proof_submitted',
                    'subject_type' => $payment->getMorphClass(),
                    'subject_id' => $payment->id,
                    'from_status' => null,
                    'to_status' => $payment->status,
                    'reason' => 'Bukti transfer menunggu verifikasi Admin.',
                    'ip' => $request->ip(),
                ]);

                return 'submitted';
            });

            $saved = $result === 'submitted';
        } finally {
            if (! $saved) {
                Storage::disk('private')->delete($proofPath);
            }
        }

        if ($result === 'expired') {
            throw ValidationException::withMessages([
                'invoice' => 'Batas bayar terlewati. Tagihan kedaluwarsa dan pesanan tidak dibatalkan otomatis.',
            ]);
        }

        return redirect()->route('klien.tagihan', $invoice)
            ->with('status', 'Bukti pembayaran dikirim dan menunggu verifikasi Admin.');
    }

    /**
     * Stream an invoice payment proof only to its owner.
     */
    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($payment->invoice()->whereHas('event', fn ($query) => $query
            ->where('client_id', $request->user()->id))->exists(), 404);
        abort_unless(is_string($payment->proof_path)
            && Storage::disk($payment->proof_disk)->exists($payment->proof_path), 404);

        return Storage::disk($payment->proof_disk)->download($payment->proof_path);
    }
}
