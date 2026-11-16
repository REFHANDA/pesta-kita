<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\UpdateAvailabilityRequest;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3b: kalender ketersediaan ruangan venue (B11). Status manual
 * disimpan per ruangan per tanggal; kepemilikan ruangan selalu lewat
 * venue induk menuju profil pemilik login.
 */
class AvailabilityController extends Controller
{
    /**
     * Kalender ruangan milik venue pemilik login.
     */
    public function index(Request $request): View
    {
        $venues = $this->venues($request);
        $rooms = $venues->flatMap->rooms;

        return view('venue.kalender-ketersediaan', [
            'venues' => $venues,
            'selectedRoomId' => (int) $request->input('room', $rooms->first()?->id ?? 0),
        ]);
    }

    /**
     * Tandai/simpan status ketersediaan satu tanggal untuk ruangan milik sendiri.
     */
    public function update(UpdateAvailabilityRequest $request): RedirectResponse
    {
        $room = VenueRoom::with('venue.venueProfile')->findOrFail($request->validated('room_id'));

        abort_unless(
            $room->venue?->venueProfile?->user_id === $request->user()->id,
            403,
            'Ruangan ini bukan milik akun Anda.'
        );

        Gate::authorize('update', $room);

        $room->availabilityDays()->updateOrCreate(
            ['date' => $request->validated('date')],
            ['status' => $request->validated('status')]
        );

        return redirect()
            ->route('venue.kalender', ['room' => $room->id])
            ->with('status', 'Jadwal '.$request->validated('date').' tersimpan.');
    }

    /**
     * Venue milik pemilik login beserta ruangan dan jadwal aktifnya.
     *
     * @return Collection<int, Venue>
     */
    protected function venues(Request $request): Collection
    {
        $profile = $request->user()->venueProfile;

        if ($profile === null) {
            return collect();
        }

        return $profile->venues()->with([
            'rooms' => fn ($query) => $query->orderBy('name'),
            'rooms.availabilityDays' => fn ($query) => $query
                ->where('date', '>=', today()->toDateString())
                ->orderBy('date')
                ->limit(180),
        ])
            ->orderBy('name')
            ->get();
    }
}
