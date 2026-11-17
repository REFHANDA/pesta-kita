<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\CompanyBankAccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventCompletionController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\OrderReviewController as AdminOrderReviewController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PriceChangeController as AdminPriceChangeController;
use App\Http\Controllers\Admin\ProviderVerificationController;
use App\Http\Controllers\Admin\QuoteRequestController as AdminQuoteRequestController;
use App\Http\Controllers\Admin\RefundRequestController as AdminRefundRequestController;
use App\Http\Controllers\Admin\RundownController as AdminRundownController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Klien\CalendarController as KlienCalendarController;
use App\Http\Controllers\Klien\CatalogController as KlienCatalogController;
use App\Http\Controllers\Klien\DashboardController as KlienDashboardController;
use App\Http\Controllers\Klien\EventController as KlienEventController;
use App\Http\Controllers\Klien\EventOrderItemController as KlienEventOrderItemController;
use App\Http\Controllers\Klien\EventReviewController;
use App\Http\Controllers\Klien\InvoiceController as KlienInvoiceController;
use App\Http\Controllers\Klien\QuoteRequestController as KlienQuoteRequestController;
use App\Http\Controllers\Klien\RefundRequestController as KlienRefundRequestController;
use App\Http\Controllers\Klien\RundownController as KlienRundownController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Vendor\AvailabilityController as VendorAvailabilityController;
use App\Http\Controllers\Vendor\CatalogItemController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Vendor\PriceChangeController as VendorPriceChangeController;
use App\Http\Controllers\Vendor\QuoteRequestController as VendorQuoteRequestController;
use App\Http\Controllers\Vendor\ReviewController as VendorReviewController;
use App\Http\Controllers\Vendor\RundownController as VendorRundownController;
use App\Http\Controllers\Venue\AvailabilityController as VenueAvailabilityController;
use App\Http\Controllers\Venue\DashboardController as VenueDashboardController;
use App\Http\Controllers\Venue\OrderController as VenueOrderController;
use App\Http\Controllers\Venue\PayoutController as VenuePayoutController;
use App\Http\Controllers\Venue\PriceChangeController as VenuePriceChangeController;
use App\Http\Controllers\Venue\QuoteRequestController as VenueQuoteRequestController;
use App\Http\Controllers\Venue\ReviewController as VenueReviewController;
use App\Http\Controllers\Venue\RundownController as VenueRundownController;
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

    // Daftar acara nyata milik akun login (sebelum catch-all).
    Route::get('/acara', [KlienEventController::class, 'index'])->name('acara');
    Route::get('/riwayat', [KlienEventController::class, 'riwayat'])->name('riwayat');

    // BE-5: draft acara, pemilihan item, dan pengajuan acara.
    Route::get('/acara-baru', [KlienEventController::class, 'create'])->name('acara.create');
    Route::post('/acara', [KlienEventController::class, 'store'])->name('acara.store');
    Route::get('/acara-form/{event}', [KlienEventController::class, 'edit'])->name('acara.edit');
    Route::put('/acara/{event}', [KlienEventController::class, 'update'])->name('acara.update');
    Route::post('/acara/{event}/items', [KlienEventOrderItemController::class, 'store'])->name('acara.items.store');
    Route::delete('/acara/{event}/items/{orderItem}', [KlienEventOrderItemController::class, 'destroy'])->name('acara.items.destroy');
    Route::post('/acara/{event}/submit', [KlienEventOrderItemController::class, 'submit'])->name('acara.submit');
    Route::get('/tagihan/{invoice}', [KlienInvoiceController::class, 'show'])->name('tagihan');
    Route::post('/tagihan/{invoice}/pembayaran', [KlienInvoiceController::class, 'storePayment'])->name('tagihan.pembayaran.store');
    Route::get('/pembayaran/{payment}/bukti', [KlienInvoiceController::class, 'proof'])->name('pembayaran.bukti');
    Route::get('/acara/{event}/refund', [KlienRefundRequestController::class, 'create'])->name('refund.create');
    Route::post('/acara/{event}/refund', [KlienRefundRequestController::class, 'store'])->name('refund.store');
    Route::get('/acara/{event}/rundown', [KlienRundownController::class, 'show'])->name('rundown');
    Route::post('/acara/{event}/rundown', [KlienRundownController::class, 'store'])->name('rundown.store');
    Route::put('/acara/{event}/rundown/{rundownItem}', [KlienRundownController::class, 'update'])->name('rundown.update');
    Route::delete('/acara/{event}/rundown/{rundownItem}', [KlienRundownController::class, 'destroy'])->name('rundown.destroy');
    Route::post('/acara/{event}/ulasan/{orderItem}', [EventReviewController::class, 'store'])->name('ulasan.store');

    // BE-5 read/write endpoints for owned event views; quote flow is BE-4.
    Route::get('/acara-detail/{event}', [KlienEventController::class, 'show'])->name('acara.detail');
    Route::get('/acara-draft', [KlienEventController::class, 'draft'])->name('acara.draft');
    Route::get('/acara-diajukan', [KlienEventController::class, 'diajukan'])->name('acara.diajukan');
    Route::get('/acara-terkonfirmasi', [KlienEventController::class, 'terkonfirmasi'])->name('acara.terkonfirmasi');
    Route::get('/acara-dibatalkan', [KlienEventController::class, 'dibatalkan'])->name('acara.dibatalkan');
    Route::get('/permintaan-penawaran', [KlienQuoteRequestController::class, 'index'])->name('penawaran');
    Route::post('/permintaan-penawaran', [KlienQuoteRequestController::class, 'store'])->name('penawaran.store');
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

    // BE-4: permintaan penawaran dan usulan harga (sebelum catch-all).
    Route::get('/permintaan-penawaran', [VendorQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [VendorQuoteRequestController::class, 'show'])->name('penawaran.detail');
    Route::post('/permintaan-penawaran-detail/{quoteRequest}', [VendorQuoteRequestController::class, 'store'])->name('penawaran.store');

    // BE-5: pesanan Vendor dan keberatan dalam 24 jam.
    Route::get('/pesanan', [VendorOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan-detail/{orderItem}', [VendorOrderController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan-detail/{orderItem}/keberatan', [VendorOrderController::class, 'storeObjection'])->name('keberatan.store');
    Route::get('/keberatan', [VendorOrderController::class, 'objections'])->name('keberatan');
    Route::get('/pencairan', [VendorPayoutController::class, 'index'])->name('pencairan');
    Route::get('/pencairan/{payout}', [VendorPayoutController::class, 'show'])->name('pencairan.detail');
    Route::get('/pencairan/{payout}/bukti', [VendorPayoutController::class, 'proof'])->name('pencairan.bukti');
    Route::get('/rundown', [VendorRundownController::class, 'index'])->name('rundown.index');
    Route::get('/rundown/{event}', [VendorRundownController::class, 'show'])->name('rundown');
    Route::post('/rundown/{event}', [VendorRundownController::class, 'store'])->name('rundown.store');
    Route::put('/rundown/{event}/{rundownItem}', [VendorRundownController::class, 'update'])->name('rundown.update');
    Route::delete('/rundown/{event}/{rundownItem}', [VendorRundownController::class, 'destroy'])->name('rundown.destroy');
    Route::get('/ulasan', [VendorReviewController::class, 'index'])->name('ulasan');

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

    // BE-4: permintaan penawaran dan usulan harga (sebelum catch-all).
    Route::get('/permintaan-penawaran', [VenueQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [VenueQuoteRequestController::class, 'show'])->name('penawaran.detail');
    Route::post('/permintaan-penawaran-detail/{quoteRequest}', [VenueQuoteRequestController::class, 'store'])->name('penawaran.store');

    // BE-5: pesanan Pemilik Venue dan keberatan dalam 24 jam.
    Route::get('/pesanan', [VenueOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan-detail/{orderItem}', [VenueOrderController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan-detail/{orderItem}/keberatan', [VenueOrderController::class, 'storeObjection'])->name('keberatan.store');
    Route::get('/keberatan', [VenueOrderController::class, 'objections'])->name('keberatan');
    Route::get('/ulasan', [VenueReviewController::class, 'index'])->name('ulasan');
    Route::get('/pencairan', [VenuePayoutController::class, 'index'])->name('pencairan');
    Route::get('/pencairan/{payout}', [VenuePayoutController::class, 'show'])->name('pencairan.detail');
    Route::get('/pencairan/{payout}/bukti', [VenuePayoutController::class, 'proof'])->name('pencairan.bukti');
    Route::get('/rundown', [VenueRundownController::class, 'index'])->name('rundown');

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

    // BE-4: antrean, detail, dan keputusan penawaran sebelum catch-all.
    Route::get('/permintaan-penawaran', [AdminQuoteRequestController::class, 'index'])->name('penawaran');
    Route::get('/permintaan-penawaran-detail', [AdminQuoteRequestController::class, 'empty'])->name('penawaran.empty');
    Route::get('/permintaan-penawaran-detail/{quoteRequest}', [AdminQuoteRequestController::class, 'show'])->name('penawaran.detail');
    Route::post('/permintaan-penawaran/{quoteRequest}/{action}', [AdminQuoteRequestController::class, 'decide'])
        ->whereIn('action', ['approve', 'reject'])->name('penawaran.decide');

    // BE-5: review item pesanan per acara, serta keputusan keberatan penyedia.
    Route::get('/pesanan', [AdminOrderReviewController::class, 'index'])->name('pesanan');
    Route::get('/pesanan-detail/{orderItem}', [AdminOrderReviewController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan/{orderItem}/{action}', [AdminOrderReviewController::class, 'decide'])
        ->whereIn('action', ['approve', 'reject'])->name('pesanan.decide');
    Route::get('/keberatan', [AdminOrderReviewController::class, 'objections'])->name('keberatan');
    Route::get('/keberatan-detail/{orderObjection}', [AdminOrderReviewController::class, 'showObjection'])->name('keberatan.detail');
    Route::post('/keberatan/{orderObjection}/{action}', [AdminOrderReviewController::class, 'decideObjection'])
        ->whereIn('action', ['approve', 'reject'])->name('keberatan.decide');

    // BE-6: invoice versions, payment proofs, refund intake, company accounts,
    // and one-time provider payouts. Refund decisions remain deferred.
    Route::post('/acara/{event}/tagihan', [AdminInvoiceController::class, 'issue'])->name('tagihan.issue');
    Route::get('/pembayaran', [AdminPaymentController::class, 'index'])->name('pembayaran');
    Route::get('/pembayaran/{invoice}', [AdminPaymentController::class, 'show'])->name('pembayaran.detail');
    Route::post('/pembayaran-bukti/{payment}/{action}', [AdminPaymentController::class, 'decide'])
        ->whereIn('action', ['approve', 'reject'])->name('pembayaran.decide');
    Route::get('/pembayaran-bukti/{payment}/file', [AdminPaymentController::class, 'proof'])->name('pembayaran.bukti');
    Route::get('/pembatalan', [AdminRefundRequestController::class, 'index'])->name('pembatalan');
    Route::get('/pembatalan/{refundRequest}', [AdminRefundRequestController::class, 'show'])->name('pembatalan.detail');
    Route::get('/rekening', [CompanyBankAccountController::class, 'index'])->name('rekening');
    Route::get('/rekening-form', [CompanyBankAccountController::class, 'create'])->name('rekening.create');
    Route::get('/rekening-form/{companyBankAccount}', [CompanyBankAccountController::class, 'edit'])->name('rekening.edit');
    Route::post('/rekening', [CompanyBankAccountController::class, 'store'])->name('rekening.store');
    Route::put('/rekening/{companyBankAccount}', [CompanyBankAccountController::class, 'update'])->name('rekening.update');
    Route::post('/rekening/{companyBankAccount}/status', [CompanyBankAccountController::class, 'toggle'])->name('rekening.status');
    Route::get('/pencairan', [PayoutController::class, 'index'])->name('pencairan');
    Route::get('/pencairan/{orderItem}', [PayoutController::class, 'show'])->name('pencairan.detail');
    Route::post('/pencairan/{orderItem}', [PayoutController::class, 'store'])->name('pencairan.store');
    Route::get('/pencairan-bukti/{payout}', [PayoutController::class, 'proof'])->name('pencairan.bukti');

    // BE-7: Admin closes completed events and monitors the shared rundown.
    Route::get('/acara', [EventCompletionController::class, 'index'])->name('acara');
    Route::get('/acara/{event}', [EventCompletionController::class, 'show'])->name('acara.detail');
    Route::post('/acara/{event}/selesai', [EventCompletionController::class, 'complete'])->name('acara.selesai');
    Route::get('/rundown', [AdminRundownController::class, 'index'])->name('rundown');

    Route::get('/{page}', function ($page) {
        $view = 'admin.'.$page;
        if (view()->exists($view)) {
            return view($view);
        }
        abort(404);
    })->where('page', '[a-zA-Z0-9_-]+')->name('page');
});
