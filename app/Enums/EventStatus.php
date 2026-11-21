<?php

namespace App\Enums;

/**
 * Status acara sesuai alur rancangan EMS:
 * draft -> diajukan -> menunggu_pembayaran -> terkonfirmasi -> selesai -> dievaluasi;
 * cabang: dibatalkan.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Terkonfirmasi = 'terkonfirmasi';
    case Selesai = 'selesai';
    case Dievaluasi = 'dievaluasi';
    case Dibatalkan = 'dibatalkan';
}
