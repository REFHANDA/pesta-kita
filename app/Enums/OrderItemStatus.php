<?php

namespace App\Enums;

/**
 * Status item pesanan (BACKEND_BUSINESS_RULES bagian C):
 * menunggu_admin -> disetujui atau ditolak; jika disetujui,
 * masa_keberatan -> mengikat. BE-5 menetapkan deadline keberatan 24 jam
 * setelah persetujuan Admin dan mengikat item setelah tenggat berakhir.
 */
enum OrderItemStatus: string
{
    case MenungguAdmin = 'menunggu_admin';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case MasaKeberatan = 'masa_keberatan';
    case Mengikat = 'mengikat';
}
