<?php

namespace App\Http\Controllers\Venue;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RundownController extends Controller
{
    /**
     * Show shared rundown items for confirmed events with this Venue's services.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->venueProfile;
        abort_unless($profile !== null, 404);

        return view('venue.rundown', [
            'events' => Event::query()
                ->whereIn('status', [EventStatus::Terkonfirmasi->value, EventStatus::Selesai->value, EventStatus::Dinilai->value])
                ->whereHas('orderItems', fn ($query) => $query
                    ->where('provider_type', 'venue_profile')
                    ->where('provider_id', $profile->id)
                    ->where('status', OrderItemStatus::Mengikat->value))
                ->with(['rundownItems.orderItem.subject', 'orderItems.subject'])
                ->orderBy('event_date')
                ->paginate(20),
        ]);
    }
}
