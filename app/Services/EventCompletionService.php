<?php

namespace App\Services;

use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\EventCompletedNotification;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Admin menandai acara selesai (Tahap B6) dan membuat pencairan hak pesanan
 * sebesar harga penyedia × jumlah. Komisi dan biaya kebersihan tetap menjadi
 * pendapatan perusahaan.
 */
class EventCompletionService
{
    /**
     * Alasan acara belum bisa ditandai selesai; null bila boleh.
     */
    public function blocker(Event $event, ?DateTimeInterface $today = null): ?string
    {
        if ($event->status === EventStatus::Selesai) {
            return 'Acara ini sudah ditandai selesai.';
        }

        if ($event->status !== EventStatus::Terkonfirmasi) {
            return 'Hanya acara terkonfirmasi yang dapat ditandai selesai.';
        }

        $today = CarbonImmutable::instance($today ?? now())->setTimezone(RefundCalculator::TIMEZONE)->startOfDay();
        $eventDate = CarbonImmutable::parse($event->event_date->toDateString(), RefundCalculator::TIMEZONE);

        if ($today->lessThanOrEqualTo($eventDate)) {
            return 'Acara baru dapat ditandai selesai setelah tanggal acara ('.$eventDate->translatedFormat('d M Y').') terlewati.';
        }

        $hasActiveCancellation = Cancellation::query()
            ->where('event_id', $event->id)
            ->whereIn('status', array_column(CancellationStatus::active(), 'value'))
            ->exists();

        if ($hasActiveCancellation) {
            return 'Acara memiliki pengajuan pembatalan yang masih diproses. Selesaikan atau tolak pembatalan terlebih dahulu.';
        }

        return null;
    }

    /**
     * @throws RuntimeException kode 409 bila sudah selesai, 422 bila syarat tidak terpenuhi.
     */
    public function complete(Event $event, User $admin, ?string $ip = null): Event
    {
        return DB::transaction(function () use ($event, $admin, $ip): Event {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($blocker = $this->blocker($locked)) {
                throw new RuntimeException($blocker, $locked->status === EventStatus::Selesai ? 409 : 422);
            }

            $items = OrderItem::query()
                ->where('event_id', $locked->id)
                ->where('status', OrderItemStatus::Terkonfirmasi)
                ->lockForUpdate()
                ->get();

            $locked->update(['status' => EventStatus::Selesai]);

            $notified = [];
            foreach ($items as $item) {
                $amount = $item->lineTotal();
                $item->update(['status' => OrderItemStatus::Selesai]);

                Disbursement::query()->firstOrCreate(
                    ['order_item_id' => $item->id, 'type' => DisbursementType::HakPesanan],
                    [
                        'provider_type' => $item->provider_type,
                        'provider_id' => $item->provider_id,
                        'amount' => $amount,
                        'status' => DisbursementStatus::BelumCair,
                    ],
                );

                $providerUser = $item->provider?->user;
                if ($providerUser && ! isset($notified[$providerUser->id])) {
                    $notified[$providerUser->id] = true;
                    $providerUser->notify(new EventCompletedNotification($locked, "Acara #{$locked->id} selesai; hak Anda siap dicairkan.", $item->id));
                }
            }

            AuditLog::create([
                'actor_id' => $admin->id,
                'action' => 'event_completed',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => EventStatus::Terkonfirmasi->value,
                'to_status' => EventStatus::Selesai->value,
                'reason' => 'Acara ditandai selesai; '.$items->count().' pencairan hak pesanan dibuat.',
                'ip' => $ip,
            ]);

            $locked->client?->notify(new EventCompletedNotification($locked, "Acara #{$locked->id} ({$locked->name}) telah selesai. Terima kasih telah menggunakan Pesta Kita."));

            return $locked;
        });
    }
}
