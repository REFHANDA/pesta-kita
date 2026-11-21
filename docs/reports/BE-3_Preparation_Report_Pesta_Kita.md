# BE-3 PREPARATION REPORT — PESTA KITA

**Gate 4A: Audit Repository, Analisis, dan Persiapan Implementasi**

| Informasi     | Detail                                                                                    |
| ------------- | ----------------------------------------------------------------------------------------- |
| Tanggal       | 16 November 2026                                                                          |
| Tahap         | BE-3 — persiapan (Gate 4A), read-only                                                     |
| Status        | **SELESAI — menunggu persetujuan Gate 4B**                                                |
| Branch / HEAD | `master` @ `50bb69f9a17df8b55f6e99e367aa89eff2bc3b63` (tidak ada commit baru)             |
| Lingkungan    | Laravel 13.40.0 · PHP 8.4.4 · Composer (Boost 2.10.4 dev) · PHPUnit 12.5.43 · MySQL lokal |
| Mode          | Audit read-only; satu-satunya artefak baru adalah laporan ini                             |

Keputusan bisnis **B12** (unggah media/dokumen) dan **B14** (dokumen verifikasi mitra) dari instruksi Gate 4A diperlakukan sebagai **keputusan tetap** dalam seluruh rancangan di bawah. Tidak ada kode aplikasi, database, dependency, `.env`, atau dokumen aturan bisnis yang diubah pada sesi ini.

---

## 1. Ringkasan Kondisi Repository Aktual

Fakta diverifikasi langsung terhadap repository pada sesi ini (bukan dari laporan historis):

- Monolit Laravel 13.40.0, PHP 8.4.4, Blade + CSS statis + JavaScript vanilla. Tidak ada `routes/api.php`; seluruh integrasi memakai form POST/PUT/DELETE + Blade sesuai `BACKEND_API_CONTRACT.md`.
- **BE-1 terpasang nyata**: login/register/logout sesi web, middleware `EnsureRole` (alias `role`) melindungi keempat portal termasuk catch-all `/{page}`, `ProfileController` (profil + sandi), enum `Role`/`UserStatus`/`VerificationStatus`, tiga tabel profil.
- **BE-2 terpasang nyata**: 16 migration domain non-finansial (batch `[2]`), 16 model + relasi, morph map 20 alias (`Relation::enforceMorphMap` di `AppServiceProvider`), 10 enum domain, 19 factory, `LocalDemoSeeder` idempoten local-only.
- **Belum ada** controller domain, Policy, endpoint CRUD katalog/venue, workflow verifikasi, alur media, atau fitur keuangan. `app/Http/Controllers` hanya berisi `Auth/*` (3), `ProfileController`, dan base `Controller`. `app/Http/Requests` hanya 4 Form Request BE-1. Tidak ada folder `app/Policies`.
- Seluruh halaman katalog/venue/verifikasi masih prototipe statis yang dirender catch-all `/{page}` di dalam grup `auth`+`role`.
- Laravel Boost 2.10.4 terpasang di `require-dev` (working tree, bukan bagian HEAD); dipertahankan sesuai keputusan 2026-11-16. Instruksi instalasi ulang Boost pada `AGENTS.md` dilewati karena Boost sudah ada dan Gate 4A melarang perubahan dependency.
- Ekstensi PHP: `fileinfo`, `pdo_mysql`, `pdo_sqlite` tersedia; **`gd` masih tidak aktif** (relevan untuk B12, lihat §14).

## 2. Hasil Audit Git dan Database

### 2.1 Git (dijalankan sesi ini)

| Pemeriksaan                 | Hasil aktual                                                           |
| --------------------------- | ---------------------------------------------------------------------- |
| `git branch --show-current` | `master`                                                               |
| `git rev-parse HEAD`        | `50bb69f9a17df8b55f6e99e367aa89eff2bc3b63`                             |
| `git log --oneline -5`      | 2 commit: `50bb69f` (pemisahan Vendor/Venue), `8e4af7e` (inisialisasi) |
| `git status --porcelain`    | **238 entri** perubahan (modified/deleted/untracked)                   |
| `git stash list`            | Kosong                                                                 |
| `git diff --check`          | Bersih (exit 0)                                                        |

Komposisi working tree (diverifikasi dari `git status --short`):

- **Modified (tracked)**: view keempat portal + layouts, CSS/JS aset, `routes/web.php`, `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `composer.json`/`composer.lock` (Boost), `UserFactory`, `DatabaseSeeder`, `CLAUDE.md`.
- **Deleted (tracked)**: 22 view `resources/views/penyedia/*` + `layouts/penyedia.blade.php` (pemisahan Vendor/Venue yang sudah ada sejak baseline).
- **Untracked**: seluruh artefak BE-1/BE-2 (`app/Enums/`, `app/Http/Controllers/Auth/`, `app/Http/Middleware/`, `app/Http/Requests/`, 16+3 model, 20 migration, 19 factory, `LocalDemoSeeder`, `tests/Feature/Auth/`, `tests/Feature/Database/`), `docs/`, `boost.json`, `.mcp.json`, `.claude/`, `_updates/`, view baru Tahap 5, aset CSS/JS baru, skrip.

Seluruh perubahan ini adalah pekerjaan frontend + BE-1 + BE-2 yang belum di-commit. **Tidak disentuh pada sesi ini.** Tidak ada reset/clean/checkout/commit/push/merge/PR.

### 2.2 Database (dijalankan sesi ini)

- `php artisan migrate:status` → **23 Ran**: 7 migration BE-1 batch `[1]` (3 bawaan + users role/phone/status + 3 profil) utuh, 16 migration BE-2 batch `[2]` (`2026_11_16_100001`–`100016`). Kondisi identik dengan laporan BE-2; tidak ada migration baru sejak itu.
- Koneksi default MySQL `pesta_kita` @127.0.0.1, `APP_ENV=local` (tanpa menampilkan credential).
- Tidak ada migration/seeder yang dijalankan pada sesi ini.

### 2.3 Baseline pengujian (dijalankan ulang sesi ini)

| Perintah                     | Hasil aktual                                                                                                |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `php -v`                     | PHP 8.4.4 (ZTS, VS C++ 2022, x64)                                                                           |
| `php artisan --version`      | Laravel Framework 13.40.0                                                                                   |
| `composer show --direct`     | boost 2.10.4, pint 1.32.1, phpunit 12.5.43, framework 13.40.0, pail, pao, tinker, faker, mockery, collision |
| `php artisan route:list`     | 23 route (lihat §4.3)                                                                                       |
| `php artisan migrate:status` | 23 Ran                                                                                                      |
| `php artisan test --compact` | **96 passed (704 assertions)**, 12.52s                                                                      |

Angka historis BE-2 (96/704) **terkonfirmasi ulang** sebagai baseline aktual sesi ini. Lingkungan tes tetap SQLite `:memory:` terpisah dari MySQL aplikasi (`phpunit.xml`).

## 3. Dokumen yang Berhasil Dibaca

Kesembilan dokumen wajib tersedia dan dibaca penuh pada sesi ini:

| #   | Dokumen                                                 | Status | Catatan relevan untuk BE-3                                                   |
| --- | ------------------------------------------------------- | ------ | ---------------------------------------------------------------------------- |
| 1   | `docs/AI_AGENT_CONTEXT.md`                              | Dibaca | Aturan terkunci §4, batasan §5, hasil BE-1/BE-2 §6.13–6.14                   |
| 2   | `docs/BACKEND_AGENT_CONTEXT.md`                         | Dibaca | Addendum §10 = kondisi terverifikasi BE-1+BE-2                               |
| 3   | `docs/BACKEND_BUSINESS_RULES.md`                        | Dibaca | A1–A14 terkunci; B12/B14 **masih tertulis terbuka** di dokumen (lihat §18.9) |
| 4   | `docs/BACKEND_DATABASE_DESIGN.md`                       | Dibaca | Status per kelompok tabel; skema BE-2 sudah dimigrasi                        |
| 5   | `docs/BACKEND_API_CONTRACT.md`                          | Dibaca | §4 BE-3: route usulan; §5 validasi; form POST + Blade                        |
| 6   | `docs/BACKEND_IMPLEMENTATION_PLAN.md`                   | Dibaca | BE-3 = "Katalog, profil, portofolio, kalender" (Tahap 5.1–5.3, 5.5)          |
| 7   | `docs/reports/BE-0_Audit_Report_Pesta_Kita.md`          | Dibaca | Historis; temuan storage `serve=true` dan `gd` diverifikasi ulang            |
| 8   | `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md` | Dibaca | Pola auth, middleware, wiring terbatas, sisa isu §12                         |
| 9   | `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md` | Dibaca | Skema terpasang, factory states, integrity suite                             |

Tidak ada dokumen wajib yang hilang.

**Ketidaksesuaian dokumen vs instruksi Gate 4A (dicatat, tidak diubah):**

1. `BACKEND_BUSINESS_RULES.md` masih menandai B12/B14 sebagai keputusan terbuka. Instruksi Gate 4A menyatakan keduanya **sudah disetujui**. Sesuai urutan sumber kebenaran, keputusan pengguna (instruksi) menang; pembaruan dokumen diusulkan pada §18.9, bukan dilakukan sekarang.
2. `BACKEND_IMPLEMENTATION_PLAN.md` memasukkan **kalender (5.5)** dan `BACKEND_API_CONTRACT.md` memasukkan **pengajuan harga + POST ketersediaan + tambah item ke acara draft** ke lingkup BE-3, sedangkan lingkup Gate 4A (§4 A–G) tidak menyebutkannya. Diperlakukan sebagai ambiguitas lingkup (§18.1), bukan dikerjakan diam-diam.

## 4. Struktur Backend yang Relevan

### 4.1 Inventaris `app/` (44 file PHP, diverifikasi)

- **Enums (13)**: `Role`, `UserStatus`, `VerificationStatus` (BE-1); `CatalogKind` (layanan/peralatan), `PricingMode` (tetap/perlu_penawaran), `CatalogItemStatus` (aktif/menunggu_persetujuan/nonaktif), `AvailabilityStatus` (tersedia/tidak_tersedia/belum_dikonfirmasi), `MediaVisibility` (privat/publik), `PriceChangeStatus`, `EventStatus`, `QuoteRequestStatus`, `QuoteDecisionResult`, `OrderItemStatus` (BE-2).
- **Models (20)**: `User` + 3 profil (BE-1); 16 model BE-2. `CatalogItem` dan `Venue` memakai `SoftDeletes`; `Media` memakai `#[Table('media')]`; uang cast integer; morph memakai alias pendek.
- **Controllers (5)**: `Auth/LoginController`, `Auth/RegisterController`, `Auth/LogoutController`, `ProfileController`, base `Controller`. **Belum ada controller domain.**
- **Middleware (1)**: `EnsureRole` — guest→login, akun nonaktif→logout paksa, role salah→403. Terdaftar sebagai alias `role` di `bootstrap/app.php`; `redirectGuestsTo('/login')`, `redirectUsersTo` berbasis `role->portalPath()`.
- **Form Requests (4)**: `Auth/LoginRequest` (rate limit 5/menit email+IP), `Auth/RegisterRequest` (admin ditolak, kategori vendor), `Profile/UpdateProfileRequest`, `Profile/UpdatePasswordRequest`.
- **Policies**: tidak ada. **Actions/Services**: tidak ada.
- **Provider**: `AppServiceProvider` mendaftarkan `Relation::enforceMorphMap` 20 alias (diverifikasi langsung).

### 4.2 Relasi kunci yang sudah tersedia (tidak perlu dibuat ulang)

- `VendorProfile` hasMany `CatalogItem`, morphMany `Media`/`ProviderBankAccount`; belongsTo `User`.
- `VenueProfile` hasMany `Venue`, morphMany `Media`/`ProviderBankAccount`; belongsTo `User`.
- `CatalogItem` belongsTo `VendorProfile`; morphMany `Media` (owner), `AvailabilityDay`/`PriceChangeRequest`/`QuoteRequest`/`OrderItem` (subject).
- `Venue` belongsTo `VenueProfile`; hasMany `VenueRoom`/`VenueFacility`; morphMany `Media` (owner), `PriceChangeRequest` (subject).
- `VenueRoom` belongsTo `Venue`; morphMany `AvailabilityDay`/`QuoteRequest`/`OrderItem` (subject).
- `Media` morphTo `owner` (profil/katalog/venue). `AuditLog` morphTo subject, belongsTo actor (`actor_id` nullable untuk sistem).

### 4.3 Route aktual (`php artisan route:list`, 23 route)

- Publik: `GET /` (landing), `GET /up` (health).
- Guest: `GET /login`, `/register`, `/klien/login`, `/vendor/login`, `/admin/login`; `POST /login`, `POST /register`.
- Auth: `POST /logout`, `POST /profil`, `POST /profil/sandi`.
- Portal (auth + role): `GET /klien`, `/vendor`, `/venue`, `/admin` + catch-all `GET /{portal}/{page}` (`where page [a-zA-Z0-9_-]+`, render `view()->exists()` atau 404).
- Framework: `POST _boost/browser-logs` (Boost dev), **`GET|HEAD storage/{path}` dan `PUT storage/{path}`** (local-disk serving, lihat §14 R1).

Pola wajib BE-3: route eksplisit (GET berparameter dan semua POST/PUT/DELETE) didaftarkan **sebelum** catch-all `/{page}` dalam grup portal yang sama; catch-all hanya melayani view yang belum di-wiring.

### 4.4 Storage dan filesystem (diverifikasi, bukan diasumsikan)

- `config/filesystems.php`: default `local` → root `storage/app/private`, **`serve => true`**; disk `public` → `storage/app/public` dengan URL `/storage`; `s3` placeholder. `php artisan config:show filesystems.default` → `local`.
- Route framework `storage/{path}` aktif karena `serve=true` pada environment local — **file di disk local dapat diakses tanpa auth melalui URL** selama env local. Ini belum aman untuk dokumen verifikasi B12/B14.
- Symlink `public/storage` **belum ada** (`Test-Path` → False); `storage/app/private` dan `storage/app/public` ada dan kosong.
- Tidak ada kode upload/download media apa pun di `app/`.

### 4.5 Test yang ada (14 file)

- `tests/Feature/Auth/`: LoginTest (13), RegisterTest (13), LogoutTest (5), ProfileTest (12), RoleAccessTest (7, matriks 48 kombinasi), DemoBarTest (4).
- `tests/Feature/Database/`: SchemaIntegrityTest, DomainRelationsTest, LocalDemoSeederTest (38 test, 14 kategori).
- `tests/Feature/`: ProjectPortalPagesTest (semua view role 200 dengan `actingAs`, kecuali login guest), ProjectPortalAccessibilityTest, ExampleTest; `tests/Unit/ExampleTest`.
- Factory dengan states role/provider/catalog/venue/media tersedia untuk dipakai test BE-3 (mis. `VendorProfileFactory` state `terverifikasi`, `CatalogItemFactory` state `nonaktif`/`peralatan`, `MediaFactory` state `publik`).

## 5. Pemetaan Tabel, Model, dan Relasi BE-2 yang Dipakai BE-3

| Tabel                    | Model                 | Kolom kunci (diverifikasi dari migration)                                                                                                                                                                                                                                                                                    | Dipakai untuk                                                         |
| ------------------------ | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| `users`                  | `User`                | role, phone, status                                                                                                                                                                                                                                                                                                          | Akun penyedia; phone **tidak** di-query untuk Klien                   |
| `vendor_profiles`        | `VendorProfile`       | business_name, category, address, pic_name, service_area, description, verification_status (default menunggu), verified_at                                                                                                                                                                                                   | Kepemilikan katalog; subjek verifikasi B14                            |
| `venue_profiles`         | `VenueProfile`        | sama (category nullable)                                                                                                                                                                                                                                                                                                     | Kepemilikan venue; subjek verifikasi B14                              |
| `catalog_items`          | `CatalogItem`         | vendor_profile_id FK restrict, kind, name, category, description, catalog_price (uint big), pricing_mode, max_per_day nullable, status, policies, softDeletes, index (vendor_profile_id,status,category)                                                                                                                     | CRUD katalog Vendor (layanan+peralatan, B15)                          |
| `venues`                 | `Venue`               | venue_profile_id FK restrict, name, kind (string), capacity, address, description, rules, cancellation_policy, rental_price, cleaning_fee nullable (B4), duration_hours nullable, deposit_amount nullable (B5), verification_status default menunggu, verified_at, softDeletes, index (venue_profile_id,verification_status) | CRUD Venue                                                            |
| `venue_rooms`            | `VenueRoom`           | venue_id cascade, name, capacity; unique (venue_id,name)                                                                                                                                                                                                                                                                     | Kelola ruangan; kalender per ruangan                                  |
| `venue_facilities`       | `VenueFacility`       | venue_id cascade, name; unique (venue_id,name)                                                                                                                                                                                                                                                                               | Checkbox fasilitas form venue                                         |
| `media`                  | `Media`               | morphs owner, path, visibility default privat, sort_order                                                                                                                                                                                                                                                                    | Foto katalog/venue/profil + dokumen verifikasi (perlu perluasan, §15) |
| `price_change_requests`  | `PriceChangeRequest`  | morph subject, old_price, new_price, reason, status, reviewed_by, reviewed_at                                                                                                                                                                                                                                                | Pengajuan harga (lingkup opsional, §18.1)                             |
| `availability_days`      | `AvailabilityDay`     | morph subject, date, status; unique (subject,date)                                                                                                                                                                                                                                                                           | Kalender (lingkup opsional, §18.1)                                    |
| `audit_logs`             | `AuditLog`            | actor_id nullable FK, action, morph subject, from_status, to_status, reason, ip(45)                                                                                                                                                                                                                                          | Jejak keputusan verifikasi & aksi penting                             |
| `provider_bank_accounts` | `ProviderBankAccount` | morph owner, bank_name, account_number, account_name, verification_status                                                                                                                                                                                                                                                    | **Tidak** dikelola BE-3 inti; nomor utuh hanya pemilik+Admin          |

Tabel BE-2 lain (`events`, `quote_*`, `order_*`, `company_bank_accounts`) tidak disentuh BE-3 inti; relasi morph subject katalog/venue tetap valid karena soft delete (bukan force delete) menjaga riwayat.

**Kesenjangan skema terhadap B12/B14 (terverifikasi):**

1. `media` **tidak punya** kolom pembeda jenis media (foto vs dokumen verifikasi), disk, MIME, ukuran, atau nama asli. B12 mewajibkan dua kelas media dengan aturan akses berbeda — tidak bisa dibedakan hanya dari ekstensi. Perlu migration additive (§15.1).
2. `vendor_profiles`/`venue_profiles` **tidak punya** kolom alasan penolakan/catatan verifikasi dan reviewer. B14 mewajibkan penolakan disertai alasan dan pencatatan reviewer+waktu. `verified_at` sudah ada; `audit_logs` dapat memuat riwayat, tetapi UI penyedia perlu alasan terkini. Perlu migration additive (§15.2) — alternatif tanpa kolom baru dibahas di §18.4.
3. `catalog_items.status` sudah memuat `menunggu_persetujuan`, tetapi alur persetujuan per item belum diputuskan (§18.2). `venues.verification_status` ada per venue; semantik pengisiannya belum diputuskan (§18.3).

## 6. Pemetaan Kebutuhan Halaman Frontend

Kondisi aktual view (diverifikasi terarah; semua masih statis di dalam catch-all terproteksi):

### 6.1 Portal Vendor

| Halaman                        | Kondisi aktual                                                                                                                                                       | Kebutuhan backend BE-3                                                                                                   |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `vendor/layanan`               | Daftar layanan statis + badge status                                                                                                                                 | Query `catalog_items` kind=layanan milik sendiri; aksi ubah/nonaktif/hapus                                               |
| `vendor/peralatan`             | Daftar peralatan statis                                                                                                                                              | Query kind=peralatan milik sendiri                                                                                       |
| `vendor/item-form`             | Field: nama, kategori (3 opsi), kuota/hari, deskripsi, harga, alasan perubahan harga; tanpa `name` attrs, non-submit                                                 | Store/update katalog; harga baru lewat pengajuan harga bila diaktifkan; `kind` dari konteks halaman                      |
| `vendor/peralatan-form`        | Form peralatan terpisah (UI)                                                                                                                                         | Tetap satu tabel `catalog_items` (B15); form berbeda hanya presentasi                                                    |
| `vendor/verifikasi`            | **Memuat konten venue** ("Graha Melati · Pemilik venue", bukti kepemilikan venue wajib, bukti rekening wajib); teks "Akun aktif setelah seluruh pemeriksaan selesai" | Status verifikasi nyata + unggah dokumen B14 vendor (KTP wajib, NIB bila ada, NPWP opsional); teks harus diselaraskan T2 |
| `vendor/profil`                | Data usaha sudah wired BE-1; **foto & bank masih prototipe**                                                                                                         | Unggah/kelola foto profil (B12); bank di luar lingkup inti                                                               |
| `vendor/pengajuan-harga`       | Statis                                                                                                                                                               | Opsional BE-3 (§18.1)                                                                                                    |
| `vendor/kalender-ketersediaan` | Prototipe `kalender.js` + `#kal-config`                                                                                                                              | Opsional BE-3 (§18.1)                                                                                                    |

### 6.2 Portal Venue

| Halaman            | Kondisi aktual                                                                                                                                                                   | Kebutuhan backend BE-3                                                                                                                                                                     |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `venue/data`       | Daftar venue statis                                                                                                                                                              | Query venue milik sendiri + ruangan                                                                                                                                                        |
| `venue/form`       | Field: nama, jenis (6 opsi), kapasitas, alamat, deskripsi, harga sewa + alasan, **kebersihan read-only "hanya diubah Admin"**, durasi, deposit, fasilitas, ketentuan; non-submit | Store/update venue; `cleaning_fee` **ditolak dari input pemilik** (B4 + bukti UI); deposit disimpan nullable tanpa logika (B5); fasilitas sync; ruangan dikelola di form/halaman yang sama |
| `venue/verifikasi` | KTP wajib, bukti kepemilikan wajib, bukti rekening wajib; NPWP tidak ada di UI                                                                                                   | Status nyata + unggah dokumen B14 venue; tambah field NPWP opsional                                                                                                                        |
| `venue/profil`     | Data usaha wired BE-1; foto & bank prototipe                                                                                                                                     | Foto profil (B12)                                                                                                                                                                          |

### 6.3 Portal Admin

| Halaman                                            | Kondisi aktual                                                                                                                                                                            | Kebutuhan backend BE-3                                                                      |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `admin/admin-penyedia`                             | Antrean verifikasi statis (statistik, filter jenis/status, tabel "Tinjau" → `#detail-verifikasi`); **target anchor `#detail-verifikasi` dan panel setujui/tolak tidak ditemukan di file** | Daftar pengajuan nyata (filter status/jenis), detail dokumen, approve/reject + alasan wajib |
| `admin/mitra-detail`                               | Profil usaha statis; tombol nonaktifkan akun (dialog); **tanpa UI dokumen/keputusan verifikasi**                                                                                          | Detail penyedia + dokumen privat (akses Admin), keputusan verifikasi, catatan               |
| `admin/admin-venue-form`                           | Form kelola venue oleh Admin (statis)                                                                                                                                                     | Tulis `cleaning_fee` (B4, hanya Admin) dan status venue bila diputuskan (§18.3)             |
| `admin/pengguna`/`pengguna-detail`/`pengguna-form` | Statis                                                                                                                                                                                    | Manajemen status akun (T2) — kandidat lingkup pendukung, lihat §18.1                        |
| `admin/aktivitas`                                  | Statis                                                                                                                                                                                    | Baca `audit_logs` (nice-to-have; inti BE-3 cukup menulis log)                               |

### 6.4 Portal Klien (visibilitas)

| Halaman                                                             | Kebutuhan backend BE-3                                                                                                                                                               |
| ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `klien/katalog-vendor`                                              | Daftar item layanan dari vendor **terverifikasi**, item aktif, tidak terhapus; filter kategori/nama/harga maks di server (JS filter lokal tetap ada sebagai progressive enhancement) |
| `klien/katalog-vendor-detail/{item}`                                | Detail item + galeri foto publik + profil penyedia; tanpa kontak pribadi; estimasi biaya (bukan tagihan final)                                                                       |
| `klien/katalog-peralatan`                                           | Sama, kind=peralatan                                                                                                                                                                 |
| `klien/katalog`                                                     | Daftar venue terverifikasi; rincian sewa + kebersihan + komisi 10% sebagai **estimasi**                                                                                              |
| `klien/katalog-venue-detail/{venue}`                                | Detail venue, ruangan, fasilitas, aturan, kebijakan, galeri publik, ketersediaan "perlu konfirmasi"                                                                                  |
| `klien/penyedia-vendor/{profile}`, `klien/penyedia-venue/{profile}` | Profil publik penyedia terverifikasi; kontak pribadi & rekening tidak bocor                                                                                                          |
| `klien/katalog-detail`                                              | Halaman generik bersama (peralatan/rekomendasi) — **tidak dirombak**; tetap statis sampai tahap relevan                                                                              |
| `klien/rekomendasi`                                                 | Di luar lingkup (B17 terbuka)                                                                                                                                                        |

Catatan wiring: mengikuti pola BE-1 — controller mengisi view yang sama, layout/CSS tidak diganti, penanda `.data-note` dilepas hanya pada halaman yang datanya sudah dari server, error inline `.field-error` + `role="alert"`, tanpa sukses palsu (`aksi.js` tetap untuk halaman yang belum POST).

## 7. Rancangan Endpoint BE-3

Konvensi: form POST/PUT/DELETE + Blade (bukan API JSON publik), CSRF wajib, middleware `auth`+`role` per portal, route eksplisit sebelum catch-all, nama route mengikuti pola `{portal}.{aksi}`. Semua tulis menolak role salah dengan 403 dan tamu dengan redirect `/login`. `{item}` = CatalogItem, `{venue}` = Venue, `{room}` = VenueRoom, `{media}` = Media, `{provider}` = pasangan morph `vendor_profile|venue_profile` + id (divalidasi terhadap morph map).

### 7.1 Katalog Vendor (portal `/vendor`, role:vendor)

| Method | URI                             | Nama route               | Perilaku                                                             |
| ------ | ------------------------------- | ------------------------ | -------------------------------------------------------------------- |
| GET    | `/vendor/layanan`               | `vendor.layanan`         | Daftar item kind=layanan milik sendiri (controller, bukan catch-all) |
| GET    | `/vendor/peralatan`             | `vendor.peralatan`       | Daftar item kind=peralatan milik sendiri                             |
| GET    | `/vendor/item-form`             | `vendor.katalog.create`  | Form tambah (kind dari query/konteks)                                |
| GET    | `/vendor/item-form/{item}`      | `vendor.katalog.edit`    | Form ubah; policy ownership                                          |
| POST   | `/vendor/katalog`               | `vendor.katalog.store`   | Buat item; `vendor_profile_id` dari sesi, bukan input                |
| PUT    | `/vendor/katalog/{item}`        | `vendor.katalog.update`  | Ubah data non-harga-final; kepemilikan divalidasi policy             |
| POST   | `/vendor/katalog/{item}/status` | `vendor.katalog.status`  | Aktifkan/nonaktifkan (transisi terbatas, §12)                        |
| DELETE | `/vendor/katalog/{item}`        | `vendor.katalog.destroy` | Soft delete; riwayat pesanan tetap                                   |

### 7.2 Venue (portal `/venue`, role:venue)

| Method | URI                           | Nama route              | Perilaku                                                                                      |
| ------ | ----------------------------- | ----------------------- | --------------------------------------------------------------------------------------------- |
| GET    | `/venue/data`                 | `venue.data`            | Daftar venue milik sendiri + jumlah ruangan                                                   |
| GET    | `/venue/form`                 | `venue.create`          | Form tambah venue                                                                             |
| GET    | `/venue/form/{venue}`         | `venue.edit`            | Form ubah; policy ownership                                                                   |
| POST   | `/venue/data`                 | `venue.store`           | Buat venue + ruangan pertama + fasilitas (transaction)                                        |
| PUT    | `/venue/data/{venue}`         | `venue.update`          | Ubah info + sync fasilitas; `cleaning_fee`/`verification_status` diabaikan dari input pemilik |
| DELETE | `/venue/data/{venue}`         | `venue.destroy`         | Soft delete venue (ruangan/fasilitas tetap, cascade hanya saat force)                         |
| POST   | `/venue/data/{venue}/ruangan` | `venue.ruangan.store`   | Tambah ruangan (unique nama per venue)                                                        |
| PUT    | `/venue/ruangan/{room}`       | `venue.ruangan.update`  | Ubah ruangan; ownership via venue→profil                                                      |
| DELETE | `/venue/ruangan/{room}`       | `venue.ruangan.destroy` | Hapus ruangan; tolak bila sudah direferensia order/quote (FK restrict)                        |

### 7.3 Media (foto & dokumen)

| Method | URI                           | Middleware                | Perilaku                                                               |
| ------ | ----------------------------- | ------------------------- | ---------------------------------------------------------------------- |
| POST   | `/vendor/katalog/{item}/foto` | auth, role:vendor         | Unggah foto katalog (B12); policy ownership; maks 10 foto/item         |
| POST   | `/venue/data/{venue}/foto`    | auth, role:venue          | Unggah foto venue; maks 10 foto/venue                                  |
| POST   | `/profil/foto`                | auth                      | Unggah foto profil penyedia (vendor/venue); maks 10/profil             |
| POST   | `/verifikasi/dokumen`         | auth, role:vendor/venue\* | Unggah dokumen verifikasi (tipe: ktp/nib/npwp/bukti_venue); private    |
| DELETE | `/media/{media}`              | auth                      | Hapus media; policy: pemilik owner atau Admin; file fisik ikut dihapus |
| GET    | `/media/{media}`              | auth                      | Satu-satunya jalur baca file: stream/download dengan otorisasi (§9.4)  |

\* route dokumen memakai `role:vendor,venue` (satu endpoint, jenis dokumen divalidasi terhadap role pemilik).

### 7.4 Verifikasi penyedia

| Method | URI                                             | Middleware              | Perilaku                                                               |
| ------ | ----------------------------------------------- | ----------------------- | ---------------------------------------------------------------------- |
| GET    | `/vendor/verifikasi`, `/venue/verifikasi`       | auth, role sesuai       | Status nyata + daftar dokumen + alasan penolakan terakhir (controller) |
| POST   | `/verifikasi/ajukan`                            | auth, role:vendor,venue | Ajukan ulang setelah ditolak/melengkapi; syarat dokumen wajib (§10)    |
| GET    | `/admin/admin-penyedia`                         | auth, role:admin        | Antrean pengajuan nyata + filter status/jenis                          |
| GET    | `/admin/penyedia/{provider}`                    | auth, role:admin        | Detail pengajuan + dokumen privat (panel `#detail-verifikasi` baru)    |
| POST   | `/admin/penyedia/{provider}/verifikasi/setujui` | auth, role:admin        | Setujui: status→terverifikasi, verified_at, reviewer, audit log        |
| POST   | `/admin/penyedia/{provider}/verifikasi/tolak`   | auth, role:admin        | Tolak: alasan wajib, status→ditolak, audit log; akun tetap aktif (T2)  |

### 7.5 Visibilitas Klien (portal `/klien`, role:klien)

| Method | URI                                   | Nama route                    | Perilaku                                                         |
| ------ | ------------------------------------- | ----------------------------- | ---------------------------------------------------------------- |
| GET    | `/klien/katalog-vendor`               | `klien.katalog.vendor`        | Query terfilter §12; filter kategori/nama/harga maks server-side |
| GET    | `/klien/katalog-vendor-detail/{item}` | `klien.katalog.vendor.detail` | 404 bila item tidak memenuhi filter publik                       |
| GET    | `/klien/katalog-peralatan`            | `klien.katalog.peralatan`     | Sama, kind=peralatan                                             |
| GET    | `/klien/katalog`                      | `klien.katalog.venue`         | Daftar venue publik                                              |
| GET    | `/klien/katalog-venue-detail/{venue}` | `klien.katalog.venue.detail`  | Detail venue + ruangan + fasilitas + foto publik                 |
| GET    | `/klien/penyedia-vendor/{profile}`    | `klien.penyedia.vendor`       | Profil publik vendor terverifikasi                               |
| GET    | `/klien/penyedia-venue/{profile}`     | `klien.penyedia.venue`        | Profil publik pemilik venue terverifikasi                        |

Detail Klien memakai id numerik pada route eksplisit (catch-all lama tidak menerima parameter). Halaman yang belum di-wiring tetap lewat catch-all. `POST tambah item ke acara draft` (API contract BE-3) bergantung acara draft Klien dan diusulkan masuk BE-4/BE-5 bersama alur quote — lihat §18.1.

## 8. Rancangan Controller dan Form Request

### 8.1 Controller (folder mengikuti pola portal yang sudah ada)

| Controller (baru)                                           | Tanggung jawab                                                                                      |
| ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `App\Http\Controllers\Vendor\CatalogItemController`         | CRUD katalog + toggle status; daftar layanan/peralatan; form create/edit                            |
| `App\Http\Controllers\Venue\VenueController`                | CRUD venue + sync fasilitas; daftar & form                                                          |
| `App\Http\Controllers\Venue\VenueRoomController`            | CRUD ruangan (ownership via venue)                                                                  |
| `App\Http\Controllers\MediaController`                      | Upload foto (katalog/venue/profil), upload dokumen verifikasi, delete, stream/download terotorisasi |
| `App\Http\Controllers\VerificationController`               | Halaman status verifikasi penyedia + ajukan ulang                                                   |
| `App\Http\Controllers\Admin\ProviderVerificationController` | Antrean, detail, setujui, tolak; tulis audit log; flip visibilitas foto (§10)                       |
| `App\Http\Controllers\Klien\CatalogController`              | Katalog vendor/peralatan/venue publik + detail + profil penyedia publik                             |

Tidak ada folder arsitektur baru (`app/Actions` dll. tidak dibuat pada BE-3 inti; transaksi uang belum ada). Logika together-guard status ditulis di controller/policy dengan `DB::transaction` bila menyentuh lebih dari satu tabel (mis. approve: profil + media + audit log).

### 8.2 Form Request (baru, `app/Http/Requests/`)

| Request                                   | Aturan kunci                                                                                                                                                                                                                                                           |
| ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Catalog/StoreCatalogItemRequest`         | kind in layanan,peralatan; name wajib; category wajib; catalog_price integer ≥ 0 (rupiah); pricing_mode in tetap,perlu_penawaran; max_per_day nullable integer ≥ 1; description/policies nullable; **menolak** field vendor_profile_id/status/verification dari input  |
| `Catalog/UpdateCatalogItemRequest`        | Sama; ownership via policy; perubahan `catalog_price` diperlakukan sesuai keputusan §18.2 (langsung vs pengajuan harga)                                                                                                                                                |
| `Catalog/ToggleCatalogStatusRequest`      | status in aktif,nonaktif (transisi dari §12)                                                                                                                                                                                                                           |
| `Venue/StoreVenueRequest`                 | name, kind (string, himpunan opsi UI), capacity integer ≥ 1, address, rental_price integer ≥ 0; cleaning_fee **ditolak** (Admin only, B4); deposit_amount nullable integer ≥ 0 tanpa logika (B5); duration_hours nullable; rooms minimal 1; facilities array nama unik |
| `Venue/UpdateVenueRequest`                | Sama + sync fasilitas; field verification_status/verified_at diabaikan                                                                                                                                                                                                 |
| `Venue/StoreVenueRoomRequest`             | name wajib unik per venue (validasi + unique DB), capacity nullable integer ≥ 1                                                                                                                                                                                        |
| `Media/UploadPhotoRequest`                | file wajib; mimes:jpg,jpeg,png,webp; max:5120 (5 MB); validasi gambar nyata (`getimagesize`); batas jumlah 10 per owner divalidasi server terhadap DB, bukan input                                                                                                     |
| `Media/UploadVerificationDocumentRequest` | file wajib; mimes:pdf,jpg,jpeg,png; max:5120; doc_type in ktp,nib,npwp,bukti_venue; **ktp wajib ada** sebelum ajukan; bukti_venue hanya role venue; nib/npwp opsional                                                                                                  |
| `Verification/SubmitVerificationRequest`  | authorize: profil milik user, status menunggu/ditolak; dokumen wajib lengkap per role (B14)                                                                                                                                                                            |
| `Admin/ReviewVerificationRequest`         | decision in setujui,tolak; **reason wajib bila tolak** (min panjang wajar); reviewer dari sesi                                                                                                                                                                         |

Semua Request memakai `authorize()` + policy; pesan validasi Bahasa Indonesia mengikuti pola BE-1; field harga final/komisi tidak pernah diterima dari Klien/penyedia (aturan A5).

### 8.3 Policy (baru, `app/Policies/`)

| Policy                  | Aturan                                                                                                                                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `CatalogItemPolicy`     | viewAny/update/delete/status: user vendor && item.vendor_profile_id == user.vendorProfile.id; view (publik): filter §12; Admin: view all                                                               |
| `VenuePolicy`           | Pola sama via venueProfile; Admin view all + tulis cleaning_fee                                                                                                                                        |
| `VenueRoomPolicy`       | Turunan ownership venue induk                                                                                                                                                                          |
| `MediaPolicy`           | view (baca file): pemilik owner ATAU Admin; **dokumen verifikasi tidak pernah untuk Klien**; foto publik boleh dibaca Klien hanya bila owner terverifikasi && visibility=publik; delete: pemilik/Admin |
| `ProviderProfilePolicy` | review/decide: Admin saja; update profil: pemilik (sudah ada di BE-1, diperluas untuk dokumen)                                                                                                         |

Registrasi policy lewat `Gate::policy` di `AppServiceProvider` (atau auto-discovery Laravel 13). Middleware `role` tetap gerbang pertama; policy menegakkan ownership di lapis kedua (defense in depth, anti-IDOR).

## 9. Rancangan Alur Upload Media dan Dokumen (B12 — keputusan tetap)

### 9.1 Dua kelas media, aturan berbeda

| Aspek              | Foto (katalog/venue/profil)                       | Dokumen verifikasi (KTP/NIB/NPWP/bukti venue)                                  |
| ------------------ | ------------------------------------------------- | ------------------------------------------------------------------------------ |
| Format             | JPG, JPEG, PNG, WebP                              | PDF, JPG, JPEG, PNG                                                            |
| Ukuran maks        | 5 MB/file                                         | 5 MB/file                                                                      |
| Jumlah maks        | 10 foto per item katalog / per venue / per profil | Tidak dibatasi jumlah; per tipe dokumen cukup 1 aktif (unggah ulang mengganti) |
| Visibility awal    | `privat`                                          | `privat` **selamanya**                                                         |
| Setelah verifikasi | dapat menjadi `publik` (flip saat approve, §10)   | tetap privat; hanya pemilik + Admin                                            |
| Jalur akses        | `GET /media/{media}` terotorisasi                 | `GET /media/{media}` terotorisasi (pemilik/Admin)                              |

Pembedaan **berdasarkan kolom `kind`/`doc_type` di tabel media** (migration §15.1), bukan ekstensi file. Dokumen identitas tidak pernah masuk direktori publik dan path internal tidak pernah dikembalikan ke API/view publik (response hanya id + URL route terotorisasi).

### 9.2 Alur upload foto

1. Form multipart POST ke endpoint owner (§7.3) dengan `@csrf`; policy ownership dicek sebelum validasi file.
2. Validasi server: MIME nyata via `fileinfo` + `getimagesize` (bukan hanya ekstensi/`accept` HTML), ukuran ≤ 5 MB, jumlah foto owner saat ini < 10 (dihitung dari DB dalam transaction + lock agar race-safe).
3. Simpan dengan nama acak (`Str::random`/`storeAs` nama hash) ke disk privat (§9.5); path relatif disimpan di `media.path`; `visibility=privat`, `sort_order` berikutnya.
4. Record `media` dibuat dalam transaction yang sama dengan penyimpanan file; gagal simpan file → rollback record.
5. Respons redirect kembali + flash sukses nyata; pratinjau lokal `profil-media.js` tetap ada tetapi hint "konfigurasi contoh" diganti batas B12 yang sudah berlaku.
6. Hapus: policy → hapus record + file fisik (`Storage::disk(...)->delete`); foto terakhir boleh dihapus (empty state jujur).

### 9.3 Alur upload dokumen verifikasi

1. POST `/verifikasi/dokumen` dengan `doc_type`; validasi format/ukuran server; role menentukan tipe yang sah (bukti_venue hanya venue).
2. Simpan ke disk privat dengan nama acak; `kind=dokumen_verifikasi`, `visibility=privat` (tidak pernah berubah).
3. Unggah ulang tipe yang sama: file lama dihapus (atau ditandai superseded — rekomendasi: ganti file lama, satu dokumen aktif per tipe) setelah file baru berhasil disimpan.
4. Dokumen hanya dapat diunduh pemilik dan Admin lewat `GET /media/{media}`; Klien mendapat 403/404 apa pun kondisinya.
5. Kelengkapan dokumen (B14) divalidasi saat `POST /verifikasi/ajukan`, bukan saat upload parsial.

### 9.4 Otorisasi akses file (satu pintu)

- Semua baca file lewat `MediaController@show` → `MediaPolicy@view`:
    - dokumen_verifikasi: pemilik owner atau Admin; selain itu 403.
    - foto: pemilik/Admin selalu; Klien hanya bila `visibility=publik` DAN owner (profil/katalog→vendorProfile/venue→venueProfile) berstatus `terverifikasi` DAN item tidak nonaktif/terhapus.
- Response stream dengan header `Content-Type` dari MIME tersimpan dan `Content-Disposition` aman; nama file asli tidak pernah menjadi path.
- **Tidak ada** URL `storage/...` atau path disk yang dibocorkan ke view/API publik.

### 9.5 Penyimpanan private (perubahan config yang diusulkan untuk Gate 4B)

- Kondisi aktual belum aman: disk `local` `serve=true` membuka route framework `storage/{path}` tanpa auth di env local (§14 R1).
- Usulan: tambah disk khusus **`private`** di `config/filesystems.php` (driver local, root `storage/app/private`, **`serve=false`**) dan pakai disk itu untuk seluruh media BE-3; atau set `serve=false` pada disk `local`. Keduanya perubahan config biasa, **tanpa mengubah `.env`** dan tanpa dependency baru. Rekomendasi: disk `private` baru agar tidak mengubah perilaku disk `local` yang mungkin dipakai fitur lain kelak.
- Foto publik tetap disajikan lewat controller terotorisasi (bukan disk `public`/symlink), sehingga aturan "privat sebelum verifikasi" dapat ditegakkan di satu pintu dan tidak ada file penyedia yang pernah berada di direktori publik.
- `gd` tidak aktif: validasi gambar memakai `getimagesize` (tidak butuh gd). Re-encoding/thumbnail **tidak** dilakukan pada BE-3; bila kelak dibutuhkan, aktivasi gd diputuskan terpisah (§14 R7).

## 10. Rancangan Workflow Verifikasi (B14 — keputusan tetap)

### 10.1 Persyaratan dokumen

| Role   | Wajib                                                    | Opsional / kondisional            |
| ------ | -------------------------------------------------------- | --------------------------------- |
| Vendor | KTP pemilik/PIC                                          | NIB/legalitas bila tersedia; NPWP |
| Venue  | KTP pemilik/PIC; bukti kepemilikan/hak pengelolaan venue | NPWP                              |

UI saat ini berbeda dari keputusan (bukti rekening wajib di kedua halaman, NPWP tidak ada, halaman vendor berkonten venue). Penyesuaian view diusulkan sebagai perubahan frontend terbatas pada Gate 4B (§18.5); backend tetap menegakkan B14 apa adanya.

### 10.2 Mesin status (profil penyedia)

```
menunggu ──(Admin setujui)──▶ terverifikasi
menunggu ──(Admin tolak + alasan)──▶ ditolak
ditolak ──(penyedia perbaiki/unggah ulang + POST /verifikasi/ajukan)──▶ menunggu
terverifikasi ──(Admin nonaktifkan, keputusan terpisah)──▶ nonaktif
```

- Status akun (`users.status`) **tidak pernah** diubah oleh keputusan verifikasi bisnis (T2). Penolakan verifikasi tidak menonaktifkan akun; penyedia tetap dapat login dan memperbaiki data.
- Transisi di luar peta ditolak server (403/422), termasuk approve ganda dan approve atas profil yang bukan `menunggu`.
- `nonaktif` (VerificationStatus) menyembunyikan katalog tanpa menghapus riwayat; penggunaannya oleh Admin dicatat sebagai keputusan terpisah (§18.3).

### 10.3 Alur lengkap

1. Registrasi (BE-1, sudah ada): akun `aktif`, profil `verification_status=menunggu`, `verified_at=null`; redirect ke `/{portal}/verifikasi`.
2. Penyedia melengkapi profil (BE-1) dan mengunggah dokumen (baru, §9.3).
3. `POST /verifikasi/ajukan`: validasi dokumen wajib per role; status tetap/ kembali `menunggu`; audit log `verification_submitted`.
4. Admin membuka antrean `GET /admin/admin-penyedia` (filter status/jenis, urut tanggal daftar) dan detail `GET /admin/penyedia/{provider}`: data usaha, kelengkapan dokumen per tipe, pratinjau/unduh dokumen privat.
5. **Setujui** (`POST .../setujui`): dalam `DB::transaction` — profil→`terverifikasi`, `verified_at=now`, reviewer dicatat; foto milik profil/katalog/venue terkait di-flip `privat→publik` (hanya kind foto, tidak pernah dokumen); venue terkait mendapat status publik sesuai keputusan §18.3; audit log `verification_approved` (aktor, from/to, waktu, ip).
6. **Tolak** (`POST .../tolak`): alasan wajib; profil→`ditolak`; alasan disimpan untuk ditampilkan ke penyedia; audit log `verification_rejected` + alasan; akun tetap aktif.
7. Penyedia melihat alasan di halaman verifikasi, memperbaiki profil/dokumen, lalu ajukan ulang (langkah 3). Riwayat keputusan tetap terbaca dari `audit_logs`.
8. Katalog/venue tampil ke Klien hanya setelah penyedia `terverifikasi` DAN syarat publikasi item terpenuhi (§12).

### 10.4 Pencatatan reviewer dan waktu

- `verified_at` (sudah ada) diisi saat approve.
- Reviewer + alasan + from/to status dicatat di `audit_logs` (actor_id = Admin, reason, ip).
- Usulan kolom `reviewed_by` + `verification_note` pada profil (§15.2) agar UI penyedia/admin dapat menampilkan alasan terkini tanpa query audit terakhir; alternatif murni audit_logs dibahas §18.4.

## 11. Matriks Akses Berdasarkan Role

Legenda: ✅ boleh, ❌ 403, 🔒 login dulu (redirect), — tidak relevan. Ownership selalu divalidasi policy; ✅ bersyarat "milik sendiri" berarti hanya data miliknya.

| Aksi / endpoint                             | Guest | Klien     | Vendor            | Venue                     | Admin             |
| ------------------------------------------- | ----- | --------- | ----------------- | ------------------------- | ----------------- |
| GET katalog publik Klien (§7.5)             | 🔒    | ✅        | ❌                | ❌                        | ❌ (portal admin) |
| GET daftar/form katalog vendor              | 🔒    | ❌        | ✅ milik sendiri  | ❌                        | ❌                |
| POST/PUT/DELETE katalog, toggle status      | 🔒    | ❌        | ✅ milik sendiri  | ❌                        | ❌                |
| GET daftar/form venue                       | 🔒    | ❌        | ❌                | ✅ milik sendiri          | ❌                |
| POST/PUT/DELETE venue, ruangan, fasilitas   | 🔒    | ❌        | ❌                | ✅ milik sendiri          | ❌                |
| Tulis `cleaning_fee` venue                  | 🔒    | ❌        | ❌                | ❌ (read-only di UI)      | ✅                |
| Upload foto katalog/venue/profil            | 🔒    | ❌        | ✅ owner          | ✅ owner                  | ❌                |
| Upload dokumen verifikasi                   | 🔒    | ❌        | ✅ (ktp/nib/npwp) | ✅ (ktp/bukti_venue/npwp) | ❌                |
| GET /media/{media} foto privat              | 🔒    | ❌        | ✅ pemilik        | ✅ pemilik                | ✅                |
| GET /media/{media} foto publik              | 🔒    | ✅        | ✅ pemilik        | ✅ pemilik                | ✅                |
| GET /media/{media} dokumen verifikasi       | 🔒    | ❌ selalu | ✅ pemilik saja   | ✅ pemilik saja           | ✅                |
| DELETE media                                | 🔒    | ❌        | ✅ pemilik        | ✅ pemilik                | ✅                |
| GET halaman verifikasi penyedia             | 🔒    | ❌        | ✅                | ✅                        | ❌                |
| POST /verifikasi/ajukan                     | 🔒    | ❌        | ✅                | ✅                        | ❌                |
| GET antrean/detail verifikasi admin         | 🔒    | ❌        | ❌                | ❌                        | ✅                |
| POST setujui/tolak verifikasi               | 🔒    | ❌        | ❌                | ❌                        | ✅                |
| Lihat kontak pribadi/rekening utuh penyedia | ❌    | ❌        | ✅ miliknya       | ✅ miliknya               | ✅                |

Keterangan: akses lintas portal tetap diblokir `EnsureRole` (403) sebelum policy berjalan; tamu diarahkan ke `/login`. Admin tidak memiliki registrasi publik (BE-1). Halaman portal yang belum di-wiring tetap statis namun terproteksi role.

## 12. Rancangan Filter Visibilitas Katalog dan Venue

Ditegakkan pada **query dan endpoint backend**, bukan frontend.

### 12.1 Scope query (Eloquent local scope / query builder controller)

- `CatalogItem::publikUntukKlien()`:
    - `where('status', aktif)`;
    - `whereHas('vendorProfile', fn => verification_status = terverifikasi)`;
    - tanpa soft-deleted (default scope SoftDeletes sudah mengecualikan);
    - eager load hanya field usaha publik; **tidak** memuat `users.phone`/`email`, `pic_name`, rekening.
- `Venue::publikUntukKlien()`:
    - `whereHas('venueProfile', verification_status = terverifikasi)`;
    - `where('verification_status', terverifikasi)` pada venue — **menunggu keputusan §18.3** (rekomendasi: venue ikut profil saat approve, Admin dapat menonaktifkan per venue);
    - tanpa soft-deleted; ruangan/fasilitas eager load.
- Detail Klien: query yang sama + `findOrFail` → item nonpublik menghasilkan **404** (bukan 403, agar tidak membocorkan keberadaan data).
- Media publik: `visibility=publik` DAN kind foto DAN owner terverifikasi (policy §9.4). Dokumen verifikasi tidak pernah masuk query Klien.

### 12.2 Transisi status item katalog

- `aktif ↔ nonaktif` oleh pemilik (toggle). `nonaktif` menyembunyikan dari Klien tanpa menghapus.
- `menunggu_persetujuan` sudah ada di enum; pemakaiannya menunggu keputusan §18.2 (rekomendasi: item baru milik penyedia terverifikasi langsung `aktif`; `menunggu_persetujuan` dipakai bila kelak ada persetujuan per item — tidak diaktifkan diam-diam).
- Soft delete pemilik → hilang dari semua query publik; riwayat `order_items`/`quote_requests` tetap utuh (morph subject masih resolve karena baris soft-deleted ada).

### 12.3 Kebocoran yang harus diuji negatif

Response Klien (daftar, detail, profil penyedia, galeri) tidak boleh memuat: telepon pribadi, email pribadi/usaha, `pic_name`, nomor rekening utuh, path penyimpanan internal, dokumen verifikasi, data penyedia belum terverifikasi, item nonaktif/terhapus. Estimasi biaya di halaman Klien diberi label estimasi; total final tetap wewenang backend (BE-6), bukan dihitung frontend sebagai tagihan.

## 13. Rencana Test dan Acceptance Criteria

Lingkungan: PHPUnit + SQLite `:memory:` (phpunit.xml), `Storage::fake('private')` untuk upload, factory states BE-2 (bukan seeder di dalam test). Perintah: `php artisan test --compact` (suite penuh) dan `php artisan test --filter=...` per area. Regresi BE-1/BE-2 wajib tetap 96+ lulus.

### 13.1 Suite baru yang direncanakan

| Suite (baru)                                            | Cakupan minimal                                                                                                                                                                                                                                                                                                                                                                                             |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/Catalog/VendorCatalogCrudTest`           | CRUD sukses layanan & peralatan (satu tabel, kind benar); validasi gagal (harga negatif, kind tak dikenal, field wajib); role lain 403; guest redirect; akses item vendor lain 403/404 (IDOR); toggle status; soft delete hilang dari daftar & publik; input vendor_profile_id/status diabaikan                                                                                                             |
| `tests/Feature/Venue/VenueCrudTest`                     | CRUD venue + ruangan pertama; sync fasilitas; unique nama ruangan/fasilitas per venue; cleaning_fee ditolak dari pemilik, diterima dari Admin; deposit nullable tanpa logika; ownership venue lain ditolak; soft delete; hapus ruangan yang direferensia order ditolak FK                                                                                                                                   |
| `tests/Feature/Media/PhotoUploadTest`                   | Upload sukses jpg/png/webp; format ditolak (pdf/svg/php/gif); >5 MB ditolak; foto ke-11 ditolak; MIME spoof (ekstensi .jpg isi PHP) ditolak; file tersimpan di disk privat dengan nama acak; visibility awal privat; hapus media menghapus file; upload ke owner orang lain ditolak                                                                                                                         |
| `tests/Feature/Media/VerificationDocumentTest`          | Upload ktp/nib/npwp/bukti_venue sesuai role; pdf/jpg/png ≤5 MB; format lain ditolak; bukti_venue oleh vendor ditolak; unggah ulang mengganti file lama; dokumen tidak pernah publik meski penyedia terverifikasi                                                                                                                                                                                            |
| `tests/Feature/Media/MediaAccessTest`                   | Pemilik & Admin boleh baca dokumen; Klien & vendor lain 403/404; foto privat tidak bisa dibaca Klien; foto publik bisa setelah approve; path internal tidak muncul di response publik; route storage/{path} tidak dipakai untuk media BE-3                                                                                                                                                                  |
| `tests/Feature/Verification/VerificationWorkflowTest`   | Ajukan tanpa KTP ditolak; venue tanpa bukti venue ditolak; approve oleh Admin → terverifikasi + verified_at + reviewer + audit log + foto flip publik; tolak tanpa alasan ditolak; tolak dengan alasan → ditolak + alasan tersimpan + akun tetap aktif (T2); approve ganda/transisi ilegal ditolak; role non-admin 403; ajukan ulang setelah ditolak kembali menunggu; dokumen tetap privat setelah approve |
| `tests/Feature/Catalog/CatalogVisibilityTest`           | Vendor/venue belum terverifikasi tidak muncul di katalog Klien; item nonaktif/soft-deleted tidak muncul; detail nonpublik 404; response tidak memuat phone/email/pic/rekening/path; filter kategori/nama/harga maks bekerja; foto privat tidak terakses Klien                                                                                                                                               |
| `tests/Feature/Verification/AdminVerificationQueueTest` | Antrean hanya data nyata + filter; detail hanya Admin; penyedia biasa tidak bisa membuka halaman admin (regresi EnsureRole)                                                                                                                                                                                                                                                                                 |

### 13.2 Acceptance criteria Gate 4B

1. Seluruh suite baru lulus; suite penuh ≥ 96 test lama tetap lulus (tidak ada test lama dihapus/dilemahkan).
2. Matriks akses §11 terbukti oleh test negatif (role salah, guest, IDOR lintas penyedia).
3. B12 terbukti: batas format/ukuran/jumlah di server; dua kelas media terpisah; dokumen privat selamanya; tidak ada file di direktori publik; tidak ada path internal di response publik.
4. B14 terbukti: dokumen wajib per role; tolak wajib alasan; reviewer+waktu tercatat; audit log setiap keputusan; T2 utuh (status akun tidak berubah karena verifikasi).
5. Visibilitas §12 terbukti pada query/endpoint, bukan hanya Blade.
6. Halaman yang di-wiring menampilkan data nyata; halaman lain tetap prototipe jujur; tanpa sukses palsu; layout/CSS tidak dirombak.
7. Migration additive reversible (uji rollback pada SQLite sementara, bukan MySQL berisi data); seeder tetap idempoten.
8. `php artisan route:list` menunjukkan route baru sebelum catch-all; `view:cache` lulus; Pint untuk PHP yang berubah; `git diff --check` bersih.

## 14. Daftar Risiko Keamanan

| #   | Risiko                                                                                                                                                      | Tingkat    | Mitigasi dalam rancangan                                                                                                                                                                  |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R1  | Disk `local` `serve=true` → route framework `storage/{path}` melayani file tanpa auth di env local; dokumen verifikasi bisa bocor bila disimpan di disk ini | **Tinggi** | Disk `private` baru `serve=false` (§9.5); semua media BE-3 di disk itu; satu pintu `GET /media/{media}` berpolicy; test negatif                                                           |
| R2  | IDOR: id item/venue/ruangan/media milik penyedia lain ditebak                                                                                               | Tinggi     | Policy ownership di semua aksi; id dari route divalidasi terhadap profil sesi; 404 untuk data nonpublik Klien                                                                             |
| R3  | Privilege escalation: input `role`, `vendor_profile_id`, `verification_status`, `verified_at`, `cleaning_fee` diselundupkan lewat form                      | Tinggi     | Form Request membatasi field (Arr::only pola BE-1); status verifikasi hanya ditulis endpoint Admin; reviewer dari sesi                                                                    |
| R4  | MIME spoofing / file berbahaya berekstensi gambar (php, svg+xss, double extension)                                                                          | Tinggi     | Validasi `fileinfo` + `getimagesize` untuk foto; mimes ketat; nama file diacak; disimpan di luar docroot; tidak ada eksekusi dari storage                                                 |
| R5  | Upload melebihi batas / banjir foto (DoS penyimpanan)                                                                                                       | Sedang     | max:5120 server-side; batas 10 foto/owner dihitung dari DB dalam transaction+lock; perhatikan `post_max_size`/`upload_max_filesize` PHP saat implementasi (tanpa ubah php.ini tanpa izin) |
| R6  | Race condition jumlah foto & approve ganda                                                                                                                  | Sedang     | `DB::transaction` + lock baris profil/owner; transisi status diverifikasi ulang di dalam transaction                                                                                      |
| R7  | `gd` tidak aktif → tidak ada re-encoding/sanitasi piksel gambar                                                                                             | Sedang     | BE-3 tidak melakukan manipulasi gambar; validasi getimagesize cukup; aktivasi gd diputuskan terpisah bila thumbnail dibutuhkan                                                            |
| R8  | Kebocoran kontak pribadi/rekening/path internal di response Klien                                                                                           | Tinggi     | Select field eksplisit (§12.3); test negatif assertion string; rekening tidak di-query untuk Klien                                                                                        |
| R9  | Soft-deleted/nonaktif bocor lewat detail atau relasi eager load                                                                                             | Sedang     | Scope default SoftDeletes; filter publik diterapkan juga di detail & relasi; test                                                                                                         |
| R10 | Dokumen verifikasi ter-flip publik saat approve (bug logika flip massal)                                                                                    | Tinggi     | Flip hanya `kind=foto`; test asserting dokumen tetap privat setelah approve                                                                                                               |
| R11 | Path traversal via nama/path file tersimpan                                                                                                                 | Sedang     | Nama acak server; path relatif dari Storage; tidak menerima path dari input; `Storage::disk()->path()` tidak diekspos                                                                     |
| R12 | CSRF / sesi                                                                                                                                                 | Rendah     | Semua form tulis `@csrf` (pola BE-1); middleware web sudah aktif                                                                                                                          |
| R13 | Audit log tidak tertulis saat keputusan parsial gagal                                                                                                       | Sedang     | Audit dalam transaction yang sama dengan perubahan status; gagal = rollback semua                                                                                                         |
| R14 | Catch-all menelan route baru / route berparameter tidak cocok                                                                                               | Sedang     | Route eksplisit sebelum catch-all (pola terbukti BE-1); test route:list                                                                                                                   |

## 15. Daftar Migration Tambahan (diusulkan untuk Gate 4B, belum dibuat)

Semua additive, reversible, tanpa mengubah data BE-1/BE-2, tanpa melemahkan constraint yang ada.

### 15.1 `2026_11_16_200001_add_kind_and_metadata_to_media_table` (wajib)

Tambah pada `media`:

- `kind` string default `foto` (nilai: `foto`, `dokumen_verifikasi`) — pembeda dua kelas media B12; default aman untuk baris lama.
- `doc_type` string nullable (`ktp`, `nib`, `npwp`, `bukti_venue`) — hanya untuk dokumen verifikasi; dibutuhkan validasi kelengkapan B14 per role.
- `disk` string default `private` — eksplisit menyimpan lokasi; baris lama (belum ada file nyata) tetap konsisten.
- `mime_type` string nullable, `size` unsignedBigInteger nullable, `original_name` string nullable — metadata untuk validasi ulang, header unduhan, dan audit; tidak wajib tapi sangat disarankan.
- Index `(owner_type, owner_id, kind)` — query hitung foto per owner & daftar dokumen.

**Alasan:** skema media BE-2 tidak dapat membedakan foto publik-calon vs dokumen identitas privat; B12 melarang penyamaan berdasarkan ekstensi. Tanpa kolom ini, aturan akses dan batas jumlah tidak dapat ditegakkan dengan benar.

### 15.2 `2026_11_16_200002_add_verification_review_fields_to_provider_profiles` (wajib, kecuali alternatif §18.4 dipilih)

Tambah pada `vendor_profiles` dan `venue_profiles`:

- `verification_note` text nullable — alasan penolakan/catatan Admin terkini untuk UI penyedia.
- `reviewed_by` foreignId nullable constrained `users` (restrict/nullOnDelete diputuskan saat implementasi; rekomendasi nullOnDelete agar riwayat tetap) — reviewer terakhir.
- `verification_submitted_at` timestamp nullable — pengurutan antrean Admin.

**Alasan:** B14 mewajibkan penolakan disertai alasan dan pencatatan reviewer/waktu; `verified_at` saja tidak cukup. `audit_logs` menyimpan riwayat, tetapi kolom ini menyimpan status terkini yang dibaca UI tanpa query agregat.

### 15.3 Tidak ada migration lain untuk lingkup inti

- Katalog/venue/ruangan/fasilitas/audit_logs sudah lengkap untuk CRUD + verifikasi.
- Kalender & pengajuan harga (bila masuk lingkup, §18.1) memakai `availability_days`/`price_change_requests` yang sudah ada — tanpa migration baru.
- Enum baru yang menyertai: `MediaKind` (foto/dokumen_verifikasi) dan `VerificationDocType` (ktp/nib/npwp/bukti_venue) di `app/Enums/`; `media.kind`/`doc_type` di-cast ke enum tersebut.

## 16. Daftar File yang Kemungkinan Dibuat/Diubah pada Gate 4B

### 16.1 Dibuat (baru)

- `app/Http/Controllers/Vendor/CatalogItemController.php`
- `app/Http/Controllers/Venue/VenueController.php`, `VenueRoomController.php`
- `app/Http/Controllers/MediaController.php`, `VerificationController.php`
- `app/Http/Controllers/Admin/ProviderVerificationController.php`
- `app/Http/Controllers/Klien/CatalogController.php`
- `app/Http/Requests/Catalog/{StoreCatalogItemRequest,UpdateCatalogItemRequest,ToggleCatalogStatusRequest}.php`
- `app/Http/Requests/Venue/{StoreVenueRequest,UpdateVenueRequest,StoreVenueRoomRequest}.php`
- `app/Http/Requests/Media/{UploadPhotoRequest,UploadVerificationDocumentRequest}.php`
- `app/Http/Requests/Verification/SubmitVerificationRequest.php`
- `app/Http/Requests/Admin/ReviewVerificationRequest.php`
- `app/Policies/{CatalogItemPolicy,VenuePolicy,VenueRoomPolicy,MediaPolicy,ProviderProfilePolicy}.php`
- `app/Enums/{MediaKind,VerificationDocType}.php`
- 2 migration (§15)
- ±8 file test (§13.1)
- Factory pendukung bila perlu (mis. state media dokumen) — additive pada factory ada.

### 16.2 Diubah

- `routes/web.php` — route eksplisit §7 sebelum catch-all; catch-all tetap untuk halaman statis.
- `config/filesystems.php` — disk `private` `serve=false` (§9.5; perubahan config, bukan `.env`).
- `app/Models/Media.php` — fillable/casts/relasi kind+doc_type+metadata; scope foto/dokumen.
- `app/Models/VendorProfile.php`, `VenueProfile.php` — fillable/casts kolom verifikasi baru; relasi reviewer.
- `app/Models/CatalogItem.php`, `Venue.php` — scope publik (§12) bila ditaruh di model.
- `app/Providers/AppServiceProvider.php` — registrasi policy (morph map tidak berubah).
- `database/seeders/LocalDemoSeeder.php` — additive: contoh dokumen/foto demo lokal bila disetujui (tetap idempoten, local-only).
- **Blade (wiring terbatas, pola BE-1, tanpa redesign):**
    - Vendor: `layanan`, `peralatan`, `item-form`, `peralatan-form`, `verifikasi` (perbaikan konten role + field B14), `profil` (bagian foto).
    - Venue: `data`, `form`, `verifikasi` (field NPWP opsional), `profil` (bagian foto).
    - Admin: `admin-penyedia` (data nyata + panel detail/keputusan), `mitra-detail` (dokumen + keputusan), `admin-venue-form` (cleaning_fee).
    - Klien: `katalog-vendor`, `katalog-vendor-detail`, `katalog-peralatan`, `katalog`, `katalog-venue-detail`, `penyedia-vendor`, `penyedia-venue`.
- Dokumentasi (setelah Gate 4B selesai): `AI_AGENT_CONTEXT.md` (§6.15 + riwayat 9.5 + §14), `BACKEND_AGENT_CONTEXT.md` (addendum BE-3), `BACKEND_BUSINESS_RULES.md` (B12/B14 → disetujui, §18.9), `BACKEND_API_CONTRACT.md` (route terpasang), laporan implementasi BE-3.

Tidak disentuh: layout/CSS/`aksi.js`/`app-ui.js` (kecuali penambahan kecil yang disetujui untuk form media), halaman keuangan/quote/order (BE-4+), `klien/katalog-detail` generik, `.env`, dependency, konfigurasi auth yang sudah ada.

## 17. Dependensi Antarpekerjaan dan Urutan Implementasi

```
(1) Skema & enum ──▶ (2) Storage private + Policy ──▶ (3) Media (foto+dokumen)
                                                          │
(1) ──▶ (4) CRUD katalog Vendor ──────────────────────────┤
(1) ──▶ (5) CRUD Venue + ruangan/fasilitas ───────────────┤
                                                          ▼
                                   (6) Workflow verifikasi (provider + Admin)
                                                          │
                                                          ▼
                                   (7) Visibilitas & endpoint Klien
                                                          │
                                                          ▼
                                   (8) Wiring Blade terbatas ──▶ (9) Regression penuh + QC
```

Urutan rekomendasi Gate 4B (setiap langkah selesai dengan test-nya sendiri):

1. Migration §15 + enum `MediaKind`/`VerificationDocType` + penyesuaian model (test integritas additive).
2. Disk `private` + registrasi policy + `MediaPolicy` (test akses dasar).
3. `MediaController` + Request upload foto/dokumen + delete + stream (suite Media).
4. `Vendor\CatalogItemController` + Request + scope (suite VendorCatalogCrud).
5. `Venue\VenueController`/`VenueRoomController` + Request (suite VenueCrud).
6. `VerificationController` + `Admin\ProviderVerificationController` + flip foto + audit log (suite VerificationWorkflow + Queue).
7. `Klien\CatalogController` + filter publik (suite CatalogVisibility).
8. Wiring Blade terbatas per portal (test render halaman + ProjectPortalPagesTest tetap lulus).
9. Suite penuh, `route:list`, `view:cache`, Pint, `git diff --check`, uji reversibility SQLite sementara, laporan implementasi + pembaruan konteks.

Dependensi eksternal: tidak ada package baru; Boost/Pint yang ada dipakai apa adanya. Langkah 6 bergantung pada 3 (dokumen) dan 4–5 (subjek yang diverifikasi); langkah 7 bergantung pada 6 (status terverifikasi nyata).

## 18. Hal yang Masih Ambigu / Memerlukan Keputusan Pengguna

Tidak ada yang diisi sendiri di luar keputusan yang sudah disetujui. Daftar berikut harus dijawab (atau disetujui rekomendasinya) sebelum/menjelang Gate 4B:

1. **Lingkup BE-3 vs plan dokumen.** Plan/API contract memasukkan kalender ketersediaan (5.5), pengajuan harga + keputusan Admin, POST ketersediaan, dan "tambah item ke acara draft" ke BE-3; lingkup Gate 4A (A–G) hanya CRUD katalog/venue, media, verifikasi, visibilitas, authorization, testing. **Rekomendasi:** Gate 4B mengerjakan lingkup A–G dulu; kalender + pengajuan harga sebagai sub-tahap BE-3b setelahnya; "tambah item ke acara" digeser ke BE-4/BE-5 karena bergantung alur acara/quote. Perlu persetujuan.
2. **Publikasi item katalog baru.** Enum punya `menunggu_persetujuan`. Apakah tiap item baru menunggu persetujuan Admin per item, atau langsung `aktif` selama penyedia terverifikasi (gate di penyedia, bukan item)? **Rekomendasi:** gate di penyedia + status item; `menunggu_persetujuan` tidak dipakai dulu. Perlu keputusan karena memengaruhi alur Admin dan UI vendor.
3. **Semantik `venues.verification_status`.** Siapa mengisi status per venue: otomatis ikut profil saat approve, atau Admin per venue (via `admin-venue-form`)? **Rekomendasi:** saat approve penyedia, venue miliknya ikut `terverifikasi`; Admin dapat mengubah per venue kemudian (termasuk `nonaktif` yang menyembunyikan tanpa hapus). Perlu keputusan.
4. **Kolom reviewer/alasan di profil (§15.2) vs murni `audit_logs`.** **Rekomendasi:** kolom additive (UI lebih sederhana, riwayat tetap di audit). Alternatif tanpa migration profil mungkin, tetapi query alasan terakhir lebih rapuh. Perlu persetujuan migration.
5. **Perubahan frontend terbatas yang diperlukan** (di luar wiring murni): (a) `vendor/verifikasi` berkonten venue dan mewajibkan "bukti rekening" yang tidak ada di B14, serta teks "Akun aktif setelah seluruh pemeriksaan selesai" yang bertentangan dengan T2; (b) `venue/verifikasi` belum punya field NPWP opsional; (c) panel detail/keputusan `#detail-verifikasi` di `admin/admin-penyedia` belum ada (anchor tanpa target); (d) hint "JPG/PNG maks 5 MB konfigurasi contoh" di profil perlu diganti batas B12 final + WebP. **Rekomendasi:** izinkan perbaikan terbatas tersebut pada Gate 4B tanpa redesign. Perlu persetujuan eksplisit.
6. **Perubahan `config/filesystems.php`** (disk `private` `serve=false`, §9.5). Bukan `.env`, bukan dependency, tetapi tetap perubahan konfigurasi. Perlu persetujuan.
7. **Nasib "bukti rekening pencairan" di UI verifikasi.** B14 tidak mewajibkannya; UI mewajibkan. **Rekomendasi:** backend mengikuti B14 (tidak wajib); UI disesuaikan (lihat 5). Bila pengguna ingin tetap wajib, itu keputusan bisnis baru yang harus dicatat.
8. **Penulisan `cleaning_fee` oleh Admin pada BE-3.** B4 + UI menyatakan hanya Admin yang mengubah. **Rekomendasi:** endpoint Admin minimal (`admin-venue-form`) menulis `cleaning_fee` pada Gate 4B; pemilik venue read-only. Perlu konfirmasi lingkup.
9. **Pembaruan dokumen aturan bisnis (usulan, belum dilakukan).** `BACKEND_BUSINESS_RULES.md`: baris B12 dan B14 bagian B diubah menjadi **DISETUJUI (2026-11-16, Gate 4A)** dengan ringkasan keputusan instruksi §3.4–3.5, dan bagian E ditambah dua baris keputusan; `BACKEND_AGENT_CONTEXT.md` ditambah addendum Gate 4A; `AI_AGENT_CONTEXT.md` §6.15 + riwayat 9.5 + §14 diperbarui setelah Gate 4B. Sesuai instruksi Gate 4A, perubahan ini **diusulkan dulu** dan tidak dilakukan pada sesi ini.
10. **Batas jumlah dokumen verifikasi per tipe.** B12 hanya membatasi foto (10). **Rekomendasi:** satu dokumen aktif per tipe (unggah ulang mengganti), tanpa batas total tambahan. Perlu konfirmasi ringan.

Tidak ada blocker teknis keras: seluruh fondasi (skema, model, enum, factory, auth, middleware, test) sudah tersedia dan terverifikasi. Blocker yang tersisa hanyalah persetujuan lingkup/keputusan di atas.

---

## 19. Konfirmasi Kepatuhan Gate 4A (read-only)

- Tidak ada controller/model/migration/route/middleware/Form Request/Blade/CSS/JS yang dibuat atau diubah.
- Tidak ada migration/seeder yang dijalankan pada database aktif; tidak ada perubahan database.
- Tidak ada package/dependency yang ditambah/diubah; Laravel Boost dipertahankan apa adanya.
- `.env` tidak dibaca isinya maupun diubah; tidak ada credential ditampilkan.
- Tidak ada file yang dihapus/ditimpa; seluruh perubahan working tree (frontend + BE-1 + BE-2) dipertahankan; `git diff --check` bersih; HEAD tetap `50bb69f`.
- Tidak ada reset/clean/checkout paksa/commit/push/merge/PR; stash kosong.
- Perintah yang dijalankan hanya pemeriksaan read-only: `php -v`, `php artisan --version`, `composer show --direct`, `php artisan route:list`, `php artisan migrate:status`, `php artisan config:show filesystems.default`, `php artisan test --compact` (SQLite memori terisolasi), `git status/branch/log/diff --check/stash list`, serta pembacaan file.
- Satu-satunya file baru: `docs/reports/BE-3_Preparation_Report_Pesta_Kita.md` (laporan ini).
- BE-4 dan seterusnya tidak dikerjakan. Gate 4B tidak dimulai sampai pengguna menyetujui hasil persiapan ini secara eksplisit.

---

## 20. Status Akhir

**STATUS GATE 4A: SELESAI**

**Repository:**

- Branch: `master`
- HEAD: `50bb69f9a17df8b55f6e99e367aa89eff2bc3b63` (2 commit, tanpa commit baru)
- Working tree: kotor — 238 entri perubahan baseline frontend + BE-1 + BE-2 yang belum di-commit; seluruhnya dipertahankan; stash kosong; `git diff --check` bersih
- Kondisi database: MySQL `pesta_kita`, `migrate:status` → 23 Ran (7 BE-1 batch [1] + 16 BE-2 batch [2]); tidak ada perubahan database pada sesi ini

**Hasil audit:**

- Dokumen: 9/9 dokumen wajib dibaca penuh; B12/B14 diperlakukan sebagai keputusan tetap; ketidaksesuaian dokumen (B12/B14 masih "terbuka" di BUSINESS_RULES; lingkup BE-3 plan vs Gate 4A) dicatat di §3 dan §18
- Struktur backend: BE-1 (auth/role/profil) + BE-2 (16 tabel/model/enum/factory) terverifikasi nyata; belum ada controller domain/Policy/media/verifikasi; baseline test diulang: **96 passed (704 assertions)**
- Struktur database: skema BE-2 cukup untuk CRUD; kesenjangan B12/B14 = kolom jenis media + metadata (§15.1) dan kolom reviewer/alasan verifikasi (§15.2)
- Risiko: 14 risiko (§14), tertinggi = disk local `serve=true` (R1), IDOR (R2), penyelundupan field status/harga (R3), MIME spoofing (R4), kebocoran kontak/path (R8), flip dokumen jadi publik (R10); `gd` masih tidak aktif (R7)

**Rancangan BE-3:**

- CRUD katalog: §7.1 + §8 (satu tabel `catalog_items`, kind layanan/peralatan, SoftDeletes, ownership policy)
- CRUD venue: §7.2 + §8 (venue + ruangan + fasilitas; cleaning_fee Admin-only; deposit nullable tanpa logika)
- Media: §9 (dua kelas media B12, disk privat serve=false, satu pintu `GET /media/{media}`, batas 5 MB/10 foto, nama acak, validasi MIME server)
- Verifikasi: §10 (B14 per role, mesin status menunggu/terverifikasi/ditolak/nonaktif, alasan wajib saat tolak, reviewer+waktu+audit log, T2 utuh, flip foto hanya saat approve)
- Visibilitas: §12 (filter query backend: penyedia terverifikasi + item aktif + tidak terhapus + media publik; 404 untuk nonpublik; tanpa kontak pribadi/rekening/path)
- Authorization: §11 (matriks 4 role + guest; EnsureRole + Policy + Form Request; anti-IDOR)
- Testing: §13 (8 suite baru + regresi 96 test; acceptance criteria Gate 4B)

**Perubahan yang dilakukan:**

- File dibuat: `docs/reports/BE-3_Preparation_Report_Pesta_Kita.md` (laporan ini)
- Kode aplikasi, database, dependency, dan `.env` **tetap tidak berubah**; dokumen aturan bisnis tidak diubah (pembaruan B12/B14 diusulkan di §18.9)

**Blocker dan keputusan tersisa:**

- 10 butir keputusan/klarifikasi di §18 (lingkup kalender/pengajuan harga, publikasi item baru, semantik status venue, migration tambahan, perubahan frontend terbatas, config disk, bukti rekening UI, cleaning_fee Admin, pembaruan dokumen, batas dokumen per tipe). Tidak ada blocker teknis keras; semuanya menunggu keputusan pengguna.

**Rekomendasi:**

- **BE-3 siap masuk Gate 4B** setelah pengguna menjawab/menyetujui rekomendasi §18 (terutama butir 1, 2, 3, 5, 6) dan memberikan instruksi implementasi eksplisit.
- Urutan implementasi disarankan: skema+enum → disk private+policy → media → CRUD katalog → CRUD venue → workflow verifikasi → visibilitas Klien → wiring Blade terbatas → regresi penuh + QC (§17).

**PENTING: Pekerjaan berhenti di sini. Gate 4B tidak dimulai sampai pengguna secara eksplisit menyetujui hasil persiapan ini dan memberikan instruksi implementasi.**
