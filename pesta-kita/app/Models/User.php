<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = ['role' => 'client'];

    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(CatalogItem::class);
    }

    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

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
        ];
    }
}
