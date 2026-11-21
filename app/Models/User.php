<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Profil Klien (1 ke 0..1 sesuai role).
     *
     * @return HasOne<ClientProfile, $this>
     */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * Profil usaha Vendor (1 ke 0..1 sesuai role).
     *
     * @return HasOne<VendorProfile, $this>
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * Profil usaha Pemilik Venue (1 ke 0..1 sesuai role).
     *
     * @return HasOne<VenueProfile, $this>
     */
    public function venueProfile(): HasOne
    {
        return $this->hasOne(VenueProfile::class);
    }

    /**
     * Profil usaha milik akun login sesuai role (BE-3).
     * Klien/Admin tidak memiliki profil penyedia -> null.
     */
    public function providerProfile(): VendorProfile|VenueProfile|null
    {
        return match ($this->role) {
            Role::Vendor => $this->vendorProfile,
            Role::Venue => $this->venueProfile,
            default => null,
        };
    }

    /**
     * Acara milik akun Klien (BE-2).
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'client_id');
    }

    /**
     * Pengajuan harga yang ditinjau akun Admin (BE-2).
     *
     * @return HasMany<PriceChangeRequest, $this>
     */
    public function reviewedPriceChanges(): HasMany
    {
        return $this->hasMany(PriceChangeRequest::class, 'reviewed_by');
    }

    /**
     * Keberatan yang diajukan akun penyedia (BE-2).
     *
     * @return HasMany<OrderObjection, $this>
     */
    public function orderObjections(): HasMany
    {
        return $this->hasMany(OrderObjection::class, 'provider_user_id');
    }

    /**
     * Jejak audit yang dilakukan akun ini (BE-2).
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }
}
