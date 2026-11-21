<?php

namespace App\Enums;

/**
 * Mode harga item katalog: selalu tetap sesuai dokumen rancangan EMS.
 * Harga final disahkan Admin.
 */
enum PricingMode: string
{
    case Tetap = 'tetap';
}
