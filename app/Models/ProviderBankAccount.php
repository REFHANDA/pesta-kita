<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\ProviderBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * Rekening bank penyedia (Vendor/Venue) via morph owner ke profil.
 * Nomor utuh hanya untuk Admin dan pemilik akun; tampilan lain memakai
 * penyamaran (aturan terkunci A7).
 *
 * Tahap G1: rekening diisi penyedia lalu diverifikasi Admin. Baris
 * terverifikasi tidak pernah diubah di tempat; perubahan membuat baris
 * `menunggu` baru sehingga riwayat dan rekening aktif tetap utuh.
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
    'rejection_reason',
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
     * Admin yang memutuskan pengajuan rekening (Tahap G1).
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Status rekening yang mewakili satu profil untuk daftar ringkas:
     * rekening terverifikasi diutamakan, lalu pengajuan menunggu, lalu
     * penolakan terakhir. Null berarti profil belum mengisi rekening.
     *
     * @param  Collection<int, self>  $accounts
     */
    public static function currentStatus(Collection $accounts): ?VerificationStatus
    {
        $priority = [
            VerificationStatus::Terverifikasi,
            VerificationStatus::Menunggu,
            VerificationStatus::Ditolak,
            VerificationStatus::Nonaktif,
        ];

        foreach ($priority as $status) {
            if ($accounts->contains(fn (self $account): bool => $account->verification_status === $status)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Sidik data rekening yang dilihat Admin saat memuat halaman detail.
     * Dipakai untuk mencegah persetujuan atas data yang sudah berubah.
     */
    public function reviewToken(): string
    {
        return hash('sha256', implode('|', [
            (string) $this->getKey(),
            (string) $this->bank_name,
            (string) $this->account_number,
            (string) $this->account_name,
            (string) $this->updated_at,
        ]));
    }

    /**
     * Nomor rekening tersamar untuk tampilan non-pemilik/non-Admin,
     * notifikasi, dan log audit: hanya empat digit terakhir.
     */
    public function maskedNumber(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->account_number) ?? '';

        return '•••• '.substr($digits, -4);
    }

    /**
     * Label status untuk UI.
     */
    public function statusLabel(): string
    {
        return match ($this->verification_status) {
            VerificationStatus::Terverifikasi => 'Terverifikasi',
            VerificationStatus::Ditolak => 'Ditolak',
            VerificationStatus::Nonaktif => 'Nonaktif',
            default => 'Menunggu verifikasi',
        };
    }
}
