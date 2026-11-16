<?php

namespace App\Enums;

/**
 * Peran akun tunggal sesuai rancangan sistem 4 aktor Pesta Kita.
 * Nilai disimpan apa adanya pada kolom users.role.
 */
enum Role: string
{
    case Admin = 'admin';
    case Klien = 'klien';
    case Vendor = 'vendor';
    case Venue = 'venue';

    /**
     * Path portal tujuan redirect setelah login/register.
     * Ditentukan server dari role akun, bukan dari tab login (T1).
     */
    public function portalPath(): string
    {
        return match ($this) {
            self::Admin => '/admin',
            self::Klien => '/klien',
            self::Vendor => '/vendor',
            self::Venue => '/venue',
        };
    }

    /**
     * Label tampilan untuk halaman profil.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Klien => 'Klien',
            self::Vendor => 'Vendor',
            self::Venue => 'Pemilik Venue',
        };
    }
}
