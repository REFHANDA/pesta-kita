<?php

namespace App\Enums;

/**
 * Pihak penyebab atau alasan pembatalan acara.
 */
enum CancellationCause: string
{
    case Klien = 'klien';
    case Vendor = 'vendor';
    case ForceMajeure = 'force_majeure';
}
