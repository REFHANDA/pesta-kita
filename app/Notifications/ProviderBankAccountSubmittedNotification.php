<?php

namespace App\Notifications;

use App\Models\ProviderBankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tahap G1: pemberitahuan ke seluruh Admin bahwa penyedia mengajukan atau
 * mengubah rekening pencairan. Payload tanpa nomor rekening lengkap.
 */
class ProviderBankAccountSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProviderBankAccount $bankAccount,
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
        $owner = $this->bankAccount->owner;
        $business = $owner?->business_name ?? 'Penyedia';
        $ownerType = $this->bankAccount->owner_type;

        return [
            'provider_bank_account_id' => $this->bankAccount->id,
            'owner_type' => $ownerType,
            'owner_id' => $this->bankAccount->owner_id,
            'message' => "{$business} mengajukan rekening pencairan {$this->bankAccount->bank_name} {$this->bankAccount->maskedNumber()} untuk diverifikasi.",
            'url' => in_array($ownerType, ['vendor_profile', 'venue_profile'], true)
                ? route('admin.penyedia.detail', ['type' => $ownerType, 'id' => $this->bankAccount->owner_id])
                : route('admin.penyedia.index', ['rekening' => 'menunggu']),
        ];
    }
}
