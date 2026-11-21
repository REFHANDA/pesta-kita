<?php

namespace App\Enums;

/**
 * Jenis pencairan dana untuk penyedia jasa.
 */
enum DisbursementType: string
{
    case HakPesanan = 'hak_pesanan';
    case Kompensasi = 'kompensasi';
}
