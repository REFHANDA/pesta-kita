<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti pembayaran transfer manual dari Klien.
 */
#[Fillable([
    'invoice_id',
    'company_bank_account_id',
    'amount',
    'sender_name',
    'transfer_date',
    'proof_media_id',
    'status',
    'admin_note',
    'confirmed_by',
    'confirmed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
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
            'transfer_date' => 'date:Y-m-d',
            'status' => PaymentStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Tagihan yang dibayar.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Rekening bank tujuan transfer.
     *
     * @return BelongsTo<CompanyBankAccount, $this>
     */
    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    /**
     * Media file bukti transfer.
     *
     * @return BelongsTo<Media, $this>
     */
    public function proof(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'proof_media_id');
    }

    /**
     * Admin yang memvalidasi atau menolak pembayaran.
     *
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
