<?php

namespace App\Models;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use Database\Factories\CancellationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan pembatalan acara dan kalkulasi refund (snapshot kebijakan).
 */
#[Fillable([
    'event_id',
    'invoice_id',
    'reason',
    'cause',
    'refund_percent',
    'refund_amount',
    'unique_code_refund',
    'provider_compensation_total',
    'company_income',
    'policy_snapshot',
    'status',
    'refund_proof_media_id',
    'reviewed_by',
    'decision_reason',
    'refund_bank_name',
    'refund_account_number',
    'refund_account_name',
    'refund_paid_at',
])]
class Cancellation extends Model
{
    /** @use HasFactory<CancellationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refund_percent' => 'integer',
            'refund_amount' => 'integer',
            'unique_code_refund' => 'integer',
            'provider_compensation_total' => 'integer',
            'company_income' => 'integer',
            'policy_snapshot' => 'array',
            'refund_paid_at' => 'datetime',
            'cause' => CancellationCause::class,
            'status' => CancellationStatus::class,
        ];
    }

    /**
     * Acara yang dibatalkan.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Tagihan terkait pembatalan.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Bukti transfer refund kepada klien.
     *
     * @return BelongsTo<Media, $this>
     */
    public function refundProof(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'refund_proof_media_id');
    }

    /**
     * Total yang ditransfer ke klien: refund + kode unik.
     */
    public function refundTotal(): int
    {
        return (int) $this->refund_amount + (int) $this->unique_code_refund;
    }

    public function isActive(): bool
    {
        return in_array($this->status, CancellationStatus::active(), true);
    }

    /**
     * Admin yang meninjau pengajuan pembatalan.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
