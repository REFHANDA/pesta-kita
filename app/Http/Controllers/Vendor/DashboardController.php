<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda Vendor: profil usaha, status verifikasi, dan ringkasan katalog
 * nyata milik akun login. Pesanan, pencairan, rating, dan pengajuan harga
 * belum punya backend (BE-4/BE-6/BE-7/BE-3b); view memberi catatan jujur.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan usaha dan katalog vendor login.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->vendorProfile;

        $items = $profile->catalogItems()->latest()->get();

        $layananAktif = $items->filter(fn ($item) => $item->kind === CatalogKind::Layanan
            && $item->status === CatalogItemStatus::Aktif)->count();

        $peralatanAktif = $items->filter(fn ($item) => $item->kind === CatalogKind::Peralatan
            && $item->status === CatalogItemStatus::Aktif)->count();

        return view('vendor.index', [
            'profile' => $profile,
            'items' => $items,
            'layananAktifCount' => $layananAktif,
            'peralatanAktifCount' => $peralatanAktif,
        ]);
    }
}
