<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyBankAccountRequest;
use App\Http\Requests\Admin\UpdateCompanyBankAccountRequest;
use App\Models\AuditLog;
use App\Models\CompanyBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyBankAccountController extends Controller
{
    /**
     * List company accounts without modifying payment snapshots.
     */
    public function index(): View
    {
        return view('admin.rekening', [
            'bankAccounts' => CompanyBankAccount::query()->orderByDesc('is_active')->orderBy('bank_name')->get(),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.rekening-form', ['bankAccount' => null]);
    }

    /**
     * Show the edit form.
     */
    public function edit(CompanyBankAccount $companyBankAccount): View
    {
        return view('admin.rekening-form', ['bankAccount' => $companyBankAccount]);
    }

    /**
     * Store a company destination account.
     */
    public function store(StoreCompanyBankAccountRequest $request): RedirectResponse
    {
        $bankAccount = CompanyBankAccount::query()->create($request->validated());
        $this->audit($request, 'company_bank_account_created', $bankAccount, null, $bankAccount->is_active ? 'aktif' : 'nonaktif');

        return redirect()->route('admin.rekening')->with('status', 'Rekening perusahaan ditambahkan.');
    }

    /**
     * Update a company destination account.
     */
    public function update(
        UpdateCompanyBankAccountRequest $request,
        CompanyBankAccount $companyBankAccount,
    ): RedirectResponse {
        $fromStatus = $companyBankAccount->is_active ? 'aktif' : 'nonaktif';
        $companyBankAccount->update($request->validated());
        $this->audit(
            $request,
            'company_bank_account_updated',
            $companyBankAccount,
            $fromStatus,
            $companyBankAccount->is_active ? 'aktif' : 'nonaktif',
        );

        return redirect()->route('admin.rekening')->with('status', 'Rekening perusahaan diperbarui.');
    }

    /**
     * Activate or deactivate a destination account.
     */
    public function toggle(Request $request, CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        $active = ! $companyBankAccount->is_active;
        if (! $active && CompanyBankAccount::query()->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'account' => 'Minimal satu rekening perusahaan harus tetap aktif.',
            ]);
        }

        DB::transaction(function () use ($request, $companyBankAccount, $active): void {
            $account = CompanyBankAccount::query()->whereKey($companyBankAccount->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $account->is_active ? 'aktif' : 'nonaktif';
            $account->update(['is_active' => $active]);
            $this->audit($request, 'company_bank_account_status_changed', $account, $fromStatus, $active ? 'aktif' : 'nonaktif');
        });

        return redirect()->route('admin.rekening')->with('status', 'Status rekening perusahaan diperbarui.');
    }

    /**
     * Record an auditable account-management action.
     */
    private function audit(
        Request $request,
        string $action,
        CompanyBankAccount $account,
        ?string $fromStatus,
        string $toStatus,
    ): void {
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $account->getMorphClass(),
            'subject_id' => $account->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => null,
            'ip' => $request->ip(),
        ]);
    }
}
