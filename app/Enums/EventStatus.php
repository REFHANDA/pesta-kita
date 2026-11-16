<?php

namespace App\Enums;

/**
 * Status acara sesuai alur rancangan BACKEND_BUSINESS_RULES bagian C dan
 * halaman Klien yang sudah ada (draft, diajukan, menunggu pembayaran,
 * terkonfirmasi, selesai, dinilai, dibatalkan). BE-2 hanya menyimpan kolom
 * status; transisi dan aturannya menyusul pada BE-5.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Terkonfirmasi = 'terkonfirmasi';
    case Selesai = 'selesai';
    case Dinilai = 'dinilai';
    case Dibatalkan = 'dibatalkan';
}
