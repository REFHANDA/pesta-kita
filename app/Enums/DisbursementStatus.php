<?php

namespace App\Enums;

/**
 * Status pencairan hak penyedia jasa.
 */
enum DisbursementStatus: string
{
    case BelumCair = 'belum_cair';
    case Cair = 'cair';
}
