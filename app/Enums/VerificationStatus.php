<?php

namespace App\Enums;

/**
 * Status verifikasi bisnis penyedia (Vendor/Venue).
 * Alur: menunggu -> terverifikasi atau ditolak; nonaktif menyembunyikan
 * katalog tanpa menghapus riwayat (BACKEND_BUSINESS_RULES bagian C).
 * Mekanisme verifikasi lengkap menyusul pada tahap berikutnya (B14/BE-3).
 */
enum VerificationStatus: string
{
    case Menunggu = 'menunggu';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';
    case Nonaktif = 'nonaktif';
}
