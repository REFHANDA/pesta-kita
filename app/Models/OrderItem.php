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
 * Item pesanan dalam acara sesuai rancangan EMS.
 * Harga penyedia dikunci saat disetujui Admin; komisi 10% disimpan per pesanan.
 */
#[Fillable([
    'event_id',
    'usage_date',
    'quantity',
    'provider_type',
    'provider_id',
    'subject_type',
    'subject_id',
    'catalog_price_snapshot',
    'provider_price',
    'commission',
    'status',
    'rejection_reason',
    'admin_note',
    'reviewed_by',
    'reviewed_at',
    'review_due_at',
    'approved_at',
    'objection_deadline',
    'replaced_by_order_item_id',
    'hold_key',
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
            'usage_date' => 'date:Y-m-d',
            'quantity' => 'integer',
            'catalog_price_snapshot' => 'integer',
            'provider_price' => 'integer',
            'commission' => 'integer',
            'status' => OrderItemStatus::class,
            'reviewed_at' => 'datetime',
            'review_due_at' => 'datetime',
            'approved_at' => 'datetime',
            'objection_deadline' => 'datetime',
        ];
    }

    /**
     * Alias backward-compatible final_price mengarah ke provider_price.
     */
    public function getFinalPriceAttribute(): ?int
    {
        return $this->provider_price;
    }

    /**
     * Total baris pesanan = harga satuan penyedia x kuantitas.
     * Tidak fallback ke catalog_price_snapshot; lempar exception bila provider_price null
     * pada pesanan berstatus disetujui atau terkonfirmasi.
     */
    public function lineTotal(): int
    {
        if (in_array($this->status, [OrderItemStatus::Disetujui, OrderItemStatus::Terkonfirmasi], true) && $this->provider_price === null) {
            throw new \UnexpectedValueException("Harga penyedia (provider_price) tidak boleh null untuk pesanan berstatus {$this->status->value}.");
        }

        $unitPrice = (int) ($this->provider_price ?? 0);
        $qty = max(1, (int) ($this->quantity ?? 1));

        return $unitPrice * $qty;
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
     * Admin yang mereview pesanan ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Pesanan pengganti bila item ini dialihkan.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'replaced_by_order_item_id');
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
     * Pencairan dana untuk pesanan ini.
     *
     * @return HasMany<Disbursement, $this>
     */
    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    /**
     * Baris rincian tagihan terkait item pesanan ini.
     *
     * @return HasOne<InvoiceItem, $this>
     */
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(InvoiceItem::class);
    }
}
