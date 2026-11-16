# BE-1 IMPLEMENTATION REPORT — PESTA KITA

**Authentication, Authorization, Role Management & Limited Frontend Wiring**

| Informasi     | Detail                                                                                      |
| ------------- | ------------------------------------------------------------------------------------------- |
| Tanggal       | 16 November 2026                                                                            |
| Tahap         | Batch 1 — BE-1 (Gate 1 kode disetujui; Gate 2 database disetujui dan dilaksanakan)          |
| Status        | **Selesai — terverifikasi** (kode, tes, QC, dan Gate 2)                                     |
| Branch / HEAD | `master` @ `50bb69f` (tidak ada commit baru; seluruh perubahan di working tree)             |
| Lingkungan    | Laravel 13.40.0 · PHP 8.4.4 · Composer 2.9.1 · PHPUnit 12 · MySQL 127.0.0.1:3306            |
| Acuan         | Handover BE-1, `docs/BACKEND_*.md`, `docs/reports/Batch_1_Preparation_Report_Pesta_Kita.md` |

---

## 1. Executive Summary

BE-1 diimplementasikan penuh sesuai handover dan rencana teknis Batch 1:

- **Autentikasi sesi web** (login, register, logout) tanpa package/guard baru; rate limit login 5 percobaan/menit per email+IP; pesan kesalahan generik.
- **Otorisasi portal** via middleware `EnsureRole` (alias `role`): seluruh halaman keempat portal (termasuk catch-all `/{page}`) dilindungi `auth` + role; guest → `/login`, role salah → 403, akun nonaktif → logout paksa + redirect.
- **Skema pengguna**: kolom `role` (indexed, NOT NULL), `phone` (nullable), `status` (default `aktif`) pada `users`; tiga tabel profil (`client_profiles`, `vendor_profiles`, `venue_profiles`); enum `Role`, `UserStatus`, `VerificationStatus`.
- **Keputusan disetujui diterapkan**: T1 (redirect login ditentukan role database, tab role hanya visual), T2 (akun vendor/venue baru langsung `aktif` dengan verifikasi bisnis `menunggu`, `verified_at=null`), B18 (demo bar hanya environment `local`, bukan bypass auth).
- **Wiring frontend terbatas** pada 10 file yang diizinkan (auth/login, auth/register, 3 login portal, 4 halaman profil, partials/demo-bar). Layout, CSS, `aksi.js`, sidebar, dan halaman lain tidak disentuh.
- **58 test lulus (616 assertions)** mencakup seluruh matriks pengujian §8 handover.
- **Gate 2 dilaksanakan dan terverifikasi**: database `pesta_kita` dibuat (utf8mb4/utf8mb4_unicode_ci), 7 migrasi berjalan (3 baseline + 4 BE-1), seeder demo lokal idempoten, data diverifikasi.

Tidak ada perubahan dependensi, tidak ada perubahan `.env`, tidak ada commit/push, tidak ada screenshot. Seluruh perubahan lokal pra-BE-1 dipertahankan.

## 2. Repository Baseline

Fakta terverifikasi sebelum implementasi (audit read-only):

| Item          | Kondisi baseline                                                                                                                          |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Branch / HEAD | `master` @ `50bb69f`                                                                                                                      |
| Working tree  | Kotor: perubahan frontend Tahap 1–6 + instalasi Boost sebelumnya (104 modified / 22 deleted / 39 untracked menurut laporan persiapan)     |
| Backend auth  | Belum ada: hanya route GET portal generik `/{page}` + `view()->exists()`, tanpa middleware auth/role                                      |
| Database      | MySQL aktif di 127.0.0.1:3306; database `pesta_kita` **belum ada**; hanya migration baseline Laravel                                      |
| Test baseline | `php artisan test --compact` → 4 lulus (240 assertions)                                                                                   |
| Dependensi    | `laravel/boost` (dev) dan `laravel/pint` (dev) sudah terpasang di working tree sebelum sesi ini; tidak ada perubahan dependensi dilakukan |
| Konfigurasi   | Locale `id`; tidak ada config cache; `phpunit.xml` memakai SQLite `:memory:`, `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, cache/session array   |

Kondisi akhir working tree: **110 modified / 22 deleted / 53 untracked**. Selisih terhadap baseline seluruhnya berasal dari pekerjaan BE-1 (5 file backend tracked diubah + 14 entri untracked baru; file view yang di-wiring sudah termasuk himpunan modified baseline). Tidak ada perubahan baseline yang hilang atau tertimpa.

## 3. Documentation Compliance

Dokumen wajib dibaca sebelum implementasi:

- `docs/AI_AGENT_CONTEXT.md` (aturan bisnis, batasan teknis, protokol sesi)
- `docs/BACKEND_AGENT_CONTEXT.md`, `docs/BACKEND_BUSINESS_RULES.md` (termasuk section E keputusan Batch 1), `docs/BACKEND_DATABASE_DESIGN.md`, `docs/BACKEND_API_CONTRACT.md`, `docs/BACKEND_IMPLEMENTATION_PLAN.md`
- `docs/reports/Batch_1_Preparation_Report_Pesta_Kita.md` (rencana teknis §B, tabel wiring §D, SQL Gate 2 §E)
- `docs/reports/BE-0_Audit_Report_Pesta_Kita.md`

Kepatuhan:

- Urutan kerja handover diikuti: baca dokumen → audit repo/git → implementasi → tes → verifikasi → Gate 2 → laporan.
- Keputusan T1/T2/B18 dan aturan terkunci (A13: role admin tidak dapat didaftarkan publik) diterapkan apa adanya.
- B4/B15/B6 dicatat; tidak ada dampak skema BE-1 (sesuai keputusan).
- Laravel Boost dipertahankan; instruksi instalasi ulang Boost pada `AGENTS.md` dilewati karena Boost sudah terpasang dan handover melarang perubahan dependensi (konflik diselesaikan sesuai prioritas aturan).
- `docs/AI_AGENT_CONTEXT.md` diperbarui (section 6.13, riwayat 9.5, section 14).

## 4. Implemented Features

### 4.1 Login (`POST /login`)

- Validasi email+password wajib; `Auth::attempt` dengan opsi remember.
- Rate limit 5 percobaan/menit, kunci `Str::transliterate(Str::lower(email).'|'.ip)`; event `Lockout`; pesan lockout berbahasa Indonesia dengan sisa detik.
- Akun `nonaktif` ditolak dengan pesan generik **identik** dengan password salah ("Email atau kata sandi salah.") — tidak membocorkan keberadaan/status akun.
- Sukses: rate limiter dibersihkan, session di-regenerate, redirect ke portal sesuai **role database** (T1). Input `role` apa pun dari form diabaikan server.

### 4.2 Register (`POST /register`)

- Hanya role `klien`, `vendor`, `venue`; `admin` dan role tak dikenal ditolak server (validasi `Rule::in`).
- Validasi: name wajib, email unik, phone nullable, password min 8 + confirmed; vendor/venue wajib `business_name`+`address`; vendor wajib `category` dari 5 pilihan form (Katering & F&B, Dekorasi Pesta, Sound System & Audio, MC & Hiburan, Fotografi & Dokumentasi).
- User + profil dibuat dalam satu `DB::transaction`; kegagalan pembuatan profil me-rollback user (dibuktikan test).
- T2: user baru `status=aktif`; profil provider `verification_status=menunggu`, `verified_at=null`.
- Auto-login + session regenerate; redirect klien → `/klien`, vendor → `/vendor/verifikasi`, venue → `/venue/verifikasi`.

### 4.3 Logout (`POST /logout`)

- `Auth::guard('web')->logout()`, session invalidate, regenerasi CSRF token, redirect `/login`. Hanya POST (GET → 405). Form logout tersedia di keempat halaman profil.

### 4.4 Otorisasi portal (`EnsureRole`, alias `role`)

- Terdaftar di `bootstrap/app.php`; `redirectGuestsTo('/login')`; `redirectUsersTo` closure berbasis `role->portalPath()`.
- Semua route portal (beranda + catch-all) memakai `['auth', 'role:<portal>']`.
- Guest → redirect `/login`; role tidak sesuai → 403; akun nonaktif di tengah sesi → logout paksa, session invalidate + regenerasi token, redirect `/login` dengan pesan "Akun Anda tidak aktif. Hubungi Admin Pesta Kita.".

### 4.5 Profil & kata sandi (`POST /profil`, `POST /profil/sandi`)

- Kepemilikan data selalu dari authenticated user; input `id`/`user_id`/`email` dari klien diabaikan (email read-only, dibuktikan test).
- Klien/admin: `name`, `phone`. Vendor/venue tambahan: `business_name`, `category`, `address`, `pic_name` via `updateOrCreate` pada profil masing-masing. Field yang tidak dikirim tidak di-null-kan (`Arr::only`).
- Ganti sandi: verifikasi `current_password` via `Hash::check` (after-hook validator), sandi baru min 8 + confirmed; cast `hashed` menyimpan hash.
- Flash message sukses berbahasa Indonesia; redirect kembali ke `/<portal>/profil`.

### 4.6 Demo bar (B18)

- `partials/demo-bar.blade.php` dibungkus `@if(app()->environment('local'))`. Tidak pernah tampil di `testing`/`production` (dibuktikan test), dan bukan bypass auth (portal tetap menolak guest meski bar tampil).

### 4.7 Seeder demo lokal

- `LocalDemoSeeder` (dipanggil `DatabaseSeeder`): guard environment `local`; `firstOrCreate` 4 akun demo (admin/klien/vendor/venue@pestakita.test, sandi `password`) + profil; idempoten (dibuktikan menjalankan seed dua kali tanpa duplikat).

## 5. Files Created

| File                                                                             | Tujuan                                               |
| -------------------------------------------------------------------------------- | ---------------------------------------------------- |
| `app/Enums/Role.php`                                                             | Enum role + `portalPath()` + `label()`               |
| `app/Enums/UserStatus.php`                                                       | Enum `aktif`/`nonaktif`                              |
| `app/Enums/VerificationStatus.php`                                               | Enum `menunggu`/`terverifikasi`/`ditolak`/`nonaktif` |
| `app/Models/ClientProfile.php`                                                   | Profil klien (hasOne User)                           |
| `app/Models/VendorProfile.php`                                                   | Profil usaha vendor + status verifikasi              |
| `app/Models/VenueProfile.php`                                                    | Profil usaha venue + status verifikasi               |
| `app/Http/Middleware/EnsureRole.php`                                             | Gerbang role portal + penanganan akun nonaktif       |
| `app/Http/Controllers/Auth/LoginController.php`                                  | Login sesi + redirect role DB                        |
| `app/Http/Controllers/Auth/RegisterController.php`                               | Registrasi transactional + auto-login                |
| `app/Http/Controllers/Auth/LogoutController.php`                                 | Logout + invalidate session                          |
| `app/Http/Controllers/ProfileController.php`                                     | Update profil & kata sandi                           |
| `app/Http/Requests/Auth/LoginRequest.php`                                        | Validasi + authenticate + rate limit                 |
| `app/Http/Requests/Auth/RegisterRequest.php`                                     | Validasi registrasi + kategori vendor                |
| `app/Http/Requests/Profile/UpdateProfileRequest.php`                             | Validasi profil per role                             |
| `app/Http/Requests/Profile/UpdatePasswordRequest.php`                            | Validasi sandi + cek sandi saat ini                  |
| `database/migrations/2026_11_16_000001_add_role_phone_status_to_users_table.php` | Kolom `role` (index), `phone`, `status`              |
| `database/migrations/2026_11_16_000002_create_client_profiles_table.php`         | Tabel `client_profiles`                              |
| `database/migrations/2026_11_16_000003_create_vendor_profiles_table.php`         | Tabel `vendor_profiles`                              |
| `database/migrations/2026_11_16_000004_create_venue_profiles_table.php`          | Tabel `venue_profiles`                               |
| `database/seeders/LocalDemoSeeder.php`                                           | Akun demo lokal (guard environment)                  |
| `tests/Feature/Auth/LoginTest.php`                                               | 13 test login                                        |
| `tests/Feature/Auth/RegisterTest.php`                                            | 13 test registrasi                                   |
| `tests/Feature/Auth/LogoutTest.php`                                              | 5 test logout                                        |
| `tests/Feature/Auth/ProfileTest.php`                                             | 12 test profil/sandi                                 |
| `tests/Feature/Auth/RoleAccessTest.php`                                          | 7 test akses role/guest/nonaktif                     |
| `tests/Feature/Auth/DemoBarTest.php`                                             | 4 test demo bar per environment                      |
| `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md`                          | Laporan ini                                          |

## 6. Files Modified

| File                                               | Perubahan                                                                                                                                                       |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Models/User.php`                              | Fillable + cast enum `role`/`status` + relasi `clientProfile`/`vendorProfile`/`venueProfile`                                                                    |
| `bootstrap/app.php`                                | Alias middleware `role`, `redirectGuestsTo('/login')`, `redirectUsersTo` per role                                                                               |
| `routes/web.php`                                   | Grup guest (login/register + 3 login portal), 5 route POST eksplisit sebelum catch-all, proteksi `auth`+`role` seluruh grup portal; GET `/logout` lama dihapus  |
| `database/factories/UserFactory.php`               | Default role/status + state `klien()`/`admin()`/`vendor()`/`venue()`/`nonaktif()` (profil via `afterCreating`)                                                  |
| `database/seeders/DatabaseSeeder.php`              | Memanggil `LocalDemoSeeder` (factory user contoh lama dihapus)                                                                                                  |
| `tests/Feature/ProjectPortalPagesTest.php`         | B18: akses memakai `actingAs` user factory per role; halaman `login` portal dilewati (rute guest eksplisit)                                                     |
| `tests/Feature/ProjectPortalAccessibilityTest.php` | B18: akses memakai `actingAs` user factory per role                                                                                                             |
| `resources/views/auth/login.blade.php`             | Form POST `/login` + `@csrf` + name attrs + remember + error inline; prefill email/demo dan `handleLogin`/redirect JS dihapus; tab role tetap visual (T1)       |
| `resources/views/auth/register.blade.php`          | Form POST `/register` lengkap (old/checked/selected, error inline); `handleRegister` dihapus; hint T2 diselaraskan                                              |
| `resources/views/admin/login.blade.php`            | Form POST `/login` + error inline (markup ringkas dipertahankan)                                                                                                |
| `resources/views/klien/login.blade.php`            | Sama seperti di atas                                                                                                                                            |
| `resources/views/vendor/login.blade.php`           | Sama seperti di atas                                                                                                                                            |
| `resources/views/admin/profil.blade.php`           | Form profil → POST `/profil`; form sandi → POST `/profil/sandi`; email readonly; label role dari enum; flash alert; kartu logout                                |
| `resources/views/klien/profil.blade.php`           | Wiring profil+sandi; email readonly; flash alert; bagian keluar                                                                                                 |
| `resources/views/vendor/profil.blade.php`          | Wiring data usaha (`vendorProfile`) + sandi; email readonly; bagian bank tetap prototipe (tanpa name attrs + hint jujur); form foto tidak diubah; bagian keluar |
| `resources/views/venue/profil.blade.php`           | Wiring data usaha (`venueProfile`) + sandi; email readonly; bank & foto tetap prototipe; bagian keluar                                                          |
| `resources/views/partials/demo-bar.blade.php`      | Guard `@if(app()->environment('local'))` (B18)                                                                                                                  |
| `docs/AI_AGENT_CONTEXT.md`                         | Section 6.13 hasil BE-1, baris riwayat 9.5, pembaruan section 14                                                                                                |

## 7. Database Changes

### 7.1 Skema (migration BE-1)

- `users`: tambah `role` string NOT NULL setelah `email` + index `users_role_index`; `phone` string nullable; `status` string default `aktif`. `down()` drop index + kolom.
- `client_profiles`: `user_id` FK unique → `users` cascade delete.
- `vendor_profiles`: `user_id` FK unique cascade; `business_name`, `category`, `address` NOT NULL; `pic_name`, `service_area` nullable; `description` text nullable; `verification_status` default `menunggu`; `verified_at` timestamp nullable.
- `venue_profiles`: identik dengan vendor, `category` nullable.

### 7.2 Gate 2 — pelaksanaan dan hasil (disetujui, dilaksanakan sesi ini)

| Langkah                     | Hasil aktual                                                                                                                                                                                                                                                             |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Cek koneksi & keberadaan DB | MySQL terkoneksi; `pesta_kita` belum ada (`Unknown database 'pesta_kita'`)                                                                                                                                                                                               |
| Pembuatan DB                | `CREATE DATABASE pesta_kita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` via skrip PHP sekali pakai membaca `config('database.connections.mysql')` — **tanpa mengubah `.env`**; output: `CREATED pesta_kita`, `charset=utf8mb4 collation=utf8mb4_unicode_ci`        |
| Migrasi                     | `php artisan migrate` → 7 migration DONE (3 baseline Laravel + 4 BE-1), batch 1                                                                                                                                                                                          |
| Status migrasi              | `php artisan migrate:status` → ketujuh migration `[1] Ran`                                                                                                                                                                                                               |
| Seed                        | `php artisan db:seed` → `LocalDemoSeeder` DONE (environment local)                                                                                                                                                                                                       |
| Verifikasi data             | 4 user demo: role benar, semua `aktif`, `Hash::check('password')` valid; `client_profiles`=1; vendor "Dapur Nusantara" (Katering & F&B) & venue "Graha Melati" keduanya `menunggu`, `verified_at=null`; index users: `PRIMARY`, `users_email_unique`, `users_role_index` |
| Idempotensi seed            | `db:seed` dijalankan ulang → data identik, tanpa duplikat                                                                                                                                                                                                                |
| Tes pasca-Gate 2            | `php artisan test --compact` → 58 lulus (616 assertions)                                                                                                                                                                                                                 |

Skrip bantu Gate 2 (`scripts/gate2_create_database.php`, `scripts/gate2_verify.php`) bersifat sekali pakai dan **sudah dihapus** setelah verifikasi. Tidak ada data lama yang ditimpa (database baru).

## 8. Route and Middleware Summary

`php artisan route:list` → 23 route (19 aplikasi + 4 framework/Boost). Route aplikasi:

| Method | URI                                             | Middleware        | Nama                                         |
| ------ | ----------------------------------------------- | ----------------- | -------------------------------------------- |
| GET    | `/`                                             | web               | `landing`                                    |
| GET    | `/login`, `/register`                           | guest             | `login`, `register`                          |
| GET    | `/klien/login`, `/vendor/login`, `/admin/login` | guest             | `klien.login`, `vendor.login`, `admin.login` |
| POST   | `/login`                                        | guest             | `login.store`                                |
| POST   | `/register`                                     | guest             | `register.store`                             |
| POST   | `/logout`                                       | auth              | `logout`                                     |
| POST   | `/profil`                                       | auth              | `profil.update`                              |
| POST   | `/profil/sandi`                                 | auth              | `profil.sandi`                               |
| GET    | `/klien`, `/klien/{page}`                       | auth, role:klien  | `klien.beranda`, `klien.page`                |
| GET    | `/vendor`, `/vendor/{page}`                     | auth, role:vendor | `vendor.beranda`, `vendor.page`              |
| GET    | `/venue`, `/venue/{page}`                       | auth, role:venue  | `venue.beranda`, `venue.page`                |
| GET    | `/admin`, `/admin/{page}`                       | auth, role:admin  | `admin.beranda`, `admin.page`                |

Ketentuan kunci:

- **Route POST eksplisit didaftarkan sebelum catch-all `/{page}`** (sesuai handover) — terbukti tidak tertangkap halaman dinamis.
- **Satu deviasi yang diperlukan**: halaman login portal (`/klien/login`, `/vendor/login`, `/admin/login`) didaftarkan sebagai route GET eksplisit bermiddleware `guest` **sebelum** grup portal terproteksi. Tanpa ini, guest tidak akan pernah bisa mencapai halaman login portal karena catch-all terproteksi akan menolak mereka. Halaman ini milik guest, bukan halaman portal; `ProjectPortalPagesTest` mengecualikannya dan `LoginTest` mengujinya terpisah.
- Alias `role` → `App\Http\Middleware\EnsureRole` terdaftar di `bootstrap/app.php`.

## 9. Frontend Wiring Summary

Wiring terbatas pada 10 file yang diizinkan handover §7 (lihat tabel section 6). Prinsip yang dipertahankan:

- Layout, CSS, sidebar, `aksi.js`, `app-ui.js`, dan seluruh halaman lain **tidak disentuh**.
- Pola error mengikuti CSS yang sudah ada: `.field-error` + `role="alert"` + `aria-invalid="true"` (keselarasan.css); flash memakai `.alert` (admin.css).
- Simulasi redirect JS (`handleLogin`, `handleRegister`, prefills email demo, `targetUrls`) dihapus; tab role login tetap ada sebagai elemen visual (T1).
- Email pada halaman profil menjadi `readonly` dengan hint; nilai form berasal dari authenticated user + profilnya (`old()` dipertahankan saat validasi gagal).
- Bagian rekening bank (vendor/venue) tetap prototipe: input tanpa `name` + hint "Bagian rekening masih prototipe dan belum tersimpan ke server." (B12 terbuka). Form foto/portofolio tetap pratinjau lokal (B14 terbuka).
- Form logout ditambahkan pada keempat halaman profil (POST `/logout` + `@csrf`).
- Demo bar hanya dirender di environment `local` (B18).

## 10. Test Results

`php artisan test --compact` → **58 passed (616 assertions)**, dijalankan 3× (sebelum Pint, setelah Pint, pasca-Gate 2) dengan hasil identik.

| Suite                            | Test | Cakupan §8 handover                                                                                                                                                                                                                                                                      |
| -------------------------------- | ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Auth\LoginTest`                 | 13   | Halaman login guest; redirect pengguna login; sukses 4 role; T1 tab diabaikan; regenerasi session; password salah; email tak dikenal; pesan generik identik; akun nonaktif; rate limit 5 percobaan; pembersihan limiter; validasi wajib; remember me                                     |
| `Auth\RegisterTest`              | 13   | Klien/vendor/venue + profil + redirect; T2 (aktif + menunggu + verified_at null); admin ditolak; role tak dikenal; email duplikat; konfirmasi sandi; min 8; field usaha wajib; kategori vendor valid; rollback transaction (tabel profil dihapus → 500, users=0); pengguna login ditolak |
| `Auth\LogoutTest`                | 5    | Logout sukses; session invalidate; guest ditolak; hanya POST (GET 405); form logout di 4 halaman profil                                                                                                                                                                                  |
| `Auth\ProfileTest`               | 12   | Guest ditolak; update klien; email immutable; kepemilikan (input id/user_id orang lain diabaikan); update usaha vendor/venue; validasi field usaha; admin tanpa profil; ganti sandi sukses (Hash terbukti); sandi saat ini salah; validasi sandi baru; render 4 halaman profil           |
| `Auth\RoleAccessTest`            | 7    | Guest redirect dari semua halaman portal; **matriks penuh 4 role × 4 portal × 3 halaman (48 kombinasi)**; nonaktif di tengah sesi; factory nonaktif semua role; proteksi POST guest; 404 halaman tak dikenal; halaman publik tetap terbuka                                               |
| `Auth\DemoBarTest`               | 4    | Testing: tersembunyi; local: tampil (publik+portal); production: tersembunyi; bukan bypass auth                                                                                                                                                                                          |
| `ProjectPortalPagesTest`         | 1    | Seluruh view portal (kecuali login guest) 200 sebagai role sesuai + tanpa tautan `.html`                                                                                                                                                                                                 |
| `ProjectPortalAccessibilityTest` | 1    | Drawer keyboard/aria keempat portal sebagai role sesuai                                                                                                                                                                                                                                  |
| `ExampleTest` (Feature+Unit)     | 2    | Bawaan Laravel                                                                                                                                                                                                                                                                           |

Factory memakai state role (`klien()`, `admin()`, `vendor()`, `venue()`, `nonaktif()`) sesuai ketentuan "use factories".

## 11. Security and Authorization Verification

| Aspek                                                            | Status                 | Bukti                                                                    |
| ---------------------------------------------------------------- | ---------------------- | ------------------------------------------------------------------------ |
| Proteksi seluruh halaman portal (auth+role)                      | **Terverifikasi**      | Matriks 48 kombinasi GET (RoleAccessTest) + whole-view pages test        |
| Guest ditolak dari portal & endpoint tulis                       | **Terverifikasi**      | RoleAccessTest (GET+POST), ProfileTest, LogoutTest                       |
| Role salah → 403                                                 | **Terverifikasi**      | Matriks RoleAccessTest                                                   |
| Akun nonaktif: login ditolak & sesi aktif diputus                | **Terverifikasi**      | LoginTest + RoleAccessTest (mid-session)                                 |
| Redirect login berdasarkan role DB, bukan input form (T1)        | **Terverifikasi**      | LoginTest `test_login_redirect_ignores_role_tab_input`                   |
| Registrasi admin ditolak server (A13)                            | **Terverifikasi**      | RegisterTest                                                             |
| Rate limiting login 5/menit email+IP                             | **Terverifikasi**      | LoginTest (lockout + clear)                                              |
| Pesan error generik (tanpa bocor status akun)                    | **Terverifikasi**      | LoginTest (pesan identik)                                                |
| Session regeneration login; invalidate+regenerate token logout   | **Terverifikasi**      | LoginTest, LogoutTest; kode LogoutController/EnsureRole                  |
| CSRF pada semua form tulis                                       | **Terverifikasi**      | `@csrf` pada seluruh form wiring; test POST melewati middleware CSRF web |
| Email read-only & kepemilikan data dari sesi                     | **Terverifikasi**      | ProfileTest (email immutable, ownership)                                 |
| Sandi di-hash; ganti sandi wajib sandi saat ini                  | **Terverifikasi**      | RegisterTest/ProfileTest `Hash::check`                                   |
| Rollback transaction registrasi                                  | **Terverifikasi**      | RegisterTest (users=0 setelah kegagalan profil)                          |
| Demo bar local-only, bukan bypass                                | **Terverifikasi**      | DemoBarTest (3 environment + bypass check)                               |
| Proteksi tingkat HTTP terhadap serangan nyata (penetration test) | **Belum diverifikasi** | Di luar lingkup BE-1; hanya feature test                                 |
| Audit aksesibilitas menyeluruh / WCAG                            | **Belum diverifikasi** | Pola aksesibilitas yang ada dipertahankan; bukan audit baru              |

## 12. Remaining Issues

1. **Tautan "Daftar" pada login portal** masih menunjuk halaman prototipe terproteksi (`/klien/register`, `/vendor/registrasi-penyedia`); guest yang mengkliknya diarahkan ke `/login` (yang memiliki tautan ke `/register` publik). Dibiarkan sesuai batasan wiring ketat; perlu keputusan wiring lanjutan.
2. **Bagian rekening bank & unggah foto** pada profil vendor/venue tetap prototipe (B12/B14 terbuka) — ditandai jujur di UI.
3. **Manajemen pengguna oleh Admin** (aktivasi/nonaktif, verifikasi bisnis) belum ada UI/endpoint-nya; status saat ini hanya dapat diubah via database. Ini lingkup tahap berikutnya (verifikasi mitra/admin).
4. **Reset password & verifikasi email** belum termasuk lingkup BE-1 (kolom `email_verified_at` baseline ada tetapi tidak dipakai).
5. **Rate limiting hanya pada login** (sesuai spesifikasi); register/profil belum dibatasi.
6. **Kategori venue** bebas teks (nullable) pada update profil; registrasi venue menyimpan `category=null` (mengikuti desain form yang ada).
7. Akun demo memakai sandi `password` — **hanya untuk pengembangan lokal**, dijaga guard environment seeder.
8. Pint tidak dijalankan untuk Blade (tidak ada preset Blade); pemeriksaan Blade melalui `view:cache` + test render.

Tidak ada blocker. Tidak ada item yang diklaim selesai tanpa bukti.

## 13. Scope Compliance

| Larangan handover                                                | Kepatuhan                                                                       |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| `git reset`/`clean`/forced checkout                              | Tidak dilakukan                                                                 |
| Menimpa ~115 file frontend pra-ada                               | Dipertahankan seluruhnya (diverifikasi via `git status`/`git diff --name-only`) |
| Perubahan `.env`/kredensial                                      | Tidak ada (Gate 2 membaca config yang ada)                                      |
| Instalasi/hapus/update dependensi                                | Tidak ada (Boost & Pint sudah terpasang sebelum sesi)                           |
| Menghapus Laravel Boost                                          | Tidak                                                                           |
| Commit/push/merge/PR                                             | Tidak ada; HEAD tetap `50bb69f`                                                 |
| Screenshot                                                       | Tidak ada; verifikasi tekstual                                                  |
| Menyentuh `aksi.js`, sidebar, CSS, halaman di luar daftar wiring | Tidak (diff hanya file yang diizinkan)                                          |
| Lingkup BE-2                                                     | Tidak dikerjakan                                                                |

## 14. Git Change Summary

- HEAD: `master` @ `50bb69f` (tidak berubah).
- Working tree akhir: **110 modified / 22 deleted / 53 untracked**.
- File tracked yang diubah **oleh BE-1**: `app/Models/User.php`, `bootstrap/app.php`, `routes/web.php`, `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php`, serta 9 view wiring yang memang sudah berstatus modified sejak baseline.
- Entri untracked baru BE-1: `app/Enums/`, `app/Http/Controllers/Auth/`, `app/Http/Controllers/ProfileController.php`, `app/Http/Middleware/`, `app/Http/Requests/`, 3 model profil, 4 migration, `LocalDemoSeeder.php`, `tests/Feature/Auth/`, dan laporan ini (dalam `docs/`).
- `git diff --check` → bersih (tanpa whitespace error).
- Perubahan baseline lain (CSS, `app-ui.js`, layout, view Tahap 1–6, `composer.json`/`lock`, `CLAUDE.md`, penghapusan `penyedia/*`) tidak disentuh.

## 15. Recommendation for Gate 2 / BE-2

- **Gate 2: SELESAI dan terverifikasi** pada sesi ini (section 7.2). Database `pesta_kita` siap dipakai pengembangan lokal; akun demo tersedia; tidak ada langkah Gate 2 yang tersisa.
- **BE-1 direkomendasikan DITERIMA**: seluruh lingkup kode, tes, QC, dan Gate 2 terpenuhi dengan bukti aktual.
- **BE-2 (tahap berikutnya, chat terpisah)** — titik awal yang disarankan:
    1. Baca `docs/BACKEND_IMPLEMENTATION_PLAN.md` (BE-2) dan `docs/BACKEND_DATABASE_DESIGN.md` bagian verifikasi mitra; hormati dependensi yang tercatat di laporan persiapan.
    2. Audit ulang working tree (status dapat berubah antar sesi) sebelum mengedit.
    3. Kandidat lingkup sesuai rencana: alur verifikasi provider oleh Admin (`verification_status`, `verified_at`), manajemen status pengguna, dan kelanjutan domain katalog — **menunggu instruksi eksplisit pengguna**; jangan dimulai otomatis.
    4. Pertahankan pola BE-1: Form Request + enum + middleware `role`, factory states, pesan generik, dan pemisahan status akun vs verifikasi bisnis (T2).

---

_Laporan ini disusun berdasarkan hasil perintah yang benar-benar dijalankan pada 16 November 2026. Tidak ada hasil yang dikarang; item yang belum diverifikasi dinyatakan eksplisit pada section 11 dan 12._
