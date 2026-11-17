<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class RundownController extends Controller
{
    /**
     * Monitor rundown segments for confirmed and completed events.
     */
    public function index(): View
    {
        return view('admin.rundown', [
            'events' => Event::query()
                ->whereIn('status', [EventStatus::Terkonfirmasi->value, EventStatus::Selesai->value, EventStatus::Dinilai->value])
                ->with(['rundownItems.orderItem.subject', 'orderItems.subject'])
                ->orderBy('event_date')
                ->paginate(20),
        ]);
    }
}
