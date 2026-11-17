<?php

namespace App\Http\Controllers\Venue;

use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreOrderObjectionRequest;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * List order items owned by the logged-in venue provider.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->venueProfile;
        abort_unless($profile !== null, 404);

        $orderItems = OrderItem::query()
            ->where('provider_type', 'venue_profile')
            ->where('provider_id', $profile->id)
            ->with(['event', 'subject', 'objections'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('venue.pesanan', ['orderItems' => $orderItems]);
    }

    /**
     * Show an order item owned by the logged-in venue provider.
     */
    public function show(Request $request, OrderItem $orderItem): View
    {
        $profile = $request->user()->venueProfile;
        abort_unless(
            $profile !== null
                && $orderItem->provider_type === 'venue_profile'
                && $orderItem->provider_id === $profile->id,
            404,
        );

        return view('venue.pesanan-detail', [
            'orderItem' => $orderItem->load(['event', 'subject', 'objections']),
        ]);
    }

    /**
     * File one objection while the item's 24-hour window is open.
     */
    public function storeObjection(StoreOrderObjectionRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $profile = $request->user()->venueProfile;
        abort_unless(
            $profile !== null
                && $orderItem->provider_type === 'venue_profile'
                && $orderItem->provider_id === $profile->id,
            404,
        );

        DB::transaction(function () use ($request, $orderItem): void {
            $lockedItem = OrderItem::query()->whereKey($orderItem->id)->lockForUpdate()->firstOrFail();
            if ($lockedItem->status !== OrderItemStatus::MasaKeberatan
                || $lockedItem->objection_deadline === null
                || $lockedItem->objection_deadline->isPast()) {
                throw ValidationException::withMessages([
                    'order_item' => 'Keberatan hanya dapat diajukan sebelum masa 24 jam berakhir.',
                ]);
            }
            if ($lockedItem->objections()->exists()) {
                throw ValidationException::withMessages([
                    'order_item' => 'Keberatan untuk item ini sudah pernah diajukan.',
                ]);
            }

            $lockedItem->objections()->create([
                'provider_user_id' => $request->user()->id,
                'reason' => $request->validated('reason'),
                'status' => 'diajukan',
            ]);
        });

        return redirect()->route('venue.pesanan.detail', $orderItem)
            ->with('status', 'Keberatan dikirim ke Admin untuk ditinjau.');
    }

    /**
     * List objections filed by the logged-in venue provider.
     */
    public function objections(Request $request): View
    {
        $objections = $request->user()->orderObjections()
            ->with(['orderItem.event', 'orderItem.subject'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('venue.keberatan', ['objections' => $objections]);
    }
}
