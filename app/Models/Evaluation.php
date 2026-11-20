<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Evaluasi dan penilaian akhir acara oleh klien.
 */
#[Fillable(['event_id', 'system_feedback'])]
class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    /**
     * Acara yang dievaluasi.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Rincian penilaian (venue, masing-masing vendor, dan admin).
     *
     * @return HasMany<EvaluationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EvaluationItem::class);
    }
}
