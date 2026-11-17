<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\ProviderBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekening bank penyedia (Vendor/Venue) via morph owner ke profil.
 * Nomor utuh hanya untuk Admin dan pemilik akun; tampilan lain memakai
 * penyamaran (aturan terkunci A7).
 */
#[Fillable([
    'owner_type',
    'owner_id',
    'bank_name',
    'account_number',
    'account_name',
    'verification_status',
    'reviewed_by',
    'reviewed_at',
    'verification_note',
])]
class ProviderBankAccount extends Model
{
    /** @use HasFactory<ProviderBankAccountFactory> */
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
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Profil penyedia pemilik rekening (vendor_profile/venue_profile).
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Admin who reviewed this payout account.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
