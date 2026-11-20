<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Payment;
use App\Models\PriceChangeRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use Illuminate\View\View;

/**
 * Beranda Admin: ringkasan operasional nyata dari database (pengguna, penyedia,
 * acara, antrean verifikasi, review pesanan, keberatan, pengajuan harga, dan pembayaran).
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan operasional dan antrean tindakan.
     */
    public function index(): View
    {
        $menungguVerifikasi = VendorProfile::where('verification_status', VerificationStatus::Menunggu)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Menunggu)->count();

        $terverifikasi = VendorProfile::where('verification_status', VerificationStatus::Terverifikasi)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Terverifikasi)->count();

        $pesananMenungguReview = OrderItem::where('status', OrderItemStatus::MenungguReview)->count();

        $keberatanTerbuka = OrderObjection::whereIn('status', [
            OrderObjectionStatus::Diajukan,
            OrderObjectionStatus::Ditinjau,
        ])->count();

        $pengajuanHargaMenunggu = PriceChangeRequest::where('status', 'menunggu')->count();

        $tagihanMenungguValidasi = Payment::where('status', PaymentStatus::MenungguValidasi)->count();

        $totalKomisi = (int) Invoice::where('status', InvoiceStatus::Lunas)->sum('total_commission');
        $totalKebersihan = (int) Invoice::where('status', InvoiceStatus::Lunas)->sum('cleaning_fee');
        $totalPendapatanPerusahaan = $totalKomisi + $totalKebersihan;

        $perluTindakanTotal = $menungguVerifikasi
            + $pesananMenungguReview
            + $keberatanTerbuka
            + $pengajuanHargaMenunggu
            + $tagihanMenungguValidasi;

        return view('admin.index', [
            'klienCount' => User::where('role', Role::Klien)->count(),
            'penyediaTerverifikasiCount' => $terverifikasi,
            'acaraCount' => Event::count(),
            'verifikasiMenungguCount' => $menungguVerifikasi,
            'pesananMenungguReviewCount' => $pesananMenungguReview,
            'keberatanTerbukaCount' => $keberatanTerbuka,
            'pengajuanHargaMenungguCount' => $pengajuanHargaMenunggu,
            'tagihanMenungguValidasiCount' => $tagihanMenungguValidasi,
            'perluTindakanTotal' => $perluTindakanTotal,
            'totalPendapatanPerusahaan' => $totalPendapatanPerusahaan,
            'totalKomisi' => $totalKomisi,
            'totalKebersihan' => $totalKebersihan,
        ]);
    }
}
