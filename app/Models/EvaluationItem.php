<?php

namespace App\Models;

use Database\Factories\EvaluationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian penilaian per subjek (venue, vendor, admin) dengan rating 1-5 dan komentar opsional.
 */
#[Fillable([
    'evaluation_id',
    'target_type',
    'target_id',
    'rating',
    'comment',
])]
class EvaluationItem extends Model
{
    /** @use HasFactory<EvaluationItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * Induk evaluasi acara.
     *
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
