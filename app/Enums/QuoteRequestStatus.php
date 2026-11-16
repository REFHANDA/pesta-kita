<?php

namespace App\Enums;

/**
 * Status permintaan penawaran (BACKEND_BUSINESS_RULES bagian C):
 * diajukan -> diusulkan_penyedia -> disetujui_admin atau ditolak_admin.
 * Harga usulan tidak menjadi harga final sebelum keputusan Admin.
 */
enum QuoteRequestStatus: string
{
    case Diajukan = 'diajukan';
    case DiusulkanPenyedia = 'diusulkan_penyedia';
    case DisetujuiAdmin = 'disetujui_admin';
    case DitolakAdmin = 'ditolak_admin';
}
