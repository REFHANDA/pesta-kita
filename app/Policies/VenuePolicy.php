<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Venue;

/**
 * BE-3: otorisasi venue milik Pemilik Venue.
 * Hanya pemilik yang boleh mengubah/menghapus/mengunggah foto; Admin
 * dapat melihat dan mengubah cleaning_fee/status lewat endpoint Admin
 * tersendiri (keputusan Gate 4B butir 3 dan 8).
 */
class VenuePolicy
{
    /**
     * Pemilik venue atau Admin (peninjauan).
     */
    public function view(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue) || $user->role === Role::Admin;
    }

    /**
     * Pemilik Venue login boleh membuat venue untuk profilnya sendiri.
     */
    public function create(User $user): bool
    {
        return $user->role === Role::Venue && $user->venueProfile !== null;
    }

    public function update(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    /**
     * Kelola ruangan/fasilitas hanya oleh pemilik.
     */
    public function manageRooms(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    /**
     * Unggah/hapus foto venue hanya oleh pemilik.
     */
    public function managePhotos(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    /**
     * Hanya Admin: biaya kebersihan (B4) dan status venue individual.
     */
    public function adminManage(User $user, Venue $venue): bool
    {
        return $user->role === Role::Admin;
    }

    /**
     * Kepemilikan: role venue dan venue berada di bawah profil miliknya.
     */
    protected function owns(User $user, Venue $venue): bool
    {
        return $user->role === Role::Venue
            && $venue->venueProfile !== null
            && $venue->venueProfile->user_id === $user->id;
    }
}
