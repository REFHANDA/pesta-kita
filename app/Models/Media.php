<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationDocType;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Media (foto/dokumen) milik profil, katalog, atau venue via morph owner.
 * B12: kind membedakan foto (calon publik setelah verifikasi) dan dokumen
 * verifikasi (privat selamanya). Path disk private (serve=false), bukan URL
 * publik; satu-satunya jalur baca adalah controller berotorisasi.
 */
#[Fillable(['owner_type', 'owner_id', 'kind', 'doc_type', 'path', 'disk', 'mime_type', 'size', 'original_name', 'visibility', 'sort_order'])]
#[Table('media')]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'doc_type' => VerificationDocType::class,
            'visibility' => MediaVisibility::class,
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Pemilik media (profil/katalog/venue).
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hanya foto (B12): calon publik setelah verifikasi bisnis.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function scopeFoto(Builder $query): Builder
    {
        return $query->where('kind', MediaKind::Foto);
    }

    /**
     * Hanya dokumen verifikasi (B14): privat selamanya.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function scopeDokumenVerifikasi(Builder $query): Builder
    {
        return $query->where('kind', MediaKind::DokumenVerifikasi);
    }

    /**
     * Venue floor plans are served privately after authorization.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function scopeDenah(Builder $query): Builder
    {
        return $query->where('kind', MediaKind::Denah);
    }

    /**
     * Profil penyedia yang menaungi media ini, untuk cek verifikasi/ownership.
     * catalog_item -> vendorProfile; venue -> venueProfile; profil -> dirinya.
     * Owner soft-deleted/tidak ada menghasilkan null (akses publik ditolak).
     */
    public function providerProfile(): VendorProfile|VenueProfile|null
    {
        $owner = $this->owner;

        return match (true) {
            $owner instanceof VendorProfile, $owner instanceof VenueProfile => $owner,
            $owner instanceof CatalogItem => $owner->vendorProfile,
            $owner instanceof Venue => $owner->venueProfile,
            default => null,
        };
    }
}
