<?php

namespace App\Http\Controllers\Provider;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tahap G1: halaman profil usaha Vendor dan Venue.
 * Satu controller memilih view sesuai peran dan meneruskan data rekening
 * pencairan (terverifikasi aktif, pengajuan menunggu, penolakan terakhir)
 * agar Blade tidak lagi melakukan query model sendiri.
 */
class ProfilePageController extends Controller
{
    /**
     * Tampilkan profil usaha sesuai peran akun login.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $user->providerProfile();

        abort_if($profile === null, 403);

        $accounts = $profile->bankAccounts();

        $verified = (clone $accounts)->where('verification_status', VerificationStatus::Terverifikasi->value)->latest('id')->first();
        $pending = (clone $accounts)->where('verification_status', VerificationStatus::Menunggu->value)->latest('id')->first();
        $rejected = (clone $accounts)->where('verification_status', VerificationStatus::Ditolak->value)->latest('id')->first();

        // H2: penolakan lama tidak ditampilkan bila sudah ada pengajuan baru
        // atau sudah ada rekening terverifikasi yang lebih baru.
        if ($pending !== null || $rejected === null || ($verified !== null && $rejected->id < $verified->id)) {
            $rejected = null;
        }

        return view($user->role->value.'.profil', [
            'profile' => $profile,
            'verifiedAccount' => $verified,
            'pendingAccount' => $pending,
            'rejectedAccount' => $rejected,
        ]);
    }
}
