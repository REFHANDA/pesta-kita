<?php

namespace App\Http\Controllers\Provider;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderBankAccountRequest;
use App\Models\AuditLog;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Notifications\ProviderBankAccountSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Tahap G1: pengajuan rekening pencairan oleh penyedia (Vendor/Venue).
 * Admin tidak pernah mengisi rekening atas nama penyedia; Admin hanya
 * memverifikasi. Satu endpoint dipakai kedua peran karena bentuk datanya
 * sama — peran dan kepemilikan profil diambil dari akun login.
 */
class BankAccountController extends Controller
{
    /**
     * Simpan pengajuan rekening: perbarui baris `menunggu` yang ada atau
     * buat baris baru. Baris `terverifikasi` tidak pernah diubah di tempat.
     */
    public function store(StoreProviderBankAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->providerProfile();

        abort_if($profile === null, 403);

        $data = $request->validated();

        $account = DB::transaction(function () use ($profile, $data): ProviderBankAccount {
            $pending = $profile->bankAccounts()
                ->where('verification_status', VerificationStatus::Menunggu->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($pending !== null) {
                // Jangan menumpuk: pengajuan ulang memperbarui baris menunggu.
                $pending->update($data);

                return $pending;
            }

            return $profile->bankAccounts()->create($data + [
                'verification_status' => VerificationStatus::Menunggu,
            ]);
        });

        AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'provider_bank_account_submitted',
            'subject_type' => $account->getMorphClass(),
            'subject_id' => $account->id,
            'from_status' => null,
            'to_status' => VerificationStatus::Menunggu->value,
            // Aturan A7/6: nomor lengkap tidak pernah masuk log.
            'reason' => sprintf('Pengajuan rekening %s %s a.n. %s.', $account->bank_name, $account->maskedNumber(), $account->account_name),
            'ip' => $request->ip(),
        ]);

        User::query()
            ->where('role', Role::Admin)
            ->get()
            ->each(fn (User $admin) => $admin->notify(new ProviderBankAccountSubmittedNotification($account)));

        return redirect()->to($user->role->portalPath().'/profil')
            ->with('status', 'Rekening pencairan diajukan. Admin akan memverifikasi sebelum dana dapat dicairkan.');
    }
}
