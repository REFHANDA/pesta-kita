<?php

namespace App\Enums;

/**
 * Status pengajuan pembatalan acara dan proses refund.
 */
enum CancellationStatus: string
{
    case Diajukan = 'diajukan';
    case Ditinjau = 'ditinjau';
    case Disetujui = 'disetujui';
    case RefundDiproses = 'refund_diproses';
    case RefundSelesai = 'refund_selesai';
    case Ditolak = 'ditolak';
}
