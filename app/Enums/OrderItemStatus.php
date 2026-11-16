<?php

namespace App\Enums;

/**
 * Status item pesanan (BACKEND_BUSINESS_RULES bagian C):
 * menunggu_admin -> disetujui atau ditolak; jika disetujui,
 * masa_keberatan -> mengikat. Masa keberatan (B7) belum dihitung pada
 * BE-2; kolom objection_deadline hanya tersedia nullable.
 */
enum OrderItemStatus: string
{
    case MenungguAdmin = 'menunggu_admin';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case MasaKeberatan = 'masa_keberatan';
    case Mengikat = 'mengikat';
}
