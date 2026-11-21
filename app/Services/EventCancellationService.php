<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\EventCancelledNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pembatalan acara oleh klien sebelum pembayaran divalidasi (Tahap B2):
 * otomatis, tanpa biaya, tanpa baris `cancellations`.
 */
class EventCancellationService
{
    public const MESSAGE_PAYMENT_UNDER_VALIDATION = 'Bukti pembayaran Anda sedang divalidasi Admin. Setelah divalidasi, ajukan pembatalan.';

    public function __construct(
        protected AvailabilityService $availability,
    ) {}

    /**
     * @throws RuntimeException kode 409 bila sudah dibatalkan, 422 bila status tidak mengizinkan.
     */
    public function cancelBeforePayment(Event $event, User $actor, ?string $ip = null): void
    {
        DB::transaction(function () use ($event, $actor, $ip): void {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === EventStatus::Dibatalkan) {
                throw new RuntimeException('Acara ini sudah dibatalkan.', 409);
            }

            if (! in_array($locked->status, [EventStatus::Diajukan, EventStatus::MenungguPembayaran], true)) {
                throw new RuntimeException('Acara yang sudah lunas tidak dapat dibatalkan otomatis. Ajukan pembatalan melalui formulir pembatalan.', 422);
            }

            $invoices = Invoice::query()
                ->where('event_id', $locked->id)
                ->whereIn('status', [InvoiceStatus::BelumDibayar, InvoiceStatus::MenungguValidasi, InvoiceStatus::Lunas])
                ->lockForUpdate()
                ->get();

            if ($invoices->contains(fn (Invoice $invoice): bool => $invoice->status === InvoiceStatus::MenungguValidasi)) {
                throw new RuntimeException(self::MESSAGE_PAYMENT_UNDER_VALIDATION, 422);
            }

            if ($invoices->contains(fn (Invoice $invoice): bool => $invoice->status === InvoiceStatus::Lunas)) {
                throw new RuntimeException('Acara yang sudah lunas tidak dapat dibatalkan otomatis. Ajukan pembatalan melalui formulir pembatalan.', 422);
            }

            $fromStatus = $locked->status;

            $items = $locked->orderItems()
                ->whereIn('status', [OrderItemStatus::Draft, OrderItemStatus::MenungguReview, OrderItemStatus::Disetujui])
                ->lockForUpdate()
                ->get();

            $approvedItems = $items->filter(fn ($item): bool => $item->status === OrderItemStatus::Disetujui);

            foreach ($items as $item) {
                $item->update(['status' => OrderItemStatus::Dibatalkan]);
                $this->availability->releaseHold($item);
            }

            foreach ($invoices as $invoice) {
                $invoice->update(['status' => InvoiceStatus::Dibatalkan]);
            }

            $locked->update(['status' => EventStatus::Dibatalkan]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => 'event_cancelled_before_payment',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => $fromStatus->value,
                'to_status' => EventStatus::Dibatalkan->value,
                'reason' => 'Dibatalkan klien sebelum pembayaran (tanpa biaya).'
                    .($invoices->isNotEmpty() ? ' Tagihan dibatalkan: '.$invoices->pluck('invoice_number')->implode(', ').'.' : ''),
                'ip' => $ip,
            ]);

            $message = "Acara #{$locked->id} dibatalkan sebelum pembayaran.";

            User::query()->where('role', Role::Admin->value)->get()
                ->each(fn (User $admin) => $admin->notify(new EventCancelledNotification($locked, $message)));

            $notified = [];
            foreach ($approvedItems as $item) {
                $providerUser = $item->provider?->user;
                if ($providerUser && ! isset($notified[$providerUser->id])) {
                    $notified[$providerUser->id] = true;
                    $providerUser->notify(new EventCancelledNotification($locked, "Acara #{$locked->id} dibatalkan. Pesanan Anda untuk acara ini ikut dibatalkan.", $item->id));
                }
            }
        });
    }
}
