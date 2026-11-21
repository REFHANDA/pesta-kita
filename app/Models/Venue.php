<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Venue milik profil Pemilik Venue. B4: cleaning_fee opsional dan terpisah
 * dari rental_price. B5: deposit_amount nullable tanpa logika pembayaran.
 * Ketersediaan dikelola per ruangan (VenueRoom), bukan per venue.
 */
#[Fillable([
    'venue_profile_id',
    'name',
    'kind',
    'capacity',
    'address',
    'description',
    'rules',
    'cancellation_policy',
    'rental_price',
    'cleaning_fee',
    'duration_hours',
    'deposit_amount',
    'verification_status',
    'verified_at',
])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'capacity' => 'integer',
            'rental_price' => 'integer',
            'cleaning_fee' => 'integer',
            'duration_hours' => 'integer',
            'deposit_amount' => 'integer',
        ];
    }

    /**
     * Profil usaha Pemilik Venue pemilik venue.
     *
     * @return BelongsTo<VenueProfile, $this>
     */
    public function venueProfile(): BelongsTo
    {
        return $this->belongsTo(VenueProfile::class);
    }

    /**
     * Ruangan dalam venue; kalender memilih ruangan.
     *
     * @return HasMany<VenueRoom, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(VenueRoom::class);
    }

    /**
     * Fasilitas venue (checkbox form venue).
     *
     * @return HasMany<VenueFacility, $this>
     */
    public function facilities(): HasMany
    {
        return $this->hasMany(VenueFacility::class);
    }

    /**
     * Media venue.
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'owner');
    }

    /**
     * Pengajuan perubahan harga venue.
     *
     * @return MorphMany<PriceChangeRequest, $this>
     */
    public function priceChangeRequests(): MorphMany
    {
        return $this->morphMany(PriceChangeRequest::class, 'subject');
    }

    /**
     * Filter publik Klien (B14 + keputusan Gate 4B butir 3): venue mengikuti
     * verifikasi profil penyedia; verification_status venue dipakai Admin
     * untuk penonaktifan individual (nonaktif). Soft-deleted dikecualikan
     * oleh default scope.
     *
     * @param  Builder<Venue>  $query
     * @return Builder<Venue>
     */
    public function scopePublikUntukKlien(Builder $query): Builder
    {
        return $query->where('verification_status', VerificationStatus::Terverifikasi)
            ->whereHas('venueProfile', fn (Builder $q) => $q->where('verification_status', VerificationStatus::Terverifikasi));
    }
}
