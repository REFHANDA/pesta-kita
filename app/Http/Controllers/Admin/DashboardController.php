<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Payment;
use App\Models\PriceChangeRequest;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Services\RatingSummary;
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
    public function index(RatingSummary $ratings): View
    {
        $menungguVerifikasi = VendorProfile::where('verification_status', VerificationStatus::Menunggu)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Menunggu)->count();

        $terverifikasi = VendorProfile::where('verification_status', VerificationStatus::Terverifikasi)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Terverifikasi)->count();

        // Tahap G1: antrean verifikasi rekening pencairan penyedia.
        $rekeningMenunggu = ProviderBankAccount::where('verification_status', VerificationStatus::Menunggu)->count();

        $pesananMenungguReview = OrderItem::where('status', OrderItemStatus::MenungguReview)->count();

        $keberatanTerbuka = OrderObjection::whereIn('status', [
            OrderObjectionStatus::Diajukan,
            OrderObjectionStatus::Ditinjau,
        ])->count();

        $pengajuanHargaMenunggu = PriceChangeRequest::where('status', 'menunggu')->count();

        $tagihanMenungguValidasi = Payment::where('status', PaymentStatus::MenungguValidasi)->count();

        $tagihanLunasAktif = Invoice::where('status', InvoiceStatus::Lunas)
            ->whereHas('event', fn ($query) => $query->where('status', '!=', EventStatus::Dibatalkan));
        $totalKomisi = (int) (clone $tagihanLunasAktif)->sum('total_commission');
        $totalKebersihan = (int) (clone $tagihanLunasAktif)->sum('cleaning_fee');
        $totalPendapatanPembatalan = (int) Cancellation::where('status', CancellationStatus::RefundSelesai)->sum('company_income');
        $totalPendapatanPerusahaan = $totalKomisi + $totalKebersihan + $totalPendapatanPembatalan;

        $pembatalanPerluDitinjau = Cancellation::whereIn('status', [CancellationStatus::Diajukan, CancellationStatus::Ditinjau])->count();
        $pencairanBelumCair = Disbursement::where('status', DisbursementStatus::BelumCair);
        $pencairanBelumCairCount = (clone $pencairanBelumCair)->count();
        $pencairanBelumCairTotal = (int) (clone $pencairanBelumCair)->sum('amount');

        $perluTindakanTotal = $menungguVerifikasi
            + $rekeningMenunggu
            + $pesananMenungguReview
            + $keberatanTerbuka
            + $pengajuanHargaMenunggu
            + $tagihanMenungguValidasi
            + $pembatalanPerluDitinjau
            + $pencairanBelumCairCount;

        return view('admin.index', [
            'klienCount' => User::where('role', Role::Klien)->count(),
            'penyediaTerverifikasiCount' => $terverifikasi,
            'acaraCount' => Event::count(),
            'verifikasiMenungguCount' => $menungguVerifikasi,
            'rekeningMenungguCount' => $rekeningMenunggu,
            'pesananMenungguReviewCount' => $pesananMenungguReview,
            'keberatanTerbukaCount' => $keberatanTerbuka,
            'pengajuanHargaMenungguCount' => $pengajuanHargaMenunggu,
            'tagihanMenungguValidasiCount' => $tagihanMenungguValidasi,
            'perluTindakanTotal' => $perluTindakanTotal,
            'totalPendapatanPerusahaan' => $totalPendapatanPerusahaan,
            'totalKomisi' => $totalKomisi,
            'totalKebersihan' => $totalKebersihan,
            'totalPendapatanPembatalan' => $totalPendapatanPembatalan,
            'pembatalanPerluDitinjauCount' => $pembatalanPerluDitinjau,
            'pencairanBelumCairCount' => $pencairanBelumCairCount,
            'pencairanBelumCairTotal' => $pencairanBelumCairTotal,
            // Tahap C5: rating Admin dari data nyata.
            'ratingAdmin' => $ratings->forAdmin(),
        ]);
    }
}
