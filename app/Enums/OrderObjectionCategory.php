<?php

namespace App\Enums;

/**
 * Kategori alasan keberatan penyedia.
 */
enum OrderObjectionCategory: string
{
    case AlatRusak = 'alat_rusak';
    case JadwalBentrok = 'jadwal_bentrok';
    case Kapasitas = 'kapasitas';
    case Lainnya = 'lainnya';
}
