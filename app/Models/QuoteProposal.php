<?php

namespace App\Models;

use Database\Factories\QuoteProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usulan harga penyedia atas permintaan penawaran. Bukan harga final;
 * keputusan Admin yang mengesahkan nominal (aturan terkunci A5).
 */
#[Fillable(['quote_request_id', 'proposed_amount', 'breakdown', 'notes', 'submitted_at'])]
class QuoteProposal extends Model
{
    /** @use HasFactory<QuoteProposalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proposed_amount' => 'integer',
            'breakdown' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Permintaan penawaran yang diusulkan.
     *
     * @return BelongsTo<QuoteRequest, $this>
     */
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }
}
