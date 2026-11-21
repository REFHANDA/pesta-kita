<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\StoreVenueRoomRequest;
use App\Http\Requests\Venue\UpdateVenueRoomRequest;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * BE-3: kelola ruangan venue milik sendiri. Ruangan yang sudah dipakai
 * pesanan tidak dapat dihapus (integritas riwayat BE-2);
 * ketersediaan harian ruangan ikut dibersihkan saat ruangan dihapus.
 */
class VenueRoomController extends Controller
{
    /**
     * Tambah ruangan pada venue milik sendiri.
     */
    public function store(StoreVenueRoomRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('manageRooms', $venue);

        $venue->rooms()->create($request->validated());

        return redirect()->back()->with('status', 'Ruangan berhasil ditambahkan.');
    }

    /**
     * Ubah ruangan milik sendiri.
     */
    public function update(UpdateVenueRoomRequest $request, VenueRoom $room): RedirectResponse
    {
        Gate::authorize('update', $room);

        $room->update($request->validated());

        return redirect()->back()->with('status', 'Ruangan berhasil diperbarui.');
    }

    /**
     * Hapus ruangan bila belum dipakai pesanan.
     */
    public function destroy(Request $request, VenueRoom $room): RedirectResponse
    {
        Gate::authorize('delete', $room);

        if ($room->orderItems()->exists()) {
            return redirect()->back()->withErrors([
                'room' => 'Ruangan sudah digunakan dalam pesanan dan tidak dapat dihapus.',
            ]);
        }

        // Ketersediaan harian melekat pada ruangan (morph, tanpa FK).
        $room->availabilityDays()->delete();
        $room->delete();

        return redirect()->back()->with('status', 'Ruangan berhasil dihapus.');
    }
}
