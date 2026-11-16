<?php

namespace App\Enums;

/**
 * Status item katalog mengikuti label Blade vendor/layanan:
 * Aktif, Menunggu persetujuan, Nonaktif. Item baru menunggu persetujuan
 * Pesta Kita sebelum tampil di katalog (alur lengkap menyusul BE-3).
 */
enum CatalogItemStatus: string
{
    case Aktif = 'aktif';
    case MenungguPersetujuan = 'menunggu_persetujuan';
    case Nonaktif = 'nonaktif';
}
