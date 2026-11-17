<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreRefundRequest;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\RefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RefundRequestController extends Controller
{
    /**
     * Show refund-intake details without calculating or promising an amount.
     */
    public function create(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        $invoice = $event->invoices()->where('status', 'lunas')->latest('version')->firstOrFail();

        return view('klien.acara-batalkan', [
            'event' => $event,
            'invoice' => $invoice,
            'refundRequest' => $event->refundRequest,
        ]);
    }

    /**
     * Record only the client's request; final policy and amount remain deferred.
     */
    public function store(StoreRefundRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);

        $refundRequest = DB::transaction(function () use ($request, $event): RefundRequest {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($lockedEvent->refundRequest()->exists()) {
                throw ValidationException::withMessages([
                    'refund' => 'Permohonan refund untuk acara ini sudah tercatat.',
                ]);
            }

            if (! in_array($lockedEvent->status, [EventStatus::Terkonfirmasi, EventStatus::Selesai], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'Permohonan refund hanya dapat dicatat untuk acara yang sudah dibayar.',
                ]);
            }

            $invoice = Invoice::query()
                ->where('event_id', $lockedEvent->id)
                ->where('status', 'lunas')
                ->latest('version')
                ->lockForUpdate()
                ->first();
            if ($invoice === null) {
                throw ValidationException::withMessages([
                    'refund' => 'Tidak ditemukan tagihan lunas untuk acara ini.',
                ]);
            }

            $refundRequest = $lockedEvent->refundRequest()->create([
                'invoice_id' => $invoice->id,
                'client_id' => $request->user()->id,
                'reason' => $request->validated('reason'),
                'status' => 'diajukan',
                'submitted_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'refund_intake_submitted',
                'subject_type' => $refundRequest->getMorphClass(),
                'subject_id' => $refundRequest->id,
                'from_status' => null,
                'to_status' => $refundRequest->status,
                'reason' => $refundRequest->reason,
                'ip' => $request->ip(),
            ]);

            return $refundRequest;
        });

        return redirect()->route('klien.refund.create', $event)
            ->with('status', 'Permohonan tercatat untuk ditinjau. Keputusan dan nominal final belum tersedia.');
    }
}
