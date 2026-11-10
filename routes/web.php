<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Pesta Kita
|--------------------------------------------------------------------------
|
| Rute untuk antarmuka UI/UX Pesta Kita (Sesuai Rancangan EMS 4 Aktor):
| 1. Publik / Landing page & Autentikasi
| 2. Portal Klien (/klien)
| 3. Portal Vendor (/vendor)
| 4. Portal Pemilik Venue (/venue)
| 5. Panel Admin (/admin)
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


// --- Portal Klien (Penyelenggara Acara) ---
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


// --- Portal Vendor (Katering, Dekorasi, Hiburan, Peralatan) ---
Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', function () {
        return view('vendor.index');
    })->name('beranda');

    Route::get('/{page}', function ($page) {
        $view = 'vendor.' . $page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});


// --- Portal Pemilik Venue (Gedung, Aula, Tempat Pertemuan) ---
Route::prefix('venue')->name('venue.')->group(function () {
    Route::get('/', function () {
        return view('venue.index');
    })->name('beranda');

    Route::get('/{page}', function ($page) {
        $view = 'venue.' . $page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});


// --- Backward-compatibility Alias: /penyedia ---
Route::prefix('penyedia')->name('penyedia.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('vendor.beranda');
    });

    Route::get('/venue', function () {
        return redirect()->route('venue.beranda');
    });

    Route::get('/{page}', function ($page) {
        if (view()->exists('vendor.' . $page)) {
            return redirect()->to('/vendor/' . $page);
        }
        if (view()->exists('venue.' . $page)) {
            return redirect()->to('/venue/' . $page);
        }
        if (view()->exists('penyedia.' . $page)) {
            return view('penyedia.' . $page);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+');
});


// --- Panel Admin (Perusahaan Pesta Kita) ---
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
