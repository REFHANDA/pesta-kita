<?php

namespace App\Enums;

/**
 * Jenis item katalog (B15, disetujui 2026-11-16): layanan dan peralatan
 * memakai satu tabel catalog_items, tanpa entitas peralatan terpisah.
 */
enum CatalogKind: string
{
    case Layanan = 'layanan';
    case Peralatan = 'peralatan';
}
