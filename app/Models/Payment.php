<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'active_invoice_id',
    'company_bank_account_id',
    'bank_name_snapshot',
    'account_number_snapshot',
    'account_name_snapshot',
    'sender_name',
    'amount',
    'transferred_at',
    'proof_disk',
    'proof_path',
    'status',
    'verified_by',
    'verified_at',
    'rejection_reason',
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
            'transferred_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Invoice being paid.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Destination company bank account snapshot source.
     *
     * @return BelongsTo<CompanyBankAccount, $this>
     */
    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    /**
     * Admin who verified or rejected this proof.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
