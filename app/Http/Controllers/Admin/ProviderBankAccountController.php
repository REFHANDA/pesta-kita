<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProviderBankAccount;
use App\Notifications\ProviderBankAccountDecidedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tahap G1: keputusan Admin atas rekening pencairan penyedia.
 * Hanya baris `menunggu` yang dapat diputuskan; keputusan berjalan dalam
 * transaksi dengan lockForUpdate agar tidak ada efek ganda. Rekening
 * terverifikasi lama milik profil yang sama menjadi `nonaktif` sehingga
 * satu profil hanya punya satu rekening aktif, riwayat tetap tersimpan.
 */
class ProviderBankAccountController extends Controller
{
    /**
     * Setujui pengajuan rekening.
     */
    public function approve(Request $request, ProviderBankAccount $bankAccount): RedirectResponse
    {
        $token = (string) $request->input('token', '');
        $stale = false;

        $decided = DB::transaction(function () use ($request, $bankAccount, $token, &$stale): ?ProviderBankAccount {
            $locked = ProviderBankAccount::query()->whereKey($bankAccount->getKey())->lockForUpdate()->first();

            // H1: data yang disetujui harus sama dengan yang dilihat Admin.
            if ($locked === null || ! hash_equals($locked->reviewToken(), $token)) {
                $stale = true;

                return null;
            }

            if ($locked->verification_status !== VerificationStatus::Menunggu) {
                return null;
            }

            $locked->update([
                'verification_status' => VerificationStatus::Terverifikasi,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            // Satu rekening terverifikasi per profil; riwayat tetap ada.
            ProviderBankAccount::query()
                ->where('owner_type', $locked->owner_type)
                ->where('owner_id', $locked->owner_id)
                ->whereKeyNot($locked->getKey())
                ->where('verification_status', VerificationStatus::Terverifikasi->value)
                ->update(['verification_status' => VerificationStatus::Nonaktif->value]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'provider_bank_account_approved',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => VerificationStatus::Menunggu->value,
                'to_status' => VerificationStatus::Terverifikasi->value,
                // Nomor lengkap tidak boleh ada di detail log (aturan 6).
                'reason' => sprintf('Rekening %s %s a.n. %s disetujui.', $locked->bank_name, $locked->maskedNumber(), $locked->account_name),
                'ip' => $request->ip(),
            ]);

            return $locked;
        });

        if ($stale) {
            return $this->dataChanged($bankAccount);
        }

        if ($decided === null) {
            return $this->alreadyDecided();
        }

        $decided->owner?->user?->notify(new ProviderBankAccountDecidedNotification($decided, 'disetujui'));

        return $this->backToProvider($decided)
            ->with('status', 'Rekening pencairan penyedia disetujui. Dana dapat dicairkan ke rekening ini.');
    }

    /**
     * Tolak pengajuan rekening dengan alasan wajib.
     */
    public function reject(Request $request, ProviderBankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        $token = (string) $request->input('token', '');
        $stale = false;

        $decided = DB::transaction(function () use ($request, $bankAccount, $validated, $token, &$stale): ?ProviderBankAccount {
            $locked = ProviderBankAccount::query()->whereKey($bankAccount->getKey())->lockForUpdate()->first();

            // H1: data yang ditolak harus sama dengan yang dilihat Admin.
            if ($locked === null || ! hash_equals($locked->reviewToken(), $token)) {
                $stale = true;

                return null;
            }

            if ($locked->verification_status !== VerificationStatus::Menunggu) {
                return null;
            }

            $locked->update([
                'verification_status' => VerificationStatus::Ditolak,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['reason'],
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'provider_bank_account_rejected',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => VerificationStatus::Menunggu->value,
                'to_status' => VerificationStatus::Ditolak->value,
                'reason' => sprintf('Rekening %s %s ditolak: %s', $locked->bank_name, $locked->maskedNumber(), $validated['reason']),
                'ip' => $request->ip(),
            ]);

            return $locked;
        });

        if ($stale) {
            return $this->dataChanged($bankAccount);
        }

        if ($decided === null) {
            return $this->alreadyDecided();
        }

        $decided->owner?->user?->notify(new ProviderBankAccountDecidedNotification($decided, 'ditolak', $decided->rejection_reason));

        return $this->backToProvider($decided)
            ->with('status', 'Pengajuan rekening ditolak. Alasan telah disampaikan ke penyedia.');
    }

    /**
     * H1: data berubah sejak halaman dimuat — kembali ke detail penyedia
     * tanpa mengubah status apa pun.
     */
    protected function dataChanged(ProviderBankAccount $account): RedirectResponse
    {
        return redirect()->route('admin.penyedia.detail', [
            'type' => $account->owner_type,
            'id' => $account->owner_id,
        ])->withErrors([
            'bank_account' => 'Data rekening berubah sejak Anda membukanya. Periksa ulang sebelum memutuskan.',
        ]);
    }

    /**
     * Pesan jelas tanpa efek ganda bila baris sudah diputuskan.
     */
    protected function alreadyDecided(): RedirectResponse
    {
        return redirect()->back()->withErrors([
            'bank_account' => 'Hanya pengajuan rekening berstatus menunggu yang dapat diputuskan.',
        ]);
    }

    /**
     * Kembali ke detail penyedia pemilik rekening.
     */
    protected function backToProvider(ProviderBankAccount $account): RedirectResponse
    {
        return redirect()->route('admin.penyedia.detail', [
            'type' => $account->owner_type,
            'id' => $account->owner_id,
        ]);
    }
}
