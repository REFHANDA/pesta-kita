<?php

namespace App\Enums;

/**
 * Kelas media (B12, disetujui Gate 4A): foto dan dokumen verifikasi
 * memiliki aturan akses berbeda. Foto dapat menjadi publik setelah
 * verifikasi bisnis; dokumen verifikasi privat selamanya. Pembeda
 * adalah kolom ini, bukan ekstensi file.
 */
enum MediaKind: string
{
    case Foto = 'foto';
    case DokumenVerifikasi = 'dokumen_verifikasi';
}
