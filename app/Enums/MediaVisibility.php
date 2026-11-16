<?php

namespace App\Enums;

/**
 * Visibilitas media: privat sampai verifikasi, publik setelahnya.
 * Profil Klien hanya membaca media publik (aturan kontak/verifikasi).
 */
enum MediaVisibility: string
{
    case Privat = 'privat';
    case Publik = 'publik';
}
