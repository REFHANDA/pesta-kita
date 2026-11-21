<?php

namespace App\Enums;

/**
 * Status pengajuan perubahan harga katalog/venue (BACKEND_BUSINESS_RULES C):
 * menunggu -> disetujui atau ditolak. Harga aktif berubah hanya saat
 * disetujui; selama menunggu harga lama tetap berlaku (aturan terkunci A11).
 */
enum PriceChangeStatus: string
{
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
}
