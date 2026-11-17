<?php

namespace App\Enums;

/**
 * Kelas media memiliki aturan akses berbeda; denah venue juga dibatasi
 * pada venue terverifikasi dan tetap disajikan melalui endpoint privat.
 */
enum MediaKind: string
{
    case Foto = 'foto';
    case DokumenVerifikasi = 'dokumen_verifikasi';
    case Denah = 'denah';
}
