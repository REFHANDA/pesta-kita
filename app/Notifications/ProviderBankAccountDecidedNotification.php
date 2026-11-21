<?php

namespace App\Notifications;

use App\Models\ProviderBankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tahap G1: keputusan Admin atas rekening pencairan penyedia (disetujui atau
 * ditolak). Alasan penolakan ditampilkan; nomor rekening tetap tersamar.
 */
class ProviderBankAccountDecidedNotification extends Notification
{
    use Queueable;

    /**
     * @param  'disetujui'|'ditolak'  $decision
     */
    public function __construct(
        public ProviderBankAccount $bankAccount,
        public string $decision,
        public ?string $reason = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $masked = $this->bankAccount->bank_name.' '.$this->bankAccount->maskedNumber();
        $portal = $this->bankAccount->owner_type === 'venue_profile' ? 'venue' : 'vendor';

        $message = $this->decision === 'disetujui'
            ? "Rekening pencairan {$masked} telah diverifikasi Admin. Dana hak Anda dapat dicairkan ke rekening ini."
            : "Rekening pencairan {$masked} ditolak Admin. Alasan: {$this->reason}. Silakan ajukan rekening yang benar.";

        return [
            'provider_bank_account_id' => $this->bankAccount->id,
            'decision' => $this->decision,
            'reason' => $this->reason,
            'message' => $message,
            'url' => route($portal.'.profil'),
        ];
    }
}
