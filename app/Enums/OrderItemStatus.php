<?php

namespace App\Enums;

/**
 * Status item pesanan sesuai dokumen rancangan EMS:
 * menunggu_review -> disetujui -> terkonfirmasi -> selesai;
 * cabang: ditolak, dialihkan, dibatalkan.
 * Masa keberatan dihitung dari approved_at + objection_window_hours, bukan status.
 */
enum OrderItemStatus: string
{
    case Draft = 'draft';
    case MenungguReview = 'menunggu_review';
    case Disetujui = 'disetujui';
    case Terkonfirmasi = 'terkonfirmasi';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';
    case Dialihkan = 'dialihkan';
    case Dibatalkan = 'dibatalkan';
}
