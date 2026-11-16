<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * BE-1: registrasi publik klien/vendor/venue.
 * User dan profil dibuat dalam satu transaction. Provider baru langsung
 * aktif (status akun) dengan verifikasi bisnis menunggu (T2); publikasi
 * katalog ke Klien menunggu tahap verifikasi berikutnya.
 */
class RegisterController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'status' => UserStatus::Aktif,
            ]);

            match ($user->role) {
                Role::Klien => $user->clientProfile()->create([]),
                Role::Vendor => $user->vendorProfile()->create([
                    'business_name' => $data['business_name'],
                    'category' => $data['category'],
                    'address' => $data['address'],
                    'verification_status' => VerificationStatus::Menunggu,
                    'verified_at' => null,
                ]),
                Role::Venue => $user->venueProfile()->create([
                    'business_name' => $data['business_name'],
                    'category' => null,
                    'address' => $data['address'],
                    'verification_status' => VerificationStatus::Menunggu,
                    'verified_at' => null,
                ]),
                default => throw new RuntimeException('Role tidak dapat didaftarkan melalui form publik.'),
            };

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        $target = $user->role === Role::Klien
            ? $user->role->portalPath()
            : $user->role->portalPath().'/verifikasi';

        return redirect()->to($target);
    }
}
