<?php

namespace App\Enums;

/**
 * Jenis dokumen verifikasi mitra (B14, disetujui Gate 4A).
 * Vendor: ktp wajib, nib bila tersedia, npwp opsional.
 * Venue: ktp wajib, bukti_venue wajib, npwp opsional.
 * Bukti rekening bukan persyaratan verifikasi (keputusan Gate 4B).
 */
enum VerificationDocType: string
{
    case Ktp = 'ktp';
    case Nib = 'nib';
    case Npwp = 'npwp';
    case BuktiVenue = 'bukti_venue';

    /**
     * Label tampilan untuk UI penyedia/Admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP pemilik/PIC',
            self::Nib => 'NIB / legalitas usaha',
            self::Npwp => 'NPWP',
            self::BuktiVenue => 'Bukti kepemilikan / hak pengelolaan venue',
        };
    }
}
