<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\User;

/**
 * BE-3: otorisasi item katalog vendor.
 * Hanya vendor pemilik yang boleh mengubah/menghapus/mengunggah foto;
 * Admin dapat melihat untuk peninjauan. Akses publik Klien tidak lewat
 * policy ini melainkan scope publikUntukKlien + endpoint terpisah.
 */
class CatalogItemPolicy
{
    /**
     * Pemilik item atau Admin (peninjauan).
     */
    public function view(User $user, CatalogItem $item): bool
    {
        return $this->owns($user, $item) || $user->role === Role::Admin;
    }

    /**
     * Vendor login boleh membuat item untuk profilnya sendiri.
     */
    public function create(User $user): bool
    {
        return $user->role === Role::Vendor && $user->vendorProfile !== null;
    }

    public function update(User $user, CatalogItem $item): bool
    {
        return $this->owns($user, $item);
    }

    /**
     * Ubah status aktif/nonaktif hanya oleh pemilik.
     */
    public function toggleStatus(User $user, CatalogItem $item): bool
    {
        return $this->owns($user, $item);
    }

    /**
     * Unggah/hapus foto item hanya oleh pemilik.
     */
    public function managePhotos(User $user, CatalogItem $item): bool
    {
        return $this->owns($user, $item);
    }

    /**
     * Kepemilikan: role vendor dan item berada di bawah profil miliknya.
     */
    protected function owns(User $user, CatalogItem $item): bool
    {
        return $user->role === Role::Vendor
            && $item->vendorProfile !== null
            && $item->vendorProfile->user_id === $user->id;
    }
}
