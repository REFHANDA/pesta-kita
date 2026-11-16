<?php

namespace App\Http\Controllers\Venue;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda Pemilik Venue: profil usaha, status verifikasi, dan ringkasan
 * venue/ruangan nyata milik akun login. Pesanan, pencairan, rating, dan
 * pengajuan harga belum punya backend (BE-4/BE-6/BE-7/BE-3b); view memberi
 * catatan jujur.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan usaha dan venue pemilik login.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->venueProfile;

        $venues = $profile->venues()->withCount('rooms')->latest()->get();

        $tayang = $venues->filter(fn ($venue) => $venue->verification_status === VerificationStatus::Terverifikasi)->count();

        return view('venue.index', [
            'profile' => $profile,
            'venues' => $venues,
            'tayangCount' => $tayang,
            'ruanganCount' => (int) $venues->sum('rooms_count'),
        ]);
    }
}
