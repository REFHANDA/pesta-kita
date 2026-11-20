<?php

namespace App\Policies;

use App\Enums\CatalogItemStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;

/**
 * BE-3 (B12): satu pintu otorisasi baca/hapus media.
 * - Dokumen verifikasi: hanya pemilik dan Admin, selamanya privat.
 * - Foto: pemilik/Admin kapan saja; pihak lain hanya bila foto publik,
 *   penyedia terverifikasi, dan subjek masih tayang (item aktif / venue
 *   tidak dinonaktifkan Admin). Kelas media ditentukan kolom kind,
 *   bukan ekstensi file.
 * - Bukti pembayaran: hanya pemilik acara (klien) dan Admin.
 */
class MediaPolicy
{
    /**
     * Bolehkah user membaca/mengunduh file media ini.
     */
    public function view(User $user, Media $media): bool
    {
        // Admin meninjau dokumen, foto, atau bukti pembayaran apa pun.
        if ($user->role === Role::Admin) {
            return true;
        }

        // Bukti pembayaran: klien pemilik acara terkait.
        if ($media->kind === MediaKind::BuktiPembayaran) {
            $payment = $media->owner;
            if ($payment instanceof Payment) {
                return $payment->invoice?->event?->client_id === $user->id;
            }

            return false;
        }

        $profile = $media->providerProfile();

        // Pemilik penyedia: seluruh media miliknya, termasuk dokumen.
        if ($profile !== null && $profile->user_id === $user->id) {
            return true;
        }

        // Selain pemilik/Admin hanya foto yang sudah dipublikasikan.
        if ($media->kind !== MediaKind::Foto || $media->visibility !== MediaVisibility::Publik) {
            return false;
        }

        if ($profile === null || $profile->verification_status !== VerificationStatus::Terverifikasi) {
            return false;
        }

        // Subjek harus masih tayang untuk publik.
        $owner = $media->owner;

        return match (true) {
            $owner instanceof CatalogItem => $owner->status === CatalogItemStatus::Aktif,
            $owner instanceof Venue => $owner->verification_status === VerificationStatus::Terverifikasi,
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
