<?php

namespace App\Models;

use App\Enums\PriceChangeStatus;
use Database\Factories\PriceChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pengajuan perubahan harga katalog atau venue (morph subject). Selama
 * menunggu, harga lama tetap berlaku; harga aktif berubah hanya saat
 * disetujui Admin (aturan terkunci A11).
 */
#[Fillable([
    'subject_type',
    'subject_id',
    'old_price',
    'new_price',
    'reason',
    'status',
    'reviewed_by',
    'reviewed_at',
])]
class PriceChangeRequest extends Model
{
    /** @use HasFactory<PriceChangeRequestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PriceChangeStatus::class,
            'old_price' => 'integer',
            'new_price' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Subjek pengajuan (catalog_item atau venue).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Admin yang meninjau pengajuan.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
