<?php

namespace App\Http\Controllers\Venue;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\StoreVenueRequest;
use App\Http\Requests\Venue\UpdateVenueRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3: CRUD venue milik Pemilik Venue login. Venue baru mengikuti
 * verifikasi profil (menunggu sampai Admin approve, keputusan Gate 4B
 * butir 3). cleaning_fee hanya Admin (butir 8); deposit opsional (B5).
 * Hapus = soft delete; foto dikelola MediaController.
 */
class VenueController extends Controller
{
    /**
     * Daftar venue milik pemilik login (termasuk yang belum tayang).
     */
    public function data(Request $request): View
    {
        $venues = $request->user()->venueProfile->venues()
            ->withCount('rooms')
            ->latest()
            ->get();

        return view('venue.data', ['venues' => $venues]);
    }

    /**
     * Form tambah venue.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Venue::class);

        return view('venue.form', ['venue' => null]);
    }

    /**
     * Form ubah venue milik sendiri.
     */
    public function edit(Request $request, Venue $venue): View
    {
        Gate::authorize('update', $venue);

        return view('venue.form', ['venue' => $venue->load('rooms', 'facilities')]);
    }

    /**
     * Simpan venue + ruangan pertama + fasilitas dalam satu transaksi.
     */
    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Gate::authorize('create', Venue::class);

        $data = $request->validated();

        $venue = DB::transaction(function () use ($request, $data): Venue {
            // Keputusan Gate 4B butir 3: venue baru mengikuti status
            // verifikasi profil penyedia saat dibuat.
            $profile = $request->user()->venueProfile;
            $verified = $profile->verification_status === VerificationStatus::Terverifikasi;

            $venue = $profile->venues()->create(
                collect($data)->except(['rooms', 'facilities'])->all()
                + [
                    'verification_status' => $verified ? VerificationStatus::Terverifikasi : VerificationStatus::Menunggu,
                    'verified_at' => $verified ? now() : null,
                ]
            );

            $venue->rooms()->createMany($data['rooms']);
            $venue->facilities()->createMany(
                collect($data['facilities'] ?? [])->map(fn (string $name) => ['name' => $name])->all()
            );

            return $venue;
        });

        return redirect()->route('venue.data')->with('status', 'Venue berhasil ditambahkan.');
    }

    /**
     * Perbarui venue milik sendiri; fasilitas disinkronkan.
     * cleaning_fee/verification_status tidak pernah dari input pemilik.
     */
    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('update', $venue);

        $data = $request->validated();

        DB::transaction(function () use ($venue, $data): void {
            $venue->update(collect($data)->except(['facilities'])->all());

            $venue->facilities()->delete();
            $venue->facilities()->createMany(
                collect($data['facilities'] ?? [])->map(fn (string $name) => ['name' => $name])->all()
            );
        });

        return redirect()->back()->with('status', 'Perubahan venue berhasil disimpan.');
    }
}
