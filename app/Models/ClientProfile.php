<?php

namespace App\Models;

use Database\Factories\ClientProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id'])]
class ClientProfile extends Model
{
    /** @use HasFactory<ClientProfileFactory> */
    use HasFactory;

    /**
     * Akun Klien pemilik profil.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
