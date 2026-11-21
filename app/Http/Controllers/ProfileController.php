<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * BE-1: profil dan kata sandi pengguna login.
 * Endpoint dipakai bersama seluruh portal; kepemilikan data selalu
 * mengacu pada authenticated user, bukan ID dari input. Email read-only.
 */
class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill(Arr::only($data, ['name', 'phone']))->save();

        $profileFields = Arr::only($data, ['business_name', 'category', 'address', 'pic_name']);

        if ($user->role === Role::Vendor) {
            $user->vendorProfile()->updateOrCreate([], $profileFields);
        } elseif ($user->role === Role::Venue) {
            $user->venueProfile()->updateOrCreate([], $profileFields);
        }

        return redirect()->to($user->role->portalPath().'/profil')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update(['password' => $request->validated('password')]);

        return redirect()->to($user->role->portalPath().'/profil')
            ->with('status', 'Kata sandi berhasil diubah.');
    }
}
