<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Item pesanan dalam acara. provider morph ke profil penyedia; subject
 * morph ke catalog_item atau venue_room (B6: beberapa venue per acara
 * mungkin lewat item berbeda). Tiga nominal terpisah; final_price hanya
 * dikunci Admin. B7: objection_deadline nullable tanpa logika kedaluwarsa.
 */
#[Fillable([
    'event_id',
    'provider_type',
    'provider_id',
    'subject_type',
    'subject_id',
    'quote_request_id',
    'catalog_price_snapshot',
    'proposed_price_snapshot',
    'final_price',
    'status',
    'rejection_reason',
    'objection_deadline',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'catalog_price_snapshot' => 'integer',
            'proposed_price_snapshot' => 'integer',
            'final_price' => 'integer',
            'status' => OrderItemStatus::class,
            'objection_deadline' => 'datetime',
        ];
    }

    /**
     * Acara pemilik item pesanan.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Profil penyedia item (vendor_profile/venue_profile).
     *
     * @return MorphTo<Model, $this>
     */
    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Subjek pesanan (catalog_item atau venue_room).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Permintaan penawaran asal item; nullable untuk harga tetap.
     *
     * @return BelongsTo<QuoteRequest, $this>
     */
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    /**
     * Keberatan penyedia atas item ini.
     *
     * @return HasMany<OrderObjection, $this>
     */
    public function objections(): HasMany
    {
        return $this->hasMany(OrderObjection::class);
    }

    /**
     * Immutable invoice snapshots containing this item.
     *
     * @return HasMany<InvoiceLine, $this>
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Single provider payout allowed for this item.
     *
     * @return HasOne<Payout, $this>
     */
    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class);
    }

    /**
     * Client review for this item.
     *
     * @return HasOne<EventReview, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(EventReview::class);
    }

    /**
     * Rundown segments tied to this provider's order.
     *
     * @return HasMany<RundownItem, $this>
     */
    public function rundownItems(): HasMany
    {
        return $this->hasMany(RundownItem::class);
    }
}
