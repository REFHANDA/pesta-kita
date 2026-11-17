<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * List users with optional filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:admin,klien,vendor,venue'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $users = User::query()
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.user-management', ['users' => $users, 'filters' => $filters]);
    }

    /**
     * Show account details without allowing role or profile changes.
     */
    public function show(User $user): View
    {
        return view('admin.user-management-detail', ['managedUser' => $user]);
    }

    /**
     * Activate or deactivate an account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:aktif,nonaktif']]);
        $newStatus = UserStatus::from($data['status']);

        if ($request->user()->is($user) && $newStatus === UserStatus::Nonaktif) {
            throw ValidationException::withMessages([
                'status' => 'Admin tidak dapat menonaktifkan akunnya sendiri.',
            ]);
        }

        DB::transaction(function () use ($request, $user, $newStatus): void {
            $activeAdminIds = [];
            if ($user->role === Role::Admin && $newStatus === UserStatus::Nonaktif) {
                $activeAdminIds = User::query()
                    ->where('role', Role::Admin)
                    ->where('status', UserStatus::Aktif)
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all();
            }

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $oldStatus = $lockedUser->status;

            if ($lockedUser->role === Role::Admin
                && $oldStatus === UserStatus::Aktif
                && $newStatus === UserStatus::Nonaktif
                && count($activeAdminIds) <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'Admin aktif terakhir tidak dapat dinonaktifkan.',
                ]);
            }

            if ($oldStatus === $newStatus) {
                return;
            }

            $lockedUser->update(['status' => $newStatus]);
            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'user_account_status_changed',
                'subject_type' => $lockedUser->getMorphClass(),
                'subject_id' => $lockedUser->id,
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.users.detail', $user)
            ->with('status', 'Status akun berhasil diperbarui.');
    }
}
