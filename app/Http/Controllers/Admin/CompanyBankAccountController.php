<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanyBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan rekening resmi perusahaan oleh Admin (Fase 5):
 * - CRUD rekening bank (nama bank, nomor rekening, atas nama, urutan/sort_order, status aktif).
 * - Rekening nonaktif tidak ditampilkan pada pilihan pembayaran klien.
 * - Jejak audit dicatat pada setiap penambahan, pengubahan, dan perubahan status aktif/nonaktif.
 */
class CompanyBankAccountController extends Controller
{
    /**
     * Tampilkan daftar seluruh rekening perusahaan.
     */
    public function index(): View
    {
        $accounts = CompanyBankAccount::query()
            ->orderBy('sort_order')
            ->get();

        return view('admin.rekening', compact('accounts'));
    }

    /**
     * Tampilkan formulir tambah rekening.
     */
    public function create(): View
    {
        $account = new CompanyBankAccount([
            'account_name' => 'PT Pesta Kita Indonesia',
            'is_active' => true,
            'sort_order' => ((int) CompanyBankAccount::max('sort_order')) + 1,
        ]);

        return view('admin.rekening-form', [
            'account' => $account,
            'mode' => 'tambah',
        ]);
    }

    /**
     * Simpan rekening baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50', 'unique:company_bank_accounts,account_number'],
            'account_name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'bank_name.required' => 'Nama bank wajib diisi.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.unique' => 'Nomor rekening ini sudah terdaftar.',
            'account_name.required' => 'Atas nama rekening wajib diisi.',
            'sort_order.required' => 'Urutan tampil wajib diisi.',
        ]);

        $account = CompanyBankAccount::create([
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'company_bank_account_created',
            'subject_type' => $account->getMorphClass(),
            'subject_id' => $account->id,
            'from_status' => null,
            'to_status' => $account->is_active ? 'aktif' : 'nonaktif',
            'reason' => "Admin menambahkan rekening baru: {$account->bank_name} {$account->account_number} a.n. {$account->account_name}.",
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('admin.rekening.index')
            ->with('success', "Rekening {$account->bank_name} ({$account->account_number}) berhasil ditambahkan.");
    }

    /**
     * Tampilkan formulir ubah rekening.
     */
    public function edit(CompanyBankAccount $rekening): View
    {
        return view('admin.rekening-form', [
            'account' => $rekening,
            'mode' => 'edit',
        ]);
    }

    /**
     * Perbarui data rekening perusahaan.
     */
    public function update(Request $request, CompanyBankAccount $rekening): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('company_bank_accounts', 'account_number')->ignore($rekening->id),
            ],
            'account_name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'bank_name.required' => 'Nama bank wajib diisi.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.unique' => 'Nomor rekening ini sudah terdaftar pada rekening lain.',
            'account_name.required' => 'Atas nama rekening wajib diisi.',
            'sort_order.required' => 'Urutan tampil wajib diisi.',
        ]);

        $oldSummary = "{$rekening->bank_name} {$rekening->account_number} (urutan {$rekening->sort_order}, ".($rekening->is_active ? 'aktif' : 'nonaktif').')';

        $rekening->update([
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $newSummary = "{$rekening->bank_name} {$rekening->account_number} (urutan {$rekening->sort_order}, ".($rekening->is_active ? 'aktif' : 'nonaktif').')';

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'company_bank_account_updated',
            'subject_type' => $rekening->getMorphClass(),
            'subject_id' => $rekening->id,
            'from_status' => $oldSummary,
            'to_status' => $newSummary,
            'reason' => "Admin memperbarui data rekening {$rekening->bank_name} {$rekening->account_number}.",
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('admin.rekening.index')
            ->with('success', "Rekening {$rekening->bank_name} berhasil diperbarui.");
    }

    /**
     * Alihkan status aktif/nonaktif rekening.
     */
    public function toggle(CompanyBankAccount $rekening): RedirectResponse
    {
        $newStatus = ! $rekening->is_active;
        $rekening->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'company_bank_account_status_changed',
            'subject_type' => $rekening->getMorphClass(),
            'subject_id' => $rekening->id,
            'from_status' => ! $newStatus ? 'aktif' : 'nonaktif',
            'to_status' => $newStatus ? 'aktif' : 'nonaktif',
            'reason' => "Admin mengubah status rekening {$rekening->bank_name} {$rekening->account_number} menjadi {$statusLabel}.",
            'ip' => request()->ip(),
        ]);

        return redirect()
            ->route('admin.rekening.index')
            ->with('success', "Rekening {$rekening->bank_name} {$rekening->account_number} berhasil {$statusLabel}.");
    }

    /**
     * Hapus rekening perusahaan.
     */
    public function destroy(CompanyBankAccount $rekening): RedirectResponse
    {
        $summary = "{$rekening->bank_name} {$rekening->account_number} ({$rekening->account_name})";

        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'company_bank_account_deleted',
            'subject_type' => $rekening->getMorphClass(),
            'subject_id' => $rekening->id,
            'reason' => "Admin menghapus rekening {$summary}.",
            'ip' => request()->ip(),
        ]);

        $rekening->delete();

        return redirect()
            ->route('admin.rekening.index')
            ->with('success', "Rekening {$summary} berhasil dihapus.");
    }
}
