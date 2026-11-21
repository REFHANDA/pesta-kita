<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\CancellationController as AdminCancellationController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\CompanyBankAccountController as AdminCompanyBankAccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisbursementController as AdminDisbursementController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderReviewController as AdminOrderReviewController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PriceChangeController as AdminPriceChangeController;
use App\Http\Controllers\Admin\ProviderBankAccountController as AdminProviderBankAccountController;
use App\Http\Controllers\Admin\ProviderVerificationController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Klien\CatalogController as KlienCatalogController;
use App\Http\Controllers\Klien\DashboardController as KlienDashboardController;
use App\Http\Controllers\Klien\EvaluationController as KlienEvaluationController;
use App\Http\Controllers\Klien\EventController as KlienEventController;
use App\Http\Controllers\Klien\InvoiceController as KlienInvoiceController;
use App\Http\Controllers\Klien\RecommendationController as KlienRecommendationController;
use App\Http\Controllers\Klien\RoomLayoutController as KlienRoomLayoutController;
use App\Http\Controllers\Klien\RundownController as KlienRundownController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\BankAccountController as ProviderBankAccountController;
use App\Http\Controllers\Provider\ProfilePageController;
use App\Http\Controllers\ProviderDisbursementController;
use App\Http\Controllers\Vendor\CatalogItemController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PriceChangeController as VendorPriceChangeController;
use App\Http\Controllers\Vendor\RundownController as VendorRundownController;
use App\Http\Controllers\Venue\DashboardController as VenueDashboardController;
use App\Http\Controllers\Venue\OrderController as VenueOrderController;
use App\Http\Controllers\Venue\PriceChangeController as VenuePriceChangeController;
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

    // Wiring acara Klien: buat konsep draft, pemesanan, dan pengajuan acara
    Route::get('/acara', [KlienEventController::class, 'index'])->name('acara');
    Route::redirect('/riwayat', '/klien/acara?tab=riwayat')->name('riwayat');
    Route::get('/acara-baru', [KlienEventController::class, 'create'])->name('acara.baru');
    Route::post('/acara', [KlienEventController::class, 'store'])->name('acara.store');
    Route::get('/acara/{event}/edit', [KlienEventController::class, 'edit'])->name('acara.edit');
    Route::put('/acara/{event}', [KlienEventController::class, 'update'])->name('acara.update');
    Route::delete('/acara/{event}', [KlienEventController::class, 'destroy'])->name('acara.destroy');
    Route::post('/acara/{event}/pesanan', [KlienEventController::class, 'addItem'])->name('acara.pesanan.store');
    Route::delete('/acara/{event}/pesanan/{orderItem}', [KlienEventController::class, 'removeItem'])->name('acara.pesanan.destroy');
    Route::post('/acara/{event}/ajukan', [KlienEventController::class, 'submit'])->name('acara.ajukan');
    Route::post('/acara/{event}/batalkan', [KlienEventController::class, 'cancel'])->name('acara.batalkan');
    Route::post('/acara/{event}/pembatalan', [KlienEventController::class, 'requestCancellation'])->name('acara.pembatalan.store');

    // Tahap C2: rundown hidup di dalam detail acara (tanpa menu baru).
    Route::get('/acara/{event}/rundown', [KlienRundownController::class, 'index'])->name('acara.rundown.index');
    Route::post('/acara/{event}/rundown', [KlienRundownController::class, 'store'])->name('acara.rundown.store');
    Route::put('/acara/{event}/rundown/{rundownItem}', [KlienRundownController::class, 'update'])->name('acara.rundown.update');
    Route::delete('/acara/{event}/rundown/{rundownItem}', [KlienRundownController::class, 'destroy'])->name('acara.rundown.destroy');

    // Tahap D2: susunan ruangan pilihan klien (enum tetap).
    Route::put('/acara/{event}/susunan-ruangan', [KlienRoomLayoutController::class, 'update'])->name('acara.layout.update');

    // Tahap D1: rekomendasi paket untuk acara draft (bagian di detail acara).
    Route::get('/acara/{event}/rekomendasi', [KlienRecommendationController::class, 'show'])->name('acara.rekomendasi');

    // Tahap C4: evaluasi satu kali final di dalam detail acara.
    Route::get('/acara/{event}/evaluasi', [KlienEvaluationController::class, 'index'])->name('acara.evaluasi.index');
    Route::post('/acara/{event}/evaluasi', [KlienEvaluationController::class, 'store'])->name('acara.evaluasi.store');

    // Halaman status per acara + detail satu acara nyata milik akun login.
    Route::get('/acara-detail/{event}', [KlienEventController::class, 'show'])->name('acara.detail');
    Route::get('/acara-draft', [KlienEventController::class, 'draft'])->name('acara.draft');
    Route::get('/acara-diajukan', [KlienEventController::class, 'diajukan'])->name('acara.diajukan');
    Route::get('/acara-terkonfirmasi', [KlienEventController::class, 'terkonfirmasi'])->name('acara.terkonfirmasi');
    Route::get('/acara-dibatalkan', [KlienEventController::class, 'dibatalkan'])->name('acara.dibatalkan');

    // FASE 5: Tagihan klien, bayar / upload bukti transfer, dan cetak HTML (print-friendly)
    Route::get('/tagihan/{invoice}', [KlienInvoiceController::class, 'show'])->name('tagihan.show');
    Route::post('/tagihan/{invoice}/bayar', [KlienInvoiceController::class, 'pay'])->name('tagihan.pay');
    Route::get('/tagihan/{invoice}/cetak', [KlienInvoiceController::class, 'print'])->name('tagihan.print');
    Route::get('/tagihan/{invoice}/nota', [KlienInvoiceController::class, 'receipt'])->name('tagihan.receipt');

    // Halaman statis bernama (pengganti catch-all /{page}).
    Route::get('/notifikasi', [NotificationController::class, 'index'])->defaults('portal', 'klien')->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->defaults('portal', 'klien')->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->defaults('portal', 'klien')->name('notifikasi.buka');
    Route::view('/profil', 'klien.profil')->name('profil');
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

    // BE-3 (B14): halaman verifikasi penyedia.
    Route::get('/verifikasi', [VerificationController::class, 'show'])->name('verifikasi');

    // Pengajuan harga.
    Route::get('/pengajuan-harga', [VendorPriceChangeController::class, 'index'])->name('pengajuan-harga');
    Route::post('/pengajuan-harga', [VendorPriceChangeController::class, 'store'])->name('pengajuan-harga.store');

    // FASE 4: pesanan vendor (read-only) + ajukan keberatan (sebelum catch-all).
    Route::get('/pesanan', [VendorOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{orderItem}', [VendorOrderController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan-detail/{orderItem}', [VendorOrderController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan/{orderItem}/keberatan', [VendorOrderController::class, 'storeObjection'])->name('pesanan.keberatan.store');

    // Halaman statis bernama (pengganti catch-all /{page}).
    Route::get('/notifikasi', [NotificationController::class, 'index'])->defaults('portal', 'vendor')->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->defaults('portal', 'vendor')->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->defaults('portal', 'vendor')->name('notifikasi.buka');

    // Tahap G1: profil usaha + rekening pencairan (diisi penyedia, diverifikasi Admin).
    // Nama route sengaja tidak bentrok dengan admin.rekening.* (rekening perusahaan).
    Route::get('/profil', ProfilePageController::class)->name('profil');
    Route::post('/rekening', [ProviderBankAccountController::class, 'store'])->name('rekening.simpan');

    // Tahap C3: rundown acara untuk pesanan vendor (tulis hanya item sendiri).
    Route::get('/rundown', [VendorRundownController::class, 'index'])->name('rundown');
    Route::get('/rundown/{event}', [VendorRundownController::class, 'show'])->name('rundown.show');
    Route::post('/rundown/{event}', [VendorRundownController::class, 'store'])->name('rundown.store');
    Route::put('/rundown/{event}/{rundownItem}', [VendorRundownController::class, 'update'])->name('rundown.update');
    Route::delete('/rundown/{event}/{rundownItem}', [VendorRundownController::class, 'destroy'])->name('rundown.destroy');
    Route::get('/pencairan', [ProviderDisbursementController::class, 'index'])->name('pencairan');
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
    Route::post('/data/{venue}/ruangan', [VenueRoomController::class, 'store'])->name('ruangan.store');
    Route::put('/ruangan/{room}', [VenueRoomController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{room}', [VenueRoomController::class, 'destroy'])->name('ruangan.destroy');

    // Tahap D2: denah tata ruang per ruangan (maks 3, hanya pemilik venue).
    Route::post('/ruangan/{room}/denah', [MediaController::class, 'storeRoomLayout'])->name('ruangan.denah.store');

    // BE-3 (B14): halaman verifikasi penyedia.
    Route::get('/verifikasi', [VerificationController::class, 'show'])->name('verifikasi');

    // Pengajuan harga venue.
    Route::get('/pengajuan-harga', [VenuePriceChangeController::class, 'index'])->name('pengajuan-harga');
    Route::post('/pengajuan-harga', [VenuePriceChangeController::class, 'store'])->name('pengajuan-harga.store');

    // FASE 4: pesanan venue (read-only) + ajukan keberatan (sebelum catch-all).
    Route::get('/pesanan', [VenueOrderController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{orderItem}', [VenueOrderController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan-detail/{orderItem}', [VenueOrderController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan/{orderItem}/keberatan', [VenueOrderController::class, 'storeObjection'])->name('pesanan.keberatan.store');

    // Halaman statis bernama (pengganti catch-all /{page}).
    Route::get('/notifikasi', [NotificationController::class, 'index'])->defaults('portal', 'venue')->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->defaults('portal', 'venue')->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->defaults('portal', 'venue')->name('notifikasi.buka');

    // Tahap G1: profil usaha + rekening pencairan (diisi penyedia, diverifikasi Admin).
    Route::get('/profil', ProfilePageController::class)->name('profil');
    Route::post('/rekening', [ProviderBankAccountController::class, 'store'])->name('rekening.simpan');

    // Tahap C3: rundown acara hanya lihat untuk pemilik venue.
    Route::get('/rundown', [VenueRundownController::class, 'index'])->name('rundown');
    Route::get('/rundown/{event}', [VenueRundownController::class, 'show'])->name('rundown.show');
    Route::get('/pencairan', [ProviderDisbursementController::class, 'index'])->name('pencairan');
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

    // Tahap G1: keputusan Admin atas rekening pencairan penyedia.
    Route::post('/rekening-penyedia/{bankAccount}/setujui', [AdminProviderBankAccountController::class, 'approve'])
        ->name('rekening-penyedia.approve');
    Route::post('/rekening-penyedia/{bankAccount}/tolak', [AdminProviderBankAccountController::class, 'reject'])
        ->name('rekening-penyedia.reject');

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

    // FASE 4: Review pesanan Admin, keputusan keberatan, dan buat ulang tagihan (sebelum catch-all).
    Route::get('/pesanan', [AdminOrderReviewController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{orderItem}', [AdminOrderReviewController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan-detail/{orderItem}', [AdminOrderReviewController::class, 'show'])->name('pesanan.detail');
    Route::post('/pesanan/{orderItem}/setujui', [AdminOrderReviewController::class, 'approve'])->name('pesanan.approve');
    Route::post('/pesanan/{orderItem}/tolak', [AdminOrderReviewController::class, 'reject'])->name('pesanan.reject');
    Route::post('/pesanan/{orderItem}/alihkan', [AdminOrderReviewController::class, 'reassign'])->name('pesanan.reassign');
    Route::post('/keberatan/{objection}/putuskan', [AdminOrderReviewController::class, 'decideObjection'])->name('keberatan.decide');
    Route::post('/tagihan/{invoice}/buat-ulang', [AdminOrderReviewController::class, 'reissueInvoice'])->name('tagihan.reissue');

    // FASE 5: Validasi pembayaran oleh Admin dan cetak dokumen
    Route::get('/pembayaran', [AdminPaymentController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/{invoice}', [AdminPaymentController::class, 'show'])->name('pembayaran.show');
    Route::post('/pembayaran/{payment}/validasi', [AdminPaymentController::class, 'validatePayment'])->name('pembayaran.validate');
    Route::get('/tagihan/{invoice}/cetak', [AdminPaymentController::class, 'print'])->name('tagihan.print');
    Route::get('/tagihan/{invoice}/nota', [AdminPaymentController::class, 'receipt'])->name('tagihan.receipt');

    // Pengaturan: parameter sistem + tab rekening perusahaan.
    Route::get('/pengaturan', [AdminSettingController::class, 'edit'])->name('pengaturan');
    Route::put('/pengaturan', [AdminSettingController::class, 'update'])->name('pengaturan.update');
    Route::get('/rekening', [AdminCompanyBankAccountController::class, 'index'])->name('rekening.index');
    Route::get('/rekening-form', [AdminCompanyBankAccountController::class, 'create'])->name('rekening.create');
    Route::post('/rekening', [AdminCompanyBankAccountController::class, 'store'])->name('rekening.store');
    Route::get('/rekening/{rekening}/edit', [AdminCompanyBankAccountController::class, 'edit'])->name('rekening.edit');
    Route::put('/rekening/{rekening}', [AdminCompanyBankAccountController::class, 'update'])->name('rekening.update');
    Route::match(['post', 'patch'], '/rekening/{rekening}/toggle', [AdminCompanyBankAccountController::class, 'toggle'])->name('rekening.toggle');
    Route::delete('/rekening/{rekening}', [AdminCompanyBankAccountController::class, 'destroy'])->name('rekening.destroy');

    // Halaman statis bernama (pengganti catch-all /{page}).
    Route::get('/notifikasi', [NotificationController::class, 'index'])->defaults('portal', 'admin')->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->defaults('portal', 'admin')->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->defaults('portal', 'admin')->name('notifikasi.buka');
    Route::view('/profil', 'admin.profil')->name('profil');
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('pengguna');
    Route::get('/acara', [AdminEventController::class, 'index'])->name('acara');
    Route::get('/acara/{event}', [AdminEventController::class, 'show'])->name('acara.detail');
    Route::post('/acara/{event}/selesai', [AdminEventController::class, 'complete'])->name('acara.selesai');
    Route::get('/pembatalan', [AdminCancellationController::class, 'index'])->name('pembatalan');
    Route::get('/pembatalan/{cancellation}', [AdminCancellationController::class, 'show'])->name('pembatalan.show');
    Route::post('/pembatalan/{cancellation}/tinjau', [AdminCancellationController::class, 'review'])->name('pembatalan.review');
    Route::post('/pembatalan/{cancellation}/tolak', [AdminCancellationController::class, 'reject'])->name('pembatalan.reject');
    Route::post('/pembatalan/{cancellation}/setujui', [AdminCancellationController::class, 'approve'])->name('pembatalan.approve');
    Route::post('/pembatalan/{cancellation}/proses-refund', [AdminCancellationController::class, 'startRefund'])->name('pembatalan.start-refund');
    Route::post('/pembatalan/{cancellation}/refund-selesai', [AdminCancellationController::class, 'completeRefund'])->name('pembatalan.complete-refund');
    Route::get('/pencairan', [AdminDisbursementController::class, 'index'])->name('pencairan');
    Route::post('/pencairan/{disbursement}/catat-transfer', [AdminDisbursementController::class, 'record'])->name('pencairan.record');
});
