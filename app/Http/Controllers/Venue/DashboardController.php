<?php

namespace App\Http\Controllers\Venue;

use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda Pemilik Venue: profil usaha, status verifikasi, ringkasan
 * venue/ruangan, dan pesanan masuk nyata dari database.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan usaha, venue, dan pesanan pemilik login.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->venueProfile;

        $venues = $profile ? $profile->venues()->withCount('rooms')->latest()->get() : collect();

        $tayang = $venues->filter(fn ($venue) => $venue->verification_status === VerificationStatus::Terverifikasi)->count();

        $orders = $profile ? OrderItem::where('provider_type', 'venue_profile')
            ->where('provider_id', $profile->id)
            ->with(['event', 'subject'])
            ->latest()
            ->get() : collect();

        $pesananMenunggu = $orders->filter(fn ($o) => $o->status === OrderItemStatus::MenungguReview)->count();
        $pesananAktif = $orders->filter(fn ($o) => in_array($o->status, [
            OrderItemStatus::Disetujui,
            OrderItemStatus::Terkonfirmasi,
        ], true))->count();

        return view('venue.index', [
            'profile' => $profile,
            'venues' => $venues,
            'orders' => $orders,
            'tayangCount' => $tayang,
            'ruanganCount' => (int) $venues->sum('rooms_count'),
            'pesananMenungguCount' => $pesananMenunggu,
            'pesananAktifCount' => $pesananAktif,
        ]);
    }
}
