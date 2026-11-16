<?php

namespace App\Models;

use Database\Factories\CompanyBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Rekening perusahaan tujuan transfer Klien. Dipilih Klien saat membayar
 * (alur pembayaran BE-6); dikelola Admin lewat admin/rekening.
 */
#[Fillable(['bank_name', 'account_number', 'account_name', 'is_active'])]
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
        ];
    }
}
