<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use Illuminate\View\View;

/**
 * Beranda Admin: ringkasan operasional nyata (pengguna, penyedia, acara,
 * antrean verifikasi). Antrean transaksi, rekap pendapatan, dan rating
 * internal belum diagregasi pada dashboard; workflow terkait tersedia
 * melalui halaman masing-masing.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan operasional dan antrean verifikasi mitra.
     */
    public function index(): View
    {
        $menunggu = VendorProfile::where('verification_status', VerificationStatus::Menunggu)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Menunggu)->count();

        $terverifikasi = VendorProfile::where('verification_status', VerificationStatus::Terverifikasi)->count()
            + VenueProfile::where('verification_status', VerificationStatus::Terverifikasi)->count();

        return view('admin.index', [
            'klienCount' => User::where('role', Role::Klien)->count(),
            'penyediaTerverifikasiCount' => $terverifikasi,
            'acaraCount' => Event::count(),
            'verifikasiMenungguCount' => $menunggu,
        ]);
    }
}
