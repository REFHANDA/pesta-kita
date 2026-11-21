<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * BE-1: gerbang role portal (alias `role`).
 *
 * - Guest diarahkan ke /login (middleware auth berjalan lebih dulu).
 * - Akun yang dinonaktifkan saat session aktif ditolak: session di-invalidate
 *   dan pengguna dikembalikan ke /login dengan pesan generik.
 * - Role tidak sesuai mendapat HTTP 403.
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->status !== UserStatus::Aktif) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak aktif. Hubungi Admin Pesta Kita.',
            ]);
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
