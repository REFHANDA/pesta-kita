<?php

namespace App\Enums;

/**
 * Mode harga item katalog: harga tetap atau perlu penawaran penyedia.
 * Harga final tetap disahkan Admin (aturan terkunci A5).
 */
enum PricingMode: string
{
    case Tetap = 'tetap';
    case PerluPenawaran = 'perlu_penawaran';
}
