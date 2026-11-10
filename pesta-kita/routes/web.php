
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

// HALAMAN UTAMA
Route::get('/', function () {
    return view('welcome');
})->name('home');

// LOGIN & REGISTER
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/register', function () {
    return view('register-klien');
})->name('register');

Route::get('/register/vendor', function () {
    return view('register-vendor');
})->name('register.vendor');

Route::get('/register/venue', function () {
    return view('register-vendor');
})->name('register.venue');

// DASHBOARD KLIEN
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/klien/dashboard', function () {
        return view('dashboard');
    })->name('klien.dashboard');

    // RANCANG ACARA
    Route::get('/klien/rancang-acara', function () {
        return view('rancang-acara');
    })->name('klien.rancang');

    // RIWAYAT EVALUASI
    Route::get('/klien/riwayat-evaluasi', function () {
        return view('riwayat-evaluasi');
    })->name('klien.riwayat');

    // BUAT KONSEP
    Route::get('/klien/buat-konsep', function () {
        return view('buat-konsep');
    })->name('klien.buat-konsep');

    // DETAIL ACARA
    Route::get('/klien/detail-acara', function () {
        return view('detail-acara');
    })->name('klien.detail-acara');

    // VENDOR & VENUE
    Route::get('/klien/vendor-venue', function () {
        return view('vendor-venue');
    })->name('klien.vendor-venue');

    // SEWA PERALATAN
    Route::get('/klien/sewa-peralatan', function () {
        return view('sewa-peralatan');
    })->name('klien.sewa-peralatan');

    // DETAIL VENUE
    Route::get('/klien/detail-venue', function () {
        return view('detail-venue');
    })->name('klien.detail-venue');

    // PESANAN & TAGIHAN
    Route::get('/klien/pesanan-tagihan', function () {
        return view('pesanan-tagihan');
    })->name('klien.pesanan-tagihan');

    Route::get('/klien/detail-pesanan', function () {
        return view('detail-pesanan');
    })->name('klien.detail-pesanan');

    Route::get('/klien/evaluasi', function () {
        return view('evaluasi');
    })->name('klien.evaluasi');

    // NOTIFIKASI
    Route::get('/klien/notifikasi', function () {
        return view('notifikasi');
    })->name('klien.notifikasi');

    Route::get('/klien/profil', function () {
        return view('profil-saya');
    })->name('klien.profil');
});
