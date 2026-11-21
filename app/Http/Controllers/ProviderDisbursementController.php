<?php

namespace App\Http\Controllers;

use App\Enums\DisbursementStatus;
use App\Enums\VerificationStatus;
use App\Models\Disbursement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Pencairan" Vendor dan Venue (Tahap B8): hanya baca, terbatas pada
 * profil penyedia milik akun login, tanpa identitas klien.
 */
class ProviderDisbursementController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->providerProfile();
        $portal = $request->user()->role->value;

        $base = Disbursement::query()
            ->where('provider_type', $profile?->getMorphClass() ?? '-')
            ->where('provider_id', $profile?->id ?? 0);

        return view($portal.'.pencairan', [
            'portal' => $portal,
            // Tahap G1: peringatan kondisional bila rekening belum terverifikasi.
            'verifiedAccount' => $profile
                ? $profile->bankAccounts()
                    ->where('verification_status', VerificationStatus::Terverifikasi->value)
                    ->latest('id')
                    ->first()
                : null,
            'totalUnpaid' => (int) (clone $base)->where('status', DisbursementStatus::BelumCair)->sum('amount'),
            'totalPaid' => (int) (clone $base)->where('status', DisbursementStatus::Cair)->sum('amount'),
            'disbursements' => (clone $base)->with(['orderItem.subject', 'proof'])->latest('id')->paginate(20),
        ]);
    }
}
