<?php

namespace App\Services;

use App\Enums\DisbursementStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\Media;
use App\Models\User;
use App\Notifications\DisbursementPaidNotification;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Pencatatan transfer pencairan ke penyedia oleh Admin (Tahap B7).
 */
class DisbursementService
{
    public const MESSAGE_NO_BANK_ACCOUNT = 'Rekening penyedia belum tersedia';

    /**
     * @throws RuntimeException kode 409 bila sudah cair, 422 bila rekening belum tersedia.
     */
    public function recordTransfer(Disbursement $disbursement, User $admin, DateTimeInterface $paidAt, UploadedFile $proof, ?string $ip = null): Disbursement
    {
        $path = null;

        try {
            $updated = DB::transaction(function () use ($disbursement, $admin, $paidAt, $proof, $ip, &$path): Disbursement {
                /** @var Disbursement $locked */
                $locked = Disbursement::query()->whereKey($disbursement->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== DisbursementStatus::BelumCair) {
                    throw new RuntimeException('Pencairan ini sudah dicatat cair dan tidak dapat diubah.', 409);
                }

                $account = $locked->verifiedBankAccount();
                if (! $account) {
                    throw new RuntimeException(self::MESSAGE_NO_BANK_ACCOUNT, 422);
                }

                $path = $proof->store('disbursements', 'private');
                $media = Media::create([
                    'owner_type' => $locked->getMorphClass(),
                    'owner_id' => $locked->id,
                    'kind' => MediaKind::BuktiPencairan,
                    'doc_type' => null,
                    'path' => $path,
                    'disk' => 'private',
                    'mime_type' => $proof->getClientMimeType(),
                    'size' => $proof->getSize(),
                    'original_name' => $proof->getClientOriginalName(),
                    'visibility' => MediaVisibility::Privat,
                    'sort_order' => 0,
                ]);

                $locked->update([
                    'status' => DisbursementStatus::Cair,
                    'paid_at' => $paidAt,
                    'proof_media_id' => $media->id,
                    'recorded_by' => $admin->id,
                ]);

                AuditLog::create([
                    'actor_id' => $admin->id,
                    'action' => 'disbursement_paid',
                    'subject_type' => $locked->getMorphClass(),
                    'subject_id' => $locked->id,
                    'from_status' => DisbursementStatus::BelumCair->value,
                    'to_status' => DisbursementStatus::Cair->value,
                    'reason' => sprintf('Transfer %s Rp %s ke %s %s a.n. %s.', $locked->type->value, number_format($locked->amount, 0, ',', '.'), $account->bank_name, $account->account_number, $account->account_name),
                    'ip' => $ip,
                ]);

                return $locked;
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }

        $updated->provider?->user?->notify(new DisbursementPaidNotification($updated));

        return $updated;
    }
}
