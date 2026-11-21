<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\DisbursementStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\OrderItem;
use App\Services\RatingSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda Vendor: profil usaha, status verifikasi, ringkasan katalog,
 * dan pesanan masuk nyata dari database.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan usaha, katalog, dan pesanan vendor login.
     */
    public function index(Request $request, RatingSummary $ratings): View
    {
        $profile = $request->user()->vendorProfile;

        $items = $profile ? $profile->catalogItems()->latest()->get() : collect();

        $layananAktif = $items->filter(fn ($item) => $item->kind === CatalogKind::Layanan
            && $item->status === CatalogItemStatus::Aktif)->count();

        $peralatanAktif = $items->filter(fn ($item) => $item->kind === CatalogKind::Peralatan
            && $item->status === CatalogItemStatus::Aktif)->count();

        $orders = $profile ? OrderItem::where('provider_type', 'vendor_profile')
            ->where('provider_id', $profile->id)
            ->with(['event', 'subject'])
            ->latest()
            ->get() : collect();

        $pesananMenunggu = $orders->filter(fn ($o) => $o->status === OrderItemStatus::MenungguReview)->count();
        $pesananAktif = $orders->filter(fn ($o) => in_array($o->status, [
            OrderItemStatus::Disetujui,
            OrderItemStatus::Terkonfirmasi,
        ], true))->count();

        return view('vendor.index', [
            'profile' => $profile,
            'items' => $items,
            'orders' => $orders,
            'layananAktifCount' => $layananAktif,
            'peralatanAktifCount' => $peralatanAktif,
            'pesananMenungguCount' => $pesananMenunggu,
            'pesananAktifCount' => $pesananAktif,
            // Tahap C5: rating milik sendiri tanpa nama klien.
            'rating' => $profile ? $ratings->forSubject('vendor', $profile->id) : ['average' => null, 'count' => 0],
            'recentRatings' => $profile ? $ratings->recentForProvider('vendor', $profile->id, 5) : collect(),
            // Tahap G1: peringatan kondisional bila rekening pencairan belum terverifikasi.
            'verifiedAccount' => $profile
                ? $profile->bankAccounts()
                    ->where('verification_status', VerificationStatus::Terverifikasi->value)
                    ->latest('id')
                    ->first()
                : null,
            'belumCairTotal' => $profile ? (int) Disbursement::where('provider_type', 'vendor_profile')
                ->where('provider_id', $profile->id)
                ->where('status', DisbursementStatus::BelumCair)
                ->sum('amount') : 0,
        ]);
    }
}
