<?php

namespace App\Enums;

/**
 * Status tagihan acara:
 * belum_dibayar -> menunggu_validasi -> lunas;
 * cabang: kedaluwarsa, dibatalkan.
 */
enum InvoiceStatus: string
{
    case BelumDibayar = 'belum_dibayar';
    case MenungguValidasi = 'menunggu_validasi';
    case Lunas = 'lunas';
    case Kedaluwarsa = 'kedaluwarsa';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum dibayar',
            self::MenungguValidasi => 'Menunggu validasi',
            self::Lunas => 'Lunas',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
