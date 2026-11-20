<?php

namespace App\Enums;

/**
 * Status bukti pembayaran yang diunggah klien.
 */
enum PaymentStatus: string
{
    case MenungguValidasi = 'menunggu_validasi';
    case Valid = 'valid';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::MenungguValidasi => 'Menunggu validasi',
            self::Valid => 'Valid',
            self::Ditolak => 'Ditolak',
        };
    }
}
