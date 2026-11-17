<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\ProviderBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProviderBankAccountController extends Controller
{
    /**
     * Show payout accounts owned by the signed-in provider.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->providerProfile();
        abort_if($profile === null, 403);

        return view('portal.provider-bank-accounts', [
            'role' => $request->user()->role->value,
            'accounts' => $profile->bankAccounts()->latest()->get(),
        ]);
    }

    /**
     * Submit a new payout account for Admin approval.
     */
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile();
        abort_if($profile === null, 403);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'account_name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($profile, $data, $request): void {
            $account = $profile->bankAccounts()->create($data + [
                'verification_status' => VerificationStatus::Menunggu,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'provider_bank_account_submitted',
                'subject_type' => $account->getMorphClass(),
                'subject_id' => $account->id,
                'from_status' => null,
                'to_status' => VerificationStatus::Menunggu->value,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route($request->user()->role->value.'.bank-accounts')
            ->with('status', 'Rekening dikirim untuk persetujuan Admin. Rekening ini belum dapat digunakan untuk pencairan.');
    }

    /**
     * List payout accounts awaiting Admin review.
     */
    public function adminIndex(): View
    {
        return view('admin.provider-bank-accounts', [
            'accounts' => ProviderBankAccount::query()
                ->with('owner.user')
                ->where('verification_status', VerificationStatus::Menunggu)
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * Approve or reject a pending payout account.
     */
    public function decide(Request $request, ProviderBankAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'verification_note' => ['required_if:action,reject', 'nullable', 'string', 'max:2000'],
        ]);
        $status = $data['action'] === 'approve'
            ? VerificationStatus::Terverifikasi
            : VerificationStatus::Ditolak;

        DB::transaction(function () use ($account, $request, $data, $status): void {
            $lockedAccount = ProviderBankAccount::query()->lockForUpdate()->findOrFail($account->id);
            abort_unless($lockedAccount->verification_status === VerificationStatus::Menunggu, 404);

            $lockedAccount->update([
                'verification_status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'verification_note' => $data['verification_note'] ?? null,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => $data['action'] === 'approve'
                    ? 'provider_bank_account_approved'
                    : 'provider_bank_account_rejected',
                'subject_type' => $lockedAccount->getMorphClass(),
                'subject_id' => $lockedAccount->id,
                'from_status' => VerificationStatus::Menunggu->value,
                'to_status' => $status->value,
                'reason' => $data['verification_note'] ?? null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.provider-bank-accounts')
            ->with('status', 'Keputusan rekening payout berhasil disimpan.');
    }
}
