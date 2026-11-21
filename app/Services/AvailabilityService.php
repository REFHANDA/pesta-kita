<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\CatalogItemStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use App\Notifications\NewOrderSubmittedNotification;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Layanan ketersediaan stok peralatan, kuota layanan, dan slot venue sesuai aturan EMS §1.7:
 * - Stok peralatan tersedia = stok total - jumlah sewa pesanan berstatus menahan.
 * - Venue: 1 tanggal, 1 venue, 1 pesanan penahan.
 * - Status menahan: menunggu_review, disetujui, terkonfirmasi.
 * - Transaksi DB + lockForUpdate untuk mencegah race condition.
 */
class AvailabilityService
{
    /**
     * Status pesanan yang menahan ketersediaan / slot.
     *
     * @var array<int, OrderItemStatus>
     */
    public const HOLDING_STATUSES = [
        OrderItemStatus::MenungguReview,
        OrderItemStatus::Disetujui,
        OrderItemStatus::Terkonfirmasi,
    ];

    /**
     * Ambil sisa stok peralatan atau kuota layanan pada tanggal tertentu.
     */
    public function getAvailableStock(CatalogItem $item, string|DateTimeInterface $date, ?int $excludeOrderItemId = null): int
    {
        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;

        if ($this->isManualUnavailable($item, $dateStr)) {
            return 0;
        }

        if ($item->max_per_day === null) {
            return PHP_INT_MAX;
        }

        $holdingStatuses = array_map(fn (OrderItemStatus $s) => $s->value, self::HOLDING_STATUSES);

        $query = OrderItem::query()
            ->where('subject_type', 'catalog_item')
            ->where('subject_id', $item->id)
            ->whereDate('usage_date', $dateStr)
            ->whereIn('status', $holdingStatuses);

        if ($excludeOrderItemId !== null) {
            $query->where('id', '!=', $excludeOrderItemId);
        }

        $booked = (int) $query->sum('quantity');

        return max(0, (int) $item->max_per_day - $booked);
    }

    /**
     * Periksa apakah item katalog dapat dipesan pada tanggal dan jumlah tertentu.
     */
    public function isCatalogItemAvailable(CatalogItem $item, string|DateTimeInterface $date, int $requestedQuantity = 1, ?int $excludeOrderItemId = null): bool
    {
        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;

        if ($item->status !== CatalogItemStatus::Aktif) {
            return false;
        }

        if ($item->vendorProfile && $item->vendorProfile->verification_status !== VerificationStatus::Terverifikasi) {
            return false;
        }

        return $this->getAvailableStock($item, $dateStr, $excludeOrderItemId) >= $requestedQuantity;
    }

    /**
     * Periksa apakah ruangan venue dapat dipesan pada tanggal tertentu (aturan 1 venue per tanggal).
     */
    public function isVenueRoomAvailable(VenueRoom $room, string|DateTimeInterface $date, ?int $excludeOrderItemId = null): bool
    {
        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;

        if ($this->isManualUnavailable($room, $dateStr)) {
            return false;
        }

        $venue = $room->venue;
        if (! $venue || $venue->verification_status !== VerificationStatus::Terverifikasi) {
            return false;
        }

        $holdingStatuses = array_map(fn (OrderItemStatus $s) => $s->value, self::HOLDING_STATUSES);
        $holdKey = $this->makeVenueHoldKey((int) $venue->id, $dateStr);

        $query = OrderItem::query()
            ->where(function ($q) use ($holdKey, $venue, $dateStr) {
                $q->where('hold_key', $holdKey)
                    ->orWhere(function ($sub) use ($venue, $dateStr) {
                        $sub->where('subject_type', 'venue_room')
                            ->whereIn('subject_id', $venue->rooms()->pluck('id'))
                            ->whereDate('usage_date', $dateStr);
                    });
            })
            ->whereIn('status', $holdingStatuses);

        if ($excludeOrderItemId !== null) {
            $query->where('id', '!=', $excludeOrderItemId);
        }

        return ! $query->exists();
    }

    /**
     * Periksa ketersediaan umum untuk subjek (CatalogItem atau VenueRoom).
     */
    public function isSubjectAvailable(Model $subject, string|DateTimeInterface $date, int $quantity = 1, ?int $excludeOrderItemId = null): bool
    {
        if ($subject instanceof CatalogItem) {
            return $this->isCatalogItemAvailable($subject, $date, $quantity, $excludeOrderItemId);
        }

        if ($subject instanceof VenueRoom) {
            return $this->isVenueRoomAvailable($subject, $date, $excludeOrderItemId);
        }

        return false;
    }

    /**
     * Kunci penahan unik venue per tanggal: venue:{venue_id}:{tanggal}.
     */
    public function makeVenueHoldKey(int $venueId, string $date): string
    {
        return "venue:{$venueId}:{$date}";
    }

    /**
     * Periksa apakah subjek ditandai 'tidak_tersedia' manual pada kalender ketersediaan.
     */
    public function isManualUnavailable(Model $subject, string $date): bool
    {
        $morphType = $subject instanceof CatalogItem ? 'catalog_item' : ($subject instanceof VenueRoom ? 'venue_room' : null);
        if (! $morphType) {
            return false;
        }

        return AvailabilityDay::query()
            ->where('subject_type', $morphType)
            ->where('subject_id', $subject->getKey())
            ->whereDate('date', $date)
            ->where('status', AvailabilityStatus::TidakTersedia->value)
            ->exists();
    }

    /**
     * Dapatkan pemetaan ketersediaan harian untuk subjek, menggabungkan input manual penyedia dan pesanan penahan.
     *
     * @return array<string, string> Pemetaan [ 'YYYY-MM-DD' => 'tersedia'|'tidak'|'belum' ]
     */
    public function getMergedAvailabilityMap(Model $subject, int $limitDays = 180): array
    {
        $morphType = $subject instanceof CatalogItem ? 'catalog_item' : ($subject instanceof VenueRoom ? 'venue_room' : null);
        if (! $morphType) {
            return [];
        }

        $startDate = today()->toDateString();
        $manualDays = AvailabilityDay::query()
            ->where('subject_type', $morphType)
            ->where('subject_id', $subject->getKey())
            ->whereDate('date', '>=', $startDate)
            ->get()
            ->keyBy(fn (AvailabilityDay $day) => $day->date->toDateString());

        $result = [];
        $holdingStatuses = array_map(fn (OrderItemStatus $s) => $s->value, self::HOLDING_STATUSES);

        // Cari semua tanggal pesanan menahan untuk subjek ini
        if ($subject instanceof VenueRoom) {
            $venue = $subject->venue;
            $venueId = $venue ? (int) $venue->id : 0;
            $heldDates = OrderItem::query()
                ->whereIn('status', $holdingStatuses)
                ->whereDate('usage_date', '>=', $startDate)
                ->where(function ($q) use ($venueId, $subject) {
                    $q->where('hold_key', 'like', "venue:{$venueId}:%")
                        ->orWhere(function ($sub) use ($subject) {
                            $sub->where('subject_type', 'venue_room')
                                ->where('subject_id', $subject->id);
                        });
                })
                ->pluck('usage_date')
                ->map(fn ($d) => $d instanceof DateTimeInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10))
                ->flip();
        } else {
            /** @var CatalogItem $subject */
            $heldDates = collect();
            if ($subject->max_per_day !== null) {
                $heldSums = OrderItem::query()
                    ->where('subject_type', 'catalog_item')
                    ->where('subject_id', $subject->id)
                    ->whereIn('status', $holdingStatuses)
                    ->whereDate('usage_date', '>=', $startDate)
                    ->groupBy('usage_date')
                    ->selectRaw('usage_date, SUM(quantity) as total_qty')
                    ->havingRaw('SUM(quantity) >= ?', [$subject->max_per_day])
                    ->pluck('usage_date')
                    ->map(fn ($d) => $d instanceof DateTimeInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10))
                    ->flip();
                $heldDates = $heldSums;
            }
        }

        // Gabungkan tanggal manual dan penahan
        $allDates = $manualDays->keys()->merge($heldDates->keys())->unique();

        foreach ($allDates as $dateStr) {
            if ($heldDates->has($dateStr)) {
                $result[$dateStr] = 'tidak';

                continue;
            }

            $manual = $manualDays->get($dateStr);
            if ($manual) {
                $result[$dateStr] = match ($manual->status) {
                    AvailabilityStatus::TidakTersedia => 'tidak',
                    AvailabilityStatus::Tersedia => 'tersedia',
                    AvailabilityStatus::BelumDikonfirmasi => 'belum',
                };
            }
        }

        return $result;
    }

    /**
     * Lepaskan penahan slot pada item pesanan (dibatalkan / ditolak).
     */
    public function releaseHold(OrderItem $item): void
    {
        $item->update([
            'hold_key' => null,
        ]);
    }

    /**
     * Validasi dan ajukan acara dalam satu transaksi DB dengan lockForUpdate.
     */
    public function validateAndSubmitEvent(Event $event): void
    {
        DB::transaction(function () use ($event) {
            /** @var Event $lockedEvent */
            $lockedEvent = Event::query()->where('id', $event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status !== EventStatus::Draft) {
                throw new RuntimeException('Acara hanya dapat diajukan saat berstatus draft.');
            }

            if (! $lockedEvent->event_date) {
                throw new RuntimeException('Tanggal acara wajib diisi sebelum mengajukan acara.');
            }

            $eventDateStr = $lockedEvent->event_date->toDateString();

            $orderItems = $lockedEvent->orderItems()->lockForUpdate()->get();
            if ($orderItems->isEmpty()) {
                throw new RuntimeException('Acara wajib memiliki setidaknya satu pesanan layanan atau venue sebelum diajukan.');
            }

            $slaHours = (int) Setting::get('admin_review_sla_hours', 24);
            $reviewDueAt = now()->addHours($slaHours);

            foreach ($orderItems as $item) {
                $subject = $item->subject;
                if (! $subject) {
                    throw new RuntimeException('Item pesanan tidak memiliki subjek katalog yang valid.');
                }

                if ($subject instanceof CatalogItem) {
                    CatalogItem::query()->where('id', $subject->id)->lockForUpdate()->first();

                    $qty = max(1, (int) $item->quantity);
                    if (! $this->isCatalogItemAvailable($subject, $eventDateStr, $qty, $item->id)) {
                        throw new RuntimeException("Layanan atau peralatan '{$subject->name}' tidak tersedia pada tanggal {$eventDateStr}.");
                    }

                    $activePrice = (int) $subject->catalog_price;
                    $item->update([
                        'usage_date' => $eventDateStr,
                        'quantity' => $qty,
                        'catalog_price_snapshot' => $activePrice,
                        'provider_price' => $activePrice,
                        'status' => OrderItemStatus::MenungguReview,
                        'review_due_at' => $reviewDueAt,
                        'hold_key' => null,
                    ]);
                } elseif ($subject instanceof VenueRoom) {
                    VenueRoom::query()->where('id', $subject->id)->lockForUpdate()->first();

                    if (! $this->isVenueRoomAvailable($subject, $eventDateStr, $item->id)) {
                        $venueName = $subject->venue?->name ?? 'Venue';
                        throw new RuntimeException("Venue '{$venueName}' tidak tersedia pada tanggal {$eventDateStr}.");
                    }

                    $venue = $subject->venue;
                    $rentalPrice = (int) ($venue?->rental_price ?? 0);
                    $holdKey = $this->makeVenueHoldKey((int) $venue->id, $eventDateStr);
                    
                    // Lepas hold_key sisa pada baris yang sudah tidak menahan slot.
                    OrderItem::query()
                        ->where('hold_key', $holdKey)
                        ->where('id', '!=', $item->id)
                        ->whereNotIn('status', array_map(fn (OrderItemStatus $s) => $s->value, self::HOLDING_STATUSES))
                        ->update(['hold_key' => null]);

                    $item->update([
                        'usage_date' => $eventDateStr,
                        'quantity' => 1,
                        'catalog_price_snapshot' => $rentalPrice,
                        'provider_price' => $rentalPrice,
                        'status' => OrderItemStatus::MenungguReview,
                        'review_due_at' => $reviewDueAt,
                        'hold_key' => $holdKey,
                    ]);
                } else {
                    throw new RuntimeException('Jenis subjek pesanan tidak didukung.');
                }
            }

            $lockedEvent->update([
                'status' => EventStatus::Diajukan,
            ]);

            // Kirim notifikasi ke Admin
            $admins = User::query()->where('role', Role::Admin->value)->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewOrderSubmittedNotification($lockedEvent));
            }
        });
    }
}
