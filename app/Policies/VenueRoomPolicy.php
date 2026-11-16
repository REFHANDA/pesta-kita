<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\VenueRoom;

/**
 * BE-3: otorisasi ruangan venue — mengikuti kepemilikan venue induk.
 */
class VenueRoomPolicy
{
    public function update(User $user, VenueRoom $room): bool
    {
        return $this->owns($user, $room);
    }

    public function delete(User $user, VenueRoom $room): bool
    {
        return $this->owns($user, $room);
    }

    /**
     * Kepemilikan lewat venue induk -> profil pemilik venue.
     */
    protected function owns(User $user, VenueRoom $room): bool
    {
        return $user->role === Role::Venue
            && $room->venue !== null
            && $room->venue->venueProfile !== null
            && $room->venue->venueProfile->user_id === $user->id;
    }
}
