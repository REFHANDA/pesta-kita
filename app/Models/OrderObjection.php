<?php

namespace App\Models;

use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use Database\Factories\OrderObjectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keberatan penyedia atas item pesanan sesuai rancangan EMS.
 * Kategori: alat_rusak, jadwal_bentrok, kapasitas, lainnya.
 * Status: diajukan, ditinjau, diterima, ditolak.
 */
#[Fillable([
    'order_item_id',
    'provider_user_id',
    'category',
    'reason',
    'evidence_media_id',
    'is_emergency',
    'status',
    'decision_reason',
    'reviewed_by',
    'admin_decision',
    'decided_at',
])]
class OrderObjection extends Model
{
    /** @use HasFactory<OrderObjectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => OrderObjectionCategory::class,
            'status' => OrderObjectionStatus::class,
            'is_emergency' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Item pesanan yang disengketakan.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Akun penyedia pengaju keberatan.
     *
     * @return BelongsTo<User, $this>
     */
    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }

    /**
     * Bukti media (wajib jika keberatan darurat).
     *
     * @return BelongsTo<Media, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'evidence_media_id');
    }

    /**
     * Admin yang meninjau dan memutuskan keberatan.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
