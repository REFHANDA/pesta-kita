<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;

/**
 * BE-3 (B14): otorisasi alur verifikasi bisnis penyedia.
 * Pengajuan/upload dokumen hanya oleh pemilik profil; peninjauan dan
 * keputusan (setujui/tolak) hanya oleh Admin. Status akun (T2) tidak
 * pernah disentuh policy ini.
 */
class ProviderProfilePolicy
{
    /**
     * Admin melihat antrean dan detail verifikasi.
     */
    public function review(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    /**
     * Keputusan setujui/tolak hanya Admin.
     */
    public function decide(User $user, VendorProfile|VenueProfile $profile): bool
    {
        return $user->role === Role::Admin;
    }

    /**
     * Ajukan verifikasi hanya oleh pemilik profil penyedia.
     */
    public function submit(User $user, VendorProfile|VenueProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    /**
     * Upload dokumen verifikasi hanya oleh pemilik profil penyedia.
     */
    public function uploadDocument(User $user, VendorProfile|VenueProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    /**
     * Kepemilikan profil sesuai role penyedia.
     */
    protected function owns(User $user, VendorProfile|VenueProfile $profile): bool
    {
        return in_array($user->role, [Role::Vendor, Role::Venue], true)
            && $profile->user_id === $user->id;
    }
}
