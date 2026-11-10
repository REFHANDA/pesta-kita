<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Pesta Kita
|--------------------------------------------------------------------------
|
| Rute untuk antarmuka UI/UX Pesta Kita:
| 1. Publik / Landing page & Autentikasi
| 2. Portal Klien (28 halaman)
| 3. Portal Penyedia - Vendor & Venue (21 halaman)
| 4. Panel Admin (35 halaman)
|
*/

// --- Publik / Landing & Auth ---
Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout');


// --- Portal Klien ---
Route::prefix('klien')->name('klien.')->group(function () {
    Route::get('/', function () {
        return view('klien.index');
    })->name('beranda');

    Route::get('/{page}', function ($page) {
        $view = 'klien.' . $page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});


// --- Portal Penyedia (Vendor & Venue) ---
Route::prefix('penyedia')->name('penyedia.')->group(function () {
    Route::get('/', function () {
        return view('penyedia.index');
    })->name('beranda');

    Route::get('/{page}', function ($page) {
        $view = 'penyedia.' . $page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});


// --- Panel Admin ---
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    })->name('beranda');

    Route::get('/{page}', function ($page) {
        $view = 'admin.' . $page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});
