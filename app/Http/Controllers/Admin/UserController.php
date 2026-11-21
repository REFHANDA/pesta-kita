<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar akun pengguna untuk Admin (baca-saja). Akun penyedia dibuat lewat
 * pendaftaran mandiri, bukan oleh Admin.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = Role::tryFrom((string) $request->query('role'));
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->with(['vendorProfile.bankAccounts', 'venueProfile.bankAccounts'])
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pengguna', [
            'users' => $users,
            'activeRole' => $role,
            'search' => $search,
        ]);
    }
}
