<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->role === 'client'
                ? redirect()->route('klien.dashboard')
                : redirect()->route('home');
        }

        return view('login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()->role === 'client') {
            return redirect()->intended(route('klien.dashboard'));
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
