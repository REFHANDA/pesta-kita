<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\VenueProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id',
    'business_name',
    'category',
    'address',
    'pic_name',
    'service_area',
    'description',
    'verification_status',
    'verified_at',
    'verification_note',
    'reviewed_by',
    'verification_submitted_at',
])]
class VenueProfile extends Model
{
    /** @use HasFactory<VenueProfileFactory> */
    use HasFactory;

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
            'verification_submitted_at' => 'datetime',
        ];
    }

    /**
     * Akun Pemilik Venue pemilik profil usaha.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin peninjau verifikasi terakhir (B14).
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Venue milik usaha ini (BE-2).
     *
     * @return HasMany<Venue, $this>
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * Rekening bank pencairan milik profil ini (BE-2).
     *
     * @return MorphMany<ProviderBankAccount, $this>
     */
    public function bankAccounts(): MorphMany
    {
        return $this->morphMany(ProviderBankAccount::class, 'owner');
    }

    /**
     * Media profil/portofolio usaha (BE-2).
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'owner');
    }
}
