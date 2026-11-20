<?php

namespace App\Models;

use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use Database\Factories\DisbursementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pencairan hak penyedia jasa atau kompensasi pembatalan.
 * Unique constraint per order_item_id dan type.
 */
#[Fillable([
    'order_item_id',
    'provider_type',
    'provider_id',
    'type',
    'amount',
    'status',
    'paid_at',
    'proof_media_id',
    'recorded_by',
])]
class Disbursement extends Model
{
    /** @use HasFactory<DisbursementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => DisbursementType::class,
            'status' => DisbursementStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Item pesanan asal pencairan hak.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Profil penyedia penerima pencairan (vendor_profile/venue_profile).
     *
     * @return MorphTo<Model, $this>
     */
    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Bukti transfer pencairan dana ke rekening penyedia.
     *
     * @return BelongsTo<Media, $this>
     */
    public function proof(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'proof_media_id');
    }

    /**
     * Admin yang mencatat pencairan dana.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
