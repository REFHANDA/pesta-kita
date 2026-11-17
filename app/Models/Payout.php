<?php

namespace App\Models;

use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_item_id',
    'provider_bank_account_id',
    'bank_name_snapshot',
    'account_number_snapshot',
    'account_name_snapshot',
    'amount',
    'transferred_at',
    'proof_disk',
    'proof_path',
    'recorded_by',
    'notes',
])]
class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
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
            'transferred_at' => 'date',
        ];
    }

    /**
     * Item whose provider right was transferred.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Provider bank account used for the transfer.
     *
     * @return BelongsTo<ProviderBankAccount, $this>
     */
    public function providerBankAccount(): BelongsTo
    {
        return $this->belongsTo(ProviderBankAccount::class);
    }

    /**
     * Admin who recorded the completed transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
