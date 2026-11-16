<?php

namespace App\Enums;

/**
 * Status ketersediaan manual penyedia (B11): hanya tiga status ini yang
 * disimpan. Status "dipesan" tidak disimpan di sini; kelak diturunkan
 * dari order_items terkonfirmasi pada tanggal yang sama (tahap berikutnya).
 */
enum AvailabilityStatus: string
{
    case Tersedia = 'tersedia';
    case TidakTersedia = 'tidak_tersedia';
    case BelumDikonfirmasi = 'belum_dikonfirmasi';
}
