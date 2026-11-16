<?php

namespace App\Enums;

/**
 * Hasil keputusan Admin atas permintaan penawaran (C.1): disetujui atau
 * ditolak. approved_amount hanya terisi jika disetujui. Satu keputusan
 * final per quote request dijaga unique constraint.
 */
enum QuoteDecisionResult: string
{
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
}
