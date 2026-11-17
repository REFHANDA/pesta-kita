<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyPaymentRequest;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * List invoice and latest-payment states for Admin.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'semua');
        $allowedStatuses = ['semua', 'terbit', 'menunggu_verifikasi', 'lunas', 'kedaluwarsa'];
        if (! is_string($status) || ! in_array($status, $allowedStatuses, true)) {
            $status = 'semua';
        }

        $invoices = Invoice::query()
            ->with(['event', 'latestPayment'])
            ->when($status === 'menunggu_verifikasi', fn ($query) => $query
                ->whereHas('payments', fn ($paymentQuery) => $paymentQuery->where('status', 'menunggu_verifikasi')))
            ->when(in_array($status, ['terbit', 'lunas', 'kedaluwarsa'], true), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pembayaran', ['invoices' => $invoices, 'statusFilter' => $status]);
    }

    /**
     * Show invoice and payment proof to Admin.
     */
    public function show(Invoice $invoice): View
    {
        return view('admin.tagihan-detail', [
            'invoice' => $invoice->load([
                'event.client',
                'lines.orderItem.subject',
                'payments.companyBankAccount',
            ]),
        ]);
    }

    /**
     * Verify or reject a payment proof without accepting a mismatched amount.
     */
    public function decide(
        VerifyPaymentRequest $request,
        Payment $payment,
        string $action,
    ): RedirectResponse {
        abort_unless(in_array($action, ['approve', 'reject'], true), 404);
        $data = $request->validated();

        DB::transaction(function () use ($request, $payment, $action, $data): void {
            $invoiceId = Payment::query()->whereKey($payment->id)->value('invoice_id');
            abort_if($invoiceId === null, 404);
            $eventId = Invoice::query()->whereKey($invoiceId)->value('event_id');
            abort_if($eventId === null, 404);
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== 'menunggu_verifikasi' || $invoice->status !== 'terbit') {
                throw ValidationException::withMessages([
                    'payment' => 'Bukti ini sudah diproses atau tagihan tidak lagi aktif.',
                ]);
            }

            if ($action === 'approve') {
                if ((int) $data['received_amount'] !== $invoice->total_amount
                    || $lockedPayment->amount !== $invoice->total_amount) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Jumlah yang diterima dan jumlah pada bukti harus sama persis dengan total tagihan.',
                    ]);
                }

                $lockedPayment->update([
                    'status' => 'lunas',
                    'active_invoice_id' => null,
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ]);
                $invoice->update([
                    'status' => 'lunas',
                    'active_code' => null,
                ]);
                $event->update(['status' => EventStatus::Terkonfirmasi]);
            } else {
                $lockedPayment->update([
                    'status' => 'ditolak',
                    'active_invoice_id' => null,
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                    'rejection_reason' => $data['reason'],
                ]);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => $action === 'approve' ? 'payment_verified' : 'payment_rejected',
                'subject_type' => $lockedPayment->getMorphClass(),
                'subject_id' => $lockedPayment->id,
                'from_status' => 'menunggu_verifikasi',
                'to_status' => $action === 'approve' ? 'lunas' : 'ditolak',
                'reason' => $data['reason'] ?? null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.pembayaran.detail', $payment->invoice_id)
            ->with('status', $action === 'approve'
                ? 'Pembayaran disahkan setelah nominal cocok; acara terkonfirmasi.'
                : 'Bukti pembayaran ditolak. Klien dapat mengunggah bukti pengganti.');
    }

    /**
     * Stream a payment proof to Admin only.
     */
    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless(is_string($payment->proof_path)
            && Storage::disk($payment->proof_disk)->exists($payment->proof_path), 404);

        return Storage::disk($payment->proof_disk)->download($payment->proof_path);
    }
}
