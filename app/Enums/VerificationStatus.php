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

    /**
     * Label tampilan status verifikasi (profil maupun rekening penyedia).
     */
    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditolak => 'Ditolak',
            self::Nonaktif => 'Nonaktif',
        };
    }

    /**
     * Kelas lencana status yang sudah ada di CSS portal.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Menunggu => 'b-warn',
            self::Terverifikasi => 'b-ok',
            self::Ditolak => 'b-bad',
            self::Nonaktif => 'b-mute',
        };
    }
}
