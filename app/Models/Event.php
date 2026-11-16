<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Acara milik Klien. B6: tanpa events.venue_id; venue direpresentasikan
 * lewat order_items yang mengacu ke venue_room, sehingga satu acara dapat
 * memuat beberapa venue. Transisi status menyusul BE-5.
 */
#[Fillable(['client_id', 'name', 'event_date', 'guest_count', 'theme', 'description', 'status'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'guest_count' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    /**
     * Akun Klien pemilik acara.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Permintaan penawaran dalam acara.
     *
     * @return HasMany<QuoteRequest, $this>
     */
    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    /**
     * Item pesanan dalam acara (tagihan gabungan BE-6).
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
