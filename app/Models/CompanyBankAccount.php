<?php

namespace App\Models;

use Database\Factories\CompanyBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rekening perusahaan tujuan transfer klien (pembayaran manual).
 */
#[Fillable(['bank_name', 'account_number', 'account_name', 'is_active', 'sort_order'])]
class CompanyBankAccount extends Model
{
    /** @use HasFactory<CompanyBankAccountFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Pembayaran yang ditujukan ke rekening ini.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
