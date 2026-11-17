<?php

namespace App\Policies;

use App\Enums\CatalogItemStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\Venue;

/**
 * BE-3 (B12): satu pintu otorisasi baca/hapus media.
 * - Dokumen verifikasi: hanya pemilik dan Admin, selamanya privat.
 * - Foto: pemilik/Admin kapan saja; pihak lain hanya bila foto publik,
 *   penyedia terverifikasi, dan subjek masih tayang (item aktif / venue
 *   tidak dinonaktifkan Admin). Kelas media ditentukan kolom kind,
 *   bukan ekstensi file.
 */
class MediaPolicy
{
    /**
     * Bolehkah user membaca/mengunduh file media ini.
     */
    public function view(User $user, Media $media): bool
    {
        // Admin meninjau dokumen dan foto apa pun.
        if ($user->role === Role::Admin) {
            return true;
        }

        $profile = $media->providerProfile();

        // Pemilik penyedia: seluruh media miliknya, termasuk dokumen.
        if ($profile !== null && $profile->user_id === $user->id) {
            return true;
        }

        // Besides owners/Admin, only public photos and venue floor plans
        // are visible to clients after provider verification.
        if (! in_array($media->kind, [MediaKind::Foto, MediaKind::Denah], true)
            || $media->visibility !== MediaVisibility::Publik) {
            return false;
        }

        if ($profile === null || $profile->verification_status !== VerificationStatus::Terverifikasi) {
            return false;
        }

        // Subjek harus masih tayang untuk publik.
        $owner = $media->owner;

        return match (true) {
            $owner instanceof CatalogItem => $owner->status === CatalogItemStatus::Aktif,
            $owner instanceof Venue => $owner->verification_status === VerificationStatus::Terverifikasi
                && ($media->kind !== MediaKind::Denah || $user->role === Role::Klien),
            default => true, // foto profil penyedia terverifikasi
        };
    }

    /**
     * Hapus media: pemilik penyedia atau Admin. Klien tidak pernah boleh.
     */
    public function delete(User $user, Media $media): bool
    {
        if ($user->role === Role::Admin) {
            return true;
        }

        $profile = $media->providerProfile();

        return $profile !== null && $profile->user_id === $user->id;
    }
}
