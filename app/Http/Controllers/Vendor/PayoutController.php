<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    /**
     * List transfers recorded for this Vendor's order items.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->vendorProfile;
        abort_unless($profile !== null, 404);

        return view('vendor.pencairan', [
            'payouts' => Payout::query()
                ->whereHas('orderItem', fn ($query) => $query
                    ->where('provider_type', 'vendor_profile')
                    ->where('provider_id', $profile->id))
                ->with(['orderItem.event', 'orderItem.subject'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    /**
     * Show a transfer recorded for this Vendor's order item.
     */
    public function show(Request $request, Payout $payout): View
    {
        $profile = $request->user()->vendorProfile;
        abort_unless($profile !== null && $payout->orderItem()
            ->where('provider_type', 'vendor_profile')
            ->where('provider_id', $profile->id)
            ->exists(), 404);

        return view('vendor.pencairan-detail', [
            'payout' => $payout->load(['orderItem.event', 'orderItem.subject']),
        ]);
    }

    /**
     * Download a payout proof for the owning Vendor only.
     */
    public function proof(Request $request, Payout $payout): StreamedResponse
    {
        $profile = $request->user()->vendorProfile;
        abort_unless($profile !== null && $payout->orderItem()
            ->where('provider_type', 'vendor_profile')
            ->where('provider_id', $profile->id)
            ->exists(), 404);
        abort_unless(is_string($payout->proof_path)
            && Storage::disk($payout->proof_disk)->exists($payout->proof_path), 404);

        return Storage::disk($payout->proof_disk)->download($payout->proof_path);
    }
}
