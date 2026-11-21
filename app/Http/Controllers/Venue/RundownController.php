<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\RundownService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rundown di portal Pemilik Venue (Tahap C3): hanya lihat, tanpa route tulis.
 * Halaman memakai "Acara #ID" dan tidak memuat nama/kontak klien.
 */
class RundownController extends Controller
{
    /**
     * Daftar acara yang memakai venue milik pengguna.
     */
    public function index(Request $request, RundownService $rundown): View
    {
        $events = $rundown->eventsForProvider($request->user())
            ->loadCount('rundownItems');

        return view('venue.rundown', [
            'events' => $events,
        ]);
    }

    /**
     * Rundown lengkap satu acara (hanya baca).
     */
    public function show(Request $request, Event $event, RundownService $rundown): View
    {
        abort_unless($rundown->canView($request->user(), $event), 403, 'Anda tidak memiliki akses ke rundown acara ini.');

        return view('venue.rundown-show', [
            'event' => $event,
            'rundownItems' => $rundown->items($event),
            'lockedMessage' => $rundown->isLocked($event) ? 'Rundown dikunci karena acara sudah selesai.' : null,
        ]);
    }
}
