<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Jejak audit aksi penting: aktor, subjek, status lama/baru, dan alasan.
 * actor_id nullable untuk aksi sistem. Tidak menyimpan bukti file atau
 * nomor rekening utuh.
 */
#[Fillable([
    'actor_id',
    'action',
    'subject_type',
    'subject_id',
    'from_status',
    'to_status',
    'reason',
    'ip',
])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    /**
     * Akun pelaku aksi; null untuk aksi sistem.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Subjek aksi (model domain apa pun).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
