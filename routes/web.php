<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PriceChangeController as AdminPriceChangeController;
use App\Http\Controllers\Admin\ProviderVerificationController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Klien\CalendarController as KlienCalendarController;
use App\Http\Controllers\Klien\CatalogController as KlienCatalogController;
use App\Http\Controllers\Klien\DashboardController as KlienDashboardController;
use App\Http\Controllers\Klien\EventController as KlienEventController;
use App\Http\Controllers\Klien\QuoteRequestController as KlienQuoteRequestController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Vendor\AvailabilityController as VendorAvailabilityController;
use App\Http\Controllers\Vendor\CatalogItemController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\PriceChangeController as VendorPriceChangeController;
use App\Http\Controllers\Vendor\QuoteRequestController as VendorQuoteRequestController;
use App\Http\Controllers\Venue\AvailabilityController as VenueAvailabilityController;
use App\Http\Controllers\Venue\DashboardController as VenueDashboardController;
use App\Http\Controllers\Venue\PriceChangeController as VenuePriceChangeController;
use App\Http\Controllers\Venue\QuoteRequestController as VenueQuoteRequestController;
use App\Http\Controllers\Venue\VenueController;
use App\Http\Controllers\Venue\VenueRoomController;
use App\Http\Controllers\VerificationController;
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
| BE-1: seluruh portal dilindungi auth + role. Route POST eksplisit wajib
| didaftarkan sebelum catch-all /{page} agar tidak tertangkap halaman dinamis.
|
*/

// --- Publik / Landing & Auth ---
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Halaman masuk & daftar hanya untuk guest; pengguna login diarahkan ke
// portal sesuai role akun. Halaman login portal memakai POST /login yang sama.
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/klien/login', function () {
        return view('klien.login');
    })->name('klien.login');

    Route::get('/vendor/login', function () {
        return view('vendor.login');
    })->name('vendor.login');

    Route::get('/admin/login', function () {
        return view('admin.login');
    })->name('admin.login');
});

// --- Aksi tulis eksplisit (sebelum catch-all portal) ---
Route::post('/login', [LoginController::class, 'store'])->middleware('guest')->name('login.store');
Route::post('/register', [RegisterController::class, 'store'])->middleware('guest')->name('register.store');
Route::post('/logout', [LogoutController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/profil', [ProfileController::class, 'update'])->middleware('auth')->name('profil.update');
Route::post('/profil/sandi', [ProfileController::class, 'updatePassword'])->middleware('auth')->name('profil.sandi');

// --- BE-3: media (B12/B14) — satu pintu baca/hapus berotorisasi ---
Route::middleware('auth')->group(function () {
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/profil/foto', [MediaController::class, 'storeProfilePhoto'])->name('profil.foto.store');
});
Route::post('/verifikasi/dokumen', [MediaController::class, 'storeDocument'])
    ->middleware(['auth', 'role:vendor,venue'])->name('verifikasi.dokumen.store');
Route::post('/verifikasi/ajukan', [VerificationController::class, 'submit'])
    ->middleware(['auth', 'role:vendor,venue'])->name('verifikasi.ajukan');

// --- Portal Klien (Penyelenggara Acara) ---
Route::prefix('klien')->name('klien.')->middleware(['auth', 'role:klien'])->group(function () {
    Route::get('/', [KlienDashboardController::class, 'index'])->name('beranda');

    // BE-3: katalog publik — filter di query backend (sebelum catch-all).
    Route::get('/katalog-vendor', [KlienCatalogController::class, 'katalogVendor'])->name('katalog.vendor');
    Route::get('/katalog-vendor-detail/{item}', [KlienCatalogController::class, 'katalogVendorDetail'])->name('katalog.vendor.detail');
    Route::get('/katalog-peralatan', [KlienCatalogController::class, 'katalogPeralatan'])->name('katalog.peralatan');
    Route::get('/katalog', [KlienCatalogController::class, 'katalog'])->name('katalog.venue');
    Route::get('/katalog-venue-detail/{venue}', [KlienCatalogController::class, 'katalogVenueDetail'])->name('katalog.venue.detail');
    Route::get('/penyedia-vendor/{profile}', [KlienCatalogController::class, 'penyediaVendor'])->name('penyedia.vendor');
    Route::get('/penyedia-venue/{profile}', [KlienCatalogController::class, 'penyediaVenue'])->name('penyedia.venue');

    // BE-3b: kalender ketersediaan read-only (sebelum catch-all).
    Route::get('/kalender-ketersediaan', [KlienCalendarController::class, 'index'])->name('kalender');

    // Wiring read-only: daftar acara nyata milik akun login (sebelum catch-all).
    // Halaman detail/pesanan/tagihan acara masih prototipe BE-4–BE-7.
    Route::get('/acara', [KlienEventController::class, 'index'])->name('acara');
    Route::get('/riwayat', [KlienEventController::class, 'riwayat'])->name('riwayat');

    // Wiring read-only: halaman status per acara + detail satu acara nyata
    // milik akun login, dan permintaan penawaran (quote_requests) milik
    // sendiri. Alur tulis (buat acara, ajukan/putus penawaran) BE-4/BE-5.
    Route::get('/acara-detail/{event}', [KlienEventController::class, 'show'])->name('acara.detail');
    Route::get('/acara-draft', [KlienEventController::class, 'draft'])->name('acara.draft');
    Route::get('/acara-diajukan', [KlienEventController::class, 'diajukan'])->name('acara.diajukan');
    Route::get('/acara-terkonfirmasi', [KlienEventController::class, 'terkonfirmasi'])->name('acara.terkonfirmasi');
    Route::get('/acara-dibatalkan', [KlienEventController::class, 'dibatalkan'])->name('acara.dibatalkan');
    Route::get('/permintaan-penawaran', [KlienQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [KlienQuoteRequestController::class, 'show'])->name('penawaran.detail');

    Route::get('/{page}', function ($page) {
        $view = 'klien.'.$page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});

// --- Portal Vendor (Katering, Dekorasi, Hiburan, Peralatan) ---
Route::prefix('vendor')->name('vendor.')->middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('/', [VendorDashboardController::class, 'index'])->name('beranda');

    // BE-3: unggah foto item katalog (sebelum catch-all).
    Route::post('/katalog/{item}/foto', [MediaController::class, 'storeItemPhoto'])->name('katalog.foto.store');

    // BE-3: CRUD katalog vendor (B15) — eksplisit sebelum catch-all.
    Route::get('/layanan', [CatalogItemController::class, 'layanan'])->name('layanan');
    Route::get('/peralatan', [CatalogItemController::class, 'peralatan'])->name('peralatan');
    Route::get('/item-form', [CatalogItemController::class, 'createLayanan'])->name('katalog.create');
    Route::get('/item-form/{item}', [CatalogItemController::class, 'edit'])->name('katalog.edit');
    Route::get('/peralatan-form', [CatalogItemController::class, 'createPeralatan'])->name('peralatan.create');
    Route::get('/peralatan-form/{item}', [CatalogItemController::class, 'edit'])->name('peralatan.edit');
    Route::post('/katalog', [CatalogItemController::class, 'store'])->name('katalog.store');
    Route::put('/katalog/{item}', [CatalogItemController::class, 'update'])->name('katalog.update');
    Route::post('/katalog/{item}/status', [CatalogItemController::class, 'toggleStatus'])->name('katalog.status');
    Route::delete('/katalog/{item}', [CatalogItemController::class, 'destroy'])->name('katalog.destroy');

    // BE-3 (B14): halaman verifikasi penyedia.
    Route::get('/verifikasi', [VerificationController::class, 'show'])->name('verifikasi');

    // BE-3b: kalender ketersediaan item + pengajuan harga (sebelum catch-all).
    Route::get('/kalender-ketersediaan', [VendorAvailabilityController::class, 'index'])->name('kalender');
    Route::post('/kalender-ketersediaan', [VendorAvailabilityController::class, 'update'])->name('kalender.update');
    Route::get('/pengajuan-harga', [VendorPriceChangeController::class, 'index'])->name('pengajuan-harga');
    Route::post('/pengajuan-harga', [VendorPriceChangeController::class, 'store'])->name('pengajuan-harga.store');

    // Wiring read-only: permintaan penawaran masuk atas item katalog milik
    // vendor login (sebelum catch-all). Alur tulis usulan harga BE-4.
    Route::get('/permintaan-penawaran', [VendorQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [VendorQuoteRequestController::class, 'show'])->name('penawaran.detail');

    Route::get('/{page}', function ($page) {
        $view = 'vendor.'.$page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});

// --- Portal Pemilik Venue (Gedung, Aula, Tempat Pertemuan) ---
Route::prefix('venue')->name('venue.')->middleware(['auth', 'role:venue'])->group(function () {
    Route::get('/', [VenueDashboardController::class, 'index'])->name('beranda');

    // BE-3: unggah foto venue (sebelum catch-all).
    Route::post('/data/{venue}/foto', [MediaController::class, 'storeVenuePhoto'])->name('data.foto.store');

    // BE-3: CRUD venue + ruangan (sebelum catch-all).
    Route::get('/data', [VenueController::class, 'data'])->name('data');
    Route::get('/form', [VenueController::class, 'create'])->name('form.create');
    Route::get('/form/{venue}', [VenueController::class, 'edit'])->name('form.edit');
    Route::post('/data', [VenueController::class, 'store'])->name('data.store');
    Route::put('/data/{venue}', [VenueController::class, 'update'])->name('data.update');
    Route::delete('/data/{venue}', [VenueController::class, 'destroy'])->name('data.destroy');
    Route::post('/data/{venue}/ruangan', [VenueRoomController::class, 'store'])->name('ruangan.store');
    Route::put('/ruangan/{room}', [VenueRoomController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{room}', [VenueRoomController::class, 'destroy'])->name('ruangan.destroy');

    // BE-3 (B14): halaman verifikasi penyedia.
    Route::get('/verifikasi', [VerificationController::class, 'show'])->name('verifikasi');

    // BE-3b: kalender ketersediaan ruangan + pengajuan harga venue (sebelum catch-all).
    Route::get('/kalender-ketersediaan', [VenueAvailabilityController::class, 'index'])->name('kalender');
    Route::post('/kalender-ketersediaan', [VenueAvailabilityController::class, 'update'])->name('kalender.update');
    Route::get('/pengajuan-harga', [VenuePriceChangeController::class, 'index'])->name('pengajuan-harga');
    Route::post('/pengajuan-harga', [VenuePriceChangeController::class, 'store'])->name('pengajuan-harga.store');

    // Wiring read-only: permintaan penawaran masuk atas ruangan venue milik
    // login (sebelum catch-all). Alur tulis usulan harga BE-4.
    Route::get('/permintaan-penawaran', [VenueQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [VenueQuoteRequestController::class, 'show'])->name('penawaran.detail');

    Route::get('/{page}', function ($page) {
        $view = 'venue.'.$page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});

// --- Panel Admin (Perusahaan Pesta Kita) ---
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('beranda');

    // BE-3: cleaning fee & status venue individual (keputusan butir 3 & 8).
    Route::get('/admin-venue-form/{venue}', [AdminVenueController::class, 'edit'])->name('venue.edit');
    Route::post('/venue/{venue}', [AdminVenueController::class, 'update'])->name('venue.update');

    // BE-3 (B14): antrean, detail, dan keputusan verifikasi mitra.
    Route::get('/admin-penyedia', [ProviderVerificationController::class, 'index'])->name('penyedia.index');
    Route::get('/penyedia/{type}/{id}', [ProviderVerificationController::class, 'show'])
        ->whereIn('type', ['vendor_profile', 'venue_profile'])->name('penyedia.detail');
    Route::post('/penyedia/{type}/{id}/verifikasi/setujui', [ProviderVerificationController::class, 'approve'])
        ->whereIn('type', ['vendor_profile', 'venue_profile'])->name('penyedia.verifikasi.approve');
    Route::post('/penyedia/{type}/{id}/verifikasi/tolak', [ProviderVerificationController::class, 'reject'])
        ->whereIn('type', ['vendor_profile', 'venue_profile'])->name('penyedia.verifikasi.reject');

    // BE-3b: antrean dan keputusan pengajuan harga (sebelum catch-all).
    Route::get('/pengajuan-harga', [AdminPriceChangeController::class, 'index'])->name('pengajuan-harga');
    Route::get('/pengajuan-harga/{priceChange}', [AdminPriceChangeController::class, 'show'])->name('pengajuan-harga.detail');
    Route::post('/pengajuan-harga/{priceChange}/{action}', [AdminPriceChangeController::class, 'decide'])
        ->whereIn('action', ['approve', 'reject'])->name('pengajuan-harga.decide');

    // Wiring read-only: log aktivitas membaca `audit_logs` yang sudah ditulis
    // backend. Append-only — tidak ada rute tulis dari halaman ini.
    Route::get('/aktivitas', [AdminActivityLogController::class, 'index'])->name('aktivitas');
    Route::get('/aktivitas/{auditLog}', [AdminActivityLogController::class, 'show'])->name('aktivitas.detail');

    // Wiring read-only: tinjauan katalog nyata (item + venue). Tidak ada
    // persetujuan per item (keputusan Gate 4B butir 2) — status item dikelola
    // penyedia, harga lewat pengajuan harga, venue lewat formulir venue.
    Route::get('/katalog', [AdminCatalogController::class, 'index'])->name('katalog');
    Route::get('/katalog-detail/{item}', [AdminCatalogController::class, 'show'])->name('katalog.detail');

    Route::get('/{page}', function ($page) {
        $view = 'admin.'.$page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});
