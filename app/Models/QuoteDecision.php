<?php

namespace App\Models;

use App\Enums\QuoteDecisionResult;
use Database\Factories\QuoteDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keputusan final Admin atas permintaan penawaran. Satu keputusan per
 * permintaan (unique quote_request_id). approved_amount hanya terisi
 * jika disetujui; alasan wajib saat menolak ditegakkan pada alur BE-4.
 */
#[Fillable(['quote_request_id', 'admin_id', 'decision', 'approved_amount', 'reason', 'decided_at'])]
class QuoteDecision extends Model
{
    /** @use HasFactory<QuoteDecisionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => QuoteDecisionResult::class,
            'approved_amount' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Permintaan penawaran yang diputuskan.
     *
     * @return BelongsTo<QuoteRequest, $this>
     */
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    /**
     * Admin yang memutuskan.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
