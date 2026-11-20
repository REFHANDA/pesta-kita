<?php

namespace App\Models;

use Database\Factories\VenueRoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ruangan venue. Venue dengan satu ruang tetap punya satu baris room agar
 * kalender seragam. Pesanan dan penawaran venue mengacu ke ruangan ini
 * (B6: tanpa events.venue_id).
 */
#[Fillable(['venue_id', 'name', 'capacity'])]
class VenueRoom extends Model
{
    /** @use HasFactory<VenueRoomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * Venue induk ruangan.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Ketersediaan harian ruangan (morph subject venue_room).
     *
     * @return MorphMany<AvailabilityDay, $this>
     */
    public function availabilityDays(): MorphMany
    {
        return $this->morphMany(AvailabilityDay::class, 'subject');
    }

    /**
     * Item pesanan yang mengacu ruangan ini.
     *
     * @return MorphMany<OrderItem, $this>
     */
    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'subject');
    }
}
