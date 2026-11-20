<?php

namespace App\Enums;

/**
 * Tipe baris rincian item dalam tagihan.
 */
enum InvoiceItemType: string
{
    case HargaPenyedia = 'harga_penyedia';
    case Komisi = 'komisi';
    case Kebersihan = 'kebersihan';

    public function label(): string
    {
        return match ($this) {
            self::HargaPenyedia => 'Harga penyedia',
            self::Komisi => 'Komisi platform',
            self::Kebersihan => 'Biaya kebersihan venue',
        };
    }
}
