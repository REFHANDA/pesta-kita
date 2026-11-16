# BATCH 1 PREPARATION REPORT — PESTA KITA

**BE-1 + BE-2 Design Finalization**

| Informasi | Detail |
|---|---|
| Tanggal | 16 November 2026 |
| Tahap | Batch 1 Preparation — BE-1 + BE-2 |
| Mode | DOCUMENTATION-ONLY |
| Status implementasi | Belum dimulai |
| Batasan | Tidak ada code changes, migrations, maupun database writes |

---

## Session Compliance

Selama sesi persiapan, hanya lima file dokumentasi yang diedit untuk mencatat keputusan yang telah disetujui:

| File | Perubahan |
|---|---|
| `BACKEND_BUSINESS_RULES.md` | Baris B4/B15/B18 dan section E baru |
| `BACKEND_AGENT_CONTEXT.md` | Status row dan dated addendum |
| `BACKEND_DATABASE_DESIGN.md` | Anotasi B4/B15 |
| `BACKEND_IMPLEMENTATION_PLAN.md` | Dependency BE-2 dan Boost risk row |
| `AI_AGENT_CONTEXT.md` | Satu history row |

Verifikasi setelah perubahan:

- `git diff --check` bersih.
- Git change counts tidak berubah: 104 modified / 22 deleted / 39 untracked.
- Tidak ada file code, frontend, maupun configuration yang disentuh.

---

# A. Decision Register

## A.1 Approved Decisions — 16 November 2026

| ID | Keputusan | Dampak terhadap Schema |
|---|---|---|
| B4 | Cleaning fee bersifat opsional, dicatat terpisah dari rental price, dan boleh bernilai null. Detail snapshot transaksi ditunda ke BE-6. | `venues.cleaning_fee` → nullable `unsignedBigInteger`, terpisah dari `rental_price` |
| B15 | Satu struktur katalog digunakan untuk layanan dan peralatan, dibedakan berdasarkan kind/type. Tidak dibuat entity atau tabel equipment terpisah. | Mengonfirmasi `catalog_items.kind` (`layanan` / `peralatan`); halaman peralatan menggunakan tabel yang sama |
| B18 | Demo bar hanya aktif pada local/demo environment dan tidak boleh menjadi auth bypass di production. Portal tests ditulis ulang setelah autentikasi menggunakan akun dan role yang sesuai. | Tidak ada dampak schema; memengaruhi middleware, partial gating, dan tests |
| Boost | Mempertahankan dependency dan konfigurasi Laravel Boost yang sudah ada. Tidak melakukan removal, reinstall, atau update tanpa persetujuan. | Tidak ada |
| FE wiring | Wiring terbatas pada login, register, user profile, dan password-change forms. Hanya koneksi; tanpa redesign, perubahan layout/CSS/visual, atau perubahan perilaku yang tidak diperlukan. | Tidak ada |

## A.2 Still Open — Tidak Boleh Diputuskan oleh Implementer

| ID | Topik | Dibutuhkan Sebelum |
|---|---|---|
| B6 | Jumlah venue per event (satu atau banyak) | Validasi BE-5; analisis khusus pada §C.4. Schema tetap netral |
| B5 | Deposit venue masuk MVP atau ditunda | Ditunda dengan aman melalui nullable column, sesuai instruksi |
| B7 | Objection window (semantik 24 jam) | BE-5 |
| B1, B2, B3, B8, B9, B10, B13 | Refund policy, unique code, commission base, invoice versions, payment deadline, expiry, payment proof | BE-6 — hard blockers |
| B11, B12, B14 | Booked-status derivation, media limits, partner verification | BE-3 |
| B16, B17 | Floor plan, package recommendations | BE-7+ |

---

# B. BE-1 Implementation Plan — Authentication, Authorization, Role Management

## B.1 Authentication Design

Mempertahankan web session guard dan Eloquent User provider yang sudah ada.

Tidak menambahkan guard, package, atau dependency baru.

### Users Schema

Tabel `users` mendapatkan kolom:

| Kolom | Tipe / Ketentuan |
|---|---|
| `role` | String, indexed; nilai `admin`, `klien`, `vendor`, `venue` |
| `phone` | Nullable string |
| `status` | String, default `aktif`; nilai `aktif` / `nonaktif` |

### Login — `POST /login`

- Memvalidasi email dan password.
- Password diperiksa menggunakan `Hash::check` melalui hashed cast yang sudah tersedia.
- Akun berstatus `nonaktif` ditolak dengan pesan error generik.
- Menggunakan rate limit (proposal: 5 percobaan per menit berdasarkan email + IP).
- Session diregenerasi setelah login.
- Redirect ditentukan oleh role yang tersimpan pada akun, bukan oleh role tab pada form.

**Technical Proposal T1 — Konfirmasi saat approval**

Role-selector tabs yang ada tetap dipertahankan secara visual, tetapi tidak memiliki pengaruh terhadap authorization. Jika pilihan tab tidak sesuai dengan role akun, server tetap mengarahkan pengguna ke portal sesuai role akun sebenarnya.

### Register — `POST /register`

Validasi role di server:

`role:in:klien,vendor,venue`

Role Admin harus ditolak di server sesuai locked rule A13.

Validasi input:

- `name`
- `email` unik
- `phone` nullable
- `password` dengan konfirmasi dan minimal 8 karakter

Untuk Vendor dan Venue, tambahan input:

- `business_name`
- `address`
- `category` khusus Vendor (form saat ini menyembunyikannya untuk Venue)

Pembuatan data `users` dan profile yang sesuai dilakukan dalam satu database transaction.

Status awal provider profile:

- `verification_status = menunggu`
- `verified_at = null`

Redirect mengikuti UI yang tersedia:

- Klien → `/klien`
- Vendor → `/vendor/verifikasi`
- Venue → `/venue/verifikasi`

**Technical Proposal T2 — Konfirmasi saat approval**

Akun provider yang baru terdaftar mendapatkan `status = aktif` dan boleh login segera. Verifikasi bisnis menjadi gerbang publikasi katalog pada tahap berikutnya.

Petunjuk UI "aktif setelah diverifikasi" masih ambigu: apakah yang dimaksud akses login atau publikasi katalog. B14 belum diputuskan dan tidak boleh ditentukan sendiri oleh implementer.

### Logout — `POST /logout`

- Session di-invalidate.
- Token diregenerasi.
- Pengguna diarahkan ke `/login`.

**Fakta terverifikasi:** saat ini belum ada logout UI pada view mana pun.

**Proposal:** menambahkan logout form pada setiap halaman profil (masuk lingkup wiring yang disetujui). Tombol logout pada sidebar merupakan perubahan layout dan memerlukan persetujuan terpisah; tidak diasumsikan.

### Profile Update — `POST /profil`

Field yang dapat diperbarui:

- `name`
- `phone`

Untuk provider, termasuk:

- `business_name`
- `category`
- `address`
- `pic_name`

Email menjadi read-only pada seluruh portal. Admin sudah menggunakan pola ini dan belum ada alur perubahan email dalam scope.

### Password Change — `POST /profil/sandi`

- Memverifikasi current password.
- Memvalidasi new password dan konfirmasi.
- Password baru minimal 8 karakter.

## B.2 Roles & Access Restriction

| Prefix | Middleware | Guest | Wrong Role | Nonaktif |
|---|---|---|---|---|
| `/klien` | `auth`, `role:klien` | Redirect `/login` | 403 | Forced logout |
| `/vendor` | `auth`, `role:vendor` | Redirect `/login` | 403 | Forced logout |
| `/venue` | `auth`, `role:venue` | Redirect `/login` | 403 | Forced logout |
| `/admin` | `auth`, `role:admin` | Redirect `/login` | 403 | Forced logout |

## B.3 Middleware

Membuat `App\Http\Middleware\EnsureRole` dengan parameterized role.

Middleware didaftarkan dengan alias `role` pada `bootstrap/app.php` (closure saat ini masih kosong).

Tanggung jawab middleware:

- Memeriksa kecocokan role.
- Memeriksa status akun aktif.
- Menolak akses yang tidak sesuai.

Policies akan ditambahkan pada tahap domain berikutnya (BE-3+). BE-1 hanya membutuhkan role gating pada level portal.

## B.4 User Profile Structure

Profile dibuat pada BE-1 sesuai rencana yang sudah ada: profil kosong sesuai peran.

| Tabel | Kolom |
|---|---|
| `client_profiles` | `id` PK, `user_id` FK → `users` unique, timestamps |
| `vendor_profiles` | `id` PK, `user_id` FK unique, `business_name`, `category`, `address`, `pic_name` nullable, `service_area` nullable, `description` nullable, `verification_status` default `menunggu`, `verified_at` nullable, timestamps |
| `venue_profiles` | Pola yang sama; `category` nullable karena form registrasi Venue tidak memiliki category |

Bank accounts, media, dan tabel domain lainnya masuk BE-2 (schema only), bukan wiring BE-1.

## B.5 Routes

| Method | URI | Middleware | Action |
|---|---|---|---|
| POST | `/login` | `guest` | `Auth\LoginController@store` |
| POST | `/register` | `guest` | `Auth\RegisterController@store` |
| POST | `/logout` | `auth` | `Auth\LogoutController@destroy` |
| POST | `/profil` | `auth` | `ProfileController@update` |
| POST | `/profil/sandi` | `auth` | `ProfileController@updatePassword` |
| GET | Existing portal routes + catch-alls | `auth` + role per prefix | Closure tetap, kini terlindungi |

Seluruh explicit POST routes harus didaftarkan sebelum `/{page}` catch-alls karena risiko yang ditemukan pada BE-0.

Endpoint `/profil` digunakan bersama oleh seluruh portal. Kepemilikan data mengacu pada authenticated user sehingga tidak diperlukan delapan route duplikat.

## B.6 Work Sequence

1. Enums (`Role`, `UserStatus`, `VerificationStatus`).
2. Migrations (users alter + 3 profile tables).
3. Models dan relations.
4. Middleware + bootstrap alias.
5. Form Requests + controllers + routes.
6. Seeder.
7. Frontend wiring (§D).
8. Test rewrite + new tests.
9. Verification:
   - `php artisan test --compact`
   - `view:cache`
   - Pint pada PHP files yang berubah
   - Dokumentasi diperbarui.

## B.7 Test Plan

### Guest

- Setiap portal URL mengarahkan guest ke `/login`.
- POST write routes ditolak untuk guest.

### Correct Role

- Setiap dashboard dan catch-all page mengembalikan HTTP 200 dengan akun role yang sesuai.
- `ProjectPortalPagesTest` dan `ProjectPortalAccessibilityTest` ditulis ulang menggunakan `actingAs` untuk masing-masing role sesuai B18.

### Wrong Role

- Akses ke seluruh portal lain menghasilkan 403.
- Matriks pengujian: 4 role × 3 portal yang tidak sesuai.

### Inactive Account

- Login akun nonaktif ditolak.
- Session aktif milik akun yang kemudian berstatus nonaktif diblokir.

### Register

- Registrasi role Admin ditolak.
- Duplicate email ditolak.
- Password confirmation wajib sesuai.
- Profile Vendor/Venue dibuat dengan status `menunggu`.
- Profile Klien dibuat.
- Database transaction rollback jika proses gagal.

### Login

- Redirect sukses sesuai role akun.
- Password salah menghasilkan pesan generik.
- Throttle bekerja.

### Logout

- Session dihancurkan.

### Profile

- Ownership ditegakkan.
- Email tidak dapat diubah.
- Password change mensyaratkan current password benar.

### Demo Bar

- Ditampilkan pada local.
- Tidak ditampilkan pada testing atau environment lain.

## B.8 Files to Create / Modify

### Create — sekitar 20 file

- `app/Enums/Role.php`
- `app/Enums/UserStatus.php`
- `app/Enums/VerificationStatus.php`
- `app/Http/Middleware/EnsureRole.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/LogoutController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Profile/UpdateProfileRequest.php`
- `app/Http/Requests/Profile/UpdatePasswordRequest.php`
- `app/Models/ClientProfile.php`
- `app/Models/VendorProfile.php`
- `app/Models/VenueProfile.php`
- 4 migration files (users alter + 3 profiles)
- `database/seeders/LocalDemoSeeder.php`
- `tests/Feature/Auth/*` (login, register, logout, profile, role-access)

### Modify — sekitar 15 file

- `app/Models/User.php`
- `bootstrap/app.php`
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- `database/factories/UserFactory.php` (role states)
- Views:
  - `auth/login`
  - `auth/register`
  - `klien/login`
  - `vendor/login`
  - `admin/login`
  - 4 halaman profil
  - `partials/demo-bar`
- Tests:
  - 2 portal test files

Folder dasar baru (`app/Enums`, `app/Http/Middleware`, `app/Http/Requests`) mengikuti arsitektur yang sudah diusulkan pada implementation plan dan dicantumkan kembali dalam approval checklist (§G).

---

# C. BE-2 Schema Proposal — Database Foundation & Core Domain Models

**Scope:** domain non-finansial saja sesuai implementation plan.

Cakupan yang ditunda:

- Financial tables (`invoices`, `invoice_lines`, `payments`, `refund_requests`, `refund_transfers`, `payouts`) → BE-6.
- `rundown_items`, `reviews` → BE-7.
- Notifications → ditunda.

### Legend

- PK: Primary Key
- FK: Foreign Key
- UQ: Unique
- IDX: Index
- N: Nullable
- uBig: `unsignedBigInteger` (integer rupiah)
- morph: pasangan `*_type` + `*_id`

## C.1 Tables (16), Columns & Constraints

| # | Table | Columns (beyond id PK + timestamps) | Keys & Indexes |
|---:|---|---|---|
| 1 | `provider_bank_accounts` | Morph owner (vendor/venue profile), `bank_name`, `account_number`, `account_name`, `verification_status` (default `menunggu`) | IDX owner morph |
| 2 | `company_bank_accounts` | `bank_name`, `account_number`, `account_name`, `is_active` bool default `true` | — |
| 3 | `catalog_items` | FK `vendor_profile_id`; `kind` (`layanan` / `peralatan`) — B15; `name`, `category`, `description` N, `catalog_price` uBig, `pricing_mode` (`tetap` / `perlu_penawaran`), `max_per_day` uint N, `status`, `policies` N; softDeletes | IDX (`vendor_profile_id`, `status`, `category`) |
| 4 | `venues` | FK `venue_profile_id`; `name`, `kind`, `capacity` uint, `address`, `description` N, `rules` N, `cancellation_policy` N, `rental_price` uBig, `cleaning_fee` uBig N — B4 (optional, separate), `duration_hours` uint N, `deposit_amount` uBig N (B5 deferred), `verification_status` default `menunggu`, `verified_at` N; softDeletes | IDX (`venue_profile_id`, `verification_status`) |
| 5 | `venue_rooms` | FK `venue_id` cascade; `name`, `capacity` uint N | UQ (`venue_id`, `name`) |
| 6 | `venue_facilities` | FK `venue_id` cascade; `name` | UQ (`venue_id`, `name`) |
| 7 | `media` | Morph owner, `path`, `visibility` (`privat` default / `publik`), `sort_order` uint default 0 | IDX owner morph |
| 8 | `price_change_requests` | Morph subject (`catalog_item` / `venue`), `old_price` uBig, `new_price` uBig, `reason`, `status` default `menunggu`, FK `reviewed_by` → `users` N, `reviewed_at` N | IDX (subject morph, `status`) |
| 9 | `availability_days` | Morph subject (`catalog_item` / `venue_room`), `date` date, `status` (`tersedia` / `tidak_tersedia` / `belum_dikonfirmasi`) | UQ (`subject_type`, `subject_id`, `date`); booked status never stored (B11) |
| 10 | `events` | FK `client_id` → `users`; `name`, `event_date` date, `guest_count` uint, `theme` N, `description` N, `status` default `draft` | IDX (`client_id`, `status`, `event_date`) |
| 11 | `quote_requests` | FK `client_id` → `users`; FK `event_id` → `events` N (draft events); morph subject; `event_date`, `guest_count` uint N, `budget` uBig N, `notes` N, `catalog_price_snapshot` uBig, `status` default `diajukan` | IDX (`client_id`, `status`), IDX `event_id` |
| 12 | `quote_proposals` | FK `quote_request_id` cascade; `proposed_amount` uBig, `breakdown` json N, `notes` N, `submitted_at` N | IDX `quote_request_id` |
| 13 | `quote_decisions` | FK `quote_request_id`; FK `admin_id` → `users`; `decision` (`disetujui` / `ditolak`), `approved_amount` uBig N (only when approved), `reason` N (required-on-reject enforced in Form Request), `decided_at` | UQ (`quote_request_id`) — technical integrity proposal, one final decision per request |
| 14 | `order_items` | FK `event_id`; `provider_type`, `provider_id`, morph subject; FK `quote_request_id` N; `catalog_price_snapshot` uBig, `proposed_price_snapshot` uBig N, `final_price` uBig N (Admin locks), `status` default `menunggu_admin`, `rejection_reason` N, `objection_deadline` N (B7 — column only, no logic) | IDX (`event_id`, `status`), IDX subject morph |
| 15 | `order_objections` | FK `order_item_id`; FK `provider_user_id` → `users`; `reason`, `status` default `diajukan`, `admin_decision` N, `decided_at` N | IDX `order_item_id` |
| 16 | `audit_logs` | FK `actor_id` → `users` N (system actions); `action`, morph subject, `from_status` N, `to_status` N, `reason` N, `ip` (45) N | IDX (subject morph, `created_at`) |

Status columns menggunakan string yang sesuai dengan label Blade yang sudah ada. PHP enums diperkenalkan sesuai tahap implementasi.

Tidak ada nilai dari open decisions yang dikodekan:

- Deadline B7 tetap null.
- Deposit B5 tetap null.
- Kolom finansial hanya masuk BE-6.

## C.2 Relations

- `users` 1—0..1 untuk masing-masing profile.
- `vendor_profile` 1—N `catalog_items`.
- `venue_profile` 1—N `venues`.
- `venue` 1—N `venue_rooms` dan `venue_facilities`.
- `catalog_item` / `venue_room` 1—N `availability_days` (morph).
- `users` (Klien) 1—N `events`.
- `event` 1—N `quote_requests` dan `order_items`.
- `quote_request` 1—N `quote_proposals`, serta 1—0..1 `quote_decisions`.
- `order_item` 1—N `order_objections`.
- Morph relations digunakan oleh `media`, `price_change_requests`, dan `audit_logs`.

Tidak ada direct `events` ↔ `venues` FK. Lihat analisis B6 pada §C.4.

## C.3 Migration Order (FK-safe)

BE-1 harus selesai terlebih dahulu:

1. Users alter.
2. Client/vendor/venue profiles.

Kemudian BE-2:

1. `provider_bank_accounts`
2. `company_bank_accounts`
3. `catalog_items`
4. `venues`
5. `venue_rooms`
6. `venue_facilities`
7. `media`
8. `price_change_requests`
9. `availability_days`
10. `events`
11. `quote_requests`
12. `quote_proposals`
13. `quote_decisions`
14. `order_items`
15. `order_objections`
16. `audit_logs`

Satu migration per tabel; seluruh migration harus reversible melalui `down()`.

## C.4 B6 Special Examination — Venues per Event

Desain mengarahkan venue booking melalui:

`order_items.subject = venue_room`

Pendekatan ini menggunakan level room dan sesuai dengan kalender per-room.

Tidak ada `venue_id` pada `events`, sehingga schema dapat merepresentasikan kedua kemungkinan: satu venue maupun banyak venue dalam satu event.

**Jika B6 = satu venue aktif per event:**

Ditegakkan melalui application validation saat menambahkan atau menyetujui order items. Sistem menghitung distinct venues melalui relasi room → venue dari seluruh items event tersebut.

Tidak tersedia portable DB-level constraint untuk aturan ini. Dokumen desain sudah mengarahkan penggunaan application validation.

**Jika B6 = banyak venue per event:**

Tidak ada perubahan schema.

Invoice grouping tetap per-event pada tahap BE-6. Cleaning fee muncul sebagai line terpisah untuk setiap venue item (B4). Availability dan booked derivation per room tidak terpengaruh.

### Kesimpulan B6

B6 **bukan hard blocker untuk schema BE-2**, karena schema bersifat decision-neutral.

Namun, B6 **wajib dijawab sebelum validasi order-approval pada BE-5**. Tidak ada asumsi UI atau logic yang akan dibuat sebelum keputusan diberikan.

Jawaban pengguna diminta pada §F.

## C.5 Factories & Seeders

Satu factory untuk setiap model, dengan states:

- Provider: `menunggu` / `terverifikasi`.
- Catalog: `layanan` / `peralatan` × `tetap` / `perlu_penawaran`.
- Venue: dengan atau tanpa cleaning fee dan deposit.
- Event: `draft` / `diajukan`.
- Quote: dengan atau tanpa proposal/decision.

Tests menggunakan factories, bukan seeders, sesuai implementation plan.

Seeder:

- Memperluas local-only demo seeder dengan contoh catalog, venue, dan rooms dalam jumlah kecil.
- Menyediakan data pengembangan yang valid untuk portal yang dilindungi.
- Ditandai khusus local dan tidak boleh dijalankan di production.

## C.6 Database Integrity Tests

Pengujian integritas yang direncanakan:

- FK menolak orphan records (profile, venue → room, event → order item, quote chain).
- UQ availability (`subject`, `date`).
- UQ email.
- UQ nama room/facility per venue.
- Cascade penghapusan room/facility ketika venue dihapus.
- `cleaning_fee` dan `deposit_amount` menerima null (B4/B5).
- `kind` menerima kedua nilai dan query peralatan menggunakan tabel yang sama (B15).
- Amounts disimpan sebagai integer.
- Catalog yang soft-deleted tidak muncul pada default queries.
- Morph scopes dapat menyelesaikan relasi dengan benar.

Pengujian berjalan pada SQLite memory sesuai `phpunit.xml`, dengan foreign-key enforcement aktif melalui konfigurasi.

---

# D. Frontend Wiring Scope — Limited, Approved

| File | Form(s) | Perubahan (Connection Only) |
|---|---|---|
| `auth/login.blade.php` | Login | `action=POST /login`, name attributes, pertahankan `@csrf`; hapus redirect `handleLogin` dan demo email/password prefills (termasuk hardcoded password placeholder); role tabs tetap visual (T1) |
| `auth/register.blade.php` | Register | `action=POST /register`, name attributes pada seluruh input (role radio sudah memiliki name); pertahankan role-toggle JS; hapus redirect `handleRegister` |
| `klien/login`, `vendor/login`, `admin/login` | Portal logins | Submit ke POST `/login` yang sama; hapus `onsubmit="return false"` |
| `admin/profil` | Data akun + ubah sandi | Wiring ke POST `/profil` dan POST `/profil/sandi`; values dari authenticated user; tambahkan logout form |
| `klien/profil` | Data akun + ganti sandi | Sama; email menjadi read-only (sesuai pola Admin); tambahkan logout form |
| `vendor/profil`, `venue/profil` | Data usaha + ganti sandi | Wiring account/business dan password sections; email read-only; tambahkan logout form. Bank dan photo sections tetap prototype (B12/B14 masih open) |
| `partials/demo-bar.blade.php` | — | Bungkus dengan `@if(app()->environment('local'))` (B18); layouts tidak disentuh |

### Boundaries

- Tidak ada redesign.
- Tidak ada perubahan layout, CSS, atau visual.
- Tidak ada perubahan perilaku selain membuat form yang disetujui berfungsi.
- Seluruh accessibility attributes harus dipertahankan.
- Validation errors ditampilkan menggunakan field-error patterns yang sudah ada.
- Halaman di luar daftar ini tetap statis.
- `aksi.js` tidak disentuh.

---

# E. Database Creation Requirements

1. MySQL service berjalan secara lokal (misalnya XAMPP). Keterjangkauan server sudah terbukti melalui pemeriksaan `migrate:status` pada BE-0.
2. Database yang direncanakan:

   ```sql
   CREATE DATABASE pesta_kita
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;
   ```

   Charset dan collation mengikuti `config/database.php`.

3. Pembuatan database membutuhkan izin eksplisit dan belum dilakukan pada sesi ini.
4. `.env` sudah mengarah ke koneksi tersebut; tidak diperlukan perubahan `.env`.

Setelah persetujuan code BE-1:

- Jalankan `php artisan migrate` untuk migration BE-1.
- Jalankan local demo seeder.

Migration BE-2 dijalankan setelah persetujuan BE-2.

**Setiap langkah di atas merupakan write operation dan harus mengikuti gate persetujuan pada §G.**

---

# F. Remaining Blockers & Confirmations

| Item | Jenis | Memblokir |
|---|---|---|
| B6 — satu atau banyak venue per event | Business decision (open) | Validasi BE-5 saja; schema BE-2 netral (§C.4). Mohon dijawab saat diperlukan; tidak ada asumsi yang dibuat. |
| T1 — semantik login role-tab (server redirect berdasarkan role tersimpan) | Technical proposal | BE-1 sign-off; konfirmasi saat approval |
| T2 — akun provider boleh login sebelum verifikasi bisnis; catalog gating ditunda ke B14/BE-3 | Technical proposal yang bersinggungan dengan B14 | Semantik registrasi BE-1; konfirmasi saat approval |
| B5 | Ditunda sesuai instruksi; nullable column, tanpa logic | Tidak memblokir saat ini |
| B7 | Open | BE-5 |
| B11, B12, B14 | Open | BE-3 |
| B1, B2, B3, B8, B9, B10, B13 | Open | BE-6 (hard blockers) |
| B16, B17 | Open | BE-7+ |
| GD extension missing | Environment | Media handling (BE-3+), bukan Batch 1 |
| Folder dasar baru (`app/Enums`, `app/Http/Middleware`, `app/Http/Requests`) | Architecture approval | BE-1; sudah diusulkan dalam implementation plan |

---

# G. Implementation Approval Checklist — Separate Gates

## Gate 1 — BE-1 Code Implementation

- [ ] Migration: users alter + 3 profile tables.
- [ ] Enums.
- [ ] `EnsureRole` middleware + bootstrap alias.
- [ ] Auth/profile controllers + Form Requests.
- [ ] Route changes (POST sebelum catch-all, portal routes terlindungi).
- [ ] Local demo seeder.
- [ ] Portal test rewrite + auth tests baru.
- [ ] Frontend wiring persis sesuai §D.
- [ ] Folder baru sesuai §F.
- [ ] Konfirmasi T1.
- [ ] Konfirmasi T2.

## Gate 2 — Database Creation & Migration Run

- [ ] Buat database `pesta_kita` dengan `utf8mb4` / `utf8mb4_unicode_ci`.
- [ ] Jalankan migration yang telah disetujui.
- [ ] Jalankan local seeder.

Gate 2 dapat diberikan bersamaan dengan Gate 1, tetapi merupakan izin yang terpisah.

## Gate 3 — BE-2 Code Implementation

- [ ] 16 migrations sesuai urutan §C.3.
- [ ] 16 models + relations.
- [ ] Factories dengan states.
- [ ] Perluasan local demo data.
- [ ] Integrity tests sesuai §C.6.

B6 harus dijawab sebelum BE-5, bukan sebelum BE-2.

---

## Final Status & Handover

**BATCH 1 PREPARATION: COMPLETE**

**BE-1 IMPLEMENTATION: NOT STARTED**

**DATABASE CREATION & MIGRATION: NOT STARTED**

**BE-2 IMPLEMENTATION: NOT STARTED**

Persiapan telah selesai. Tidak ada implementasi yang dimulai:

- Tidak ada migrations.
- Tidak ada models.
- Tidak ada controllers.
- Tidak ada middleware.
- Tidak ada routes.
- Tidak ada perubahan frontend.
- Tidak ada perubahan dependencies.
- Tidak ada operasi database.

Proses dihentikan dan menunggu persetujuan eksplisit pengguna untuk masing-masing gate:

1. BE-1.
2. Database creation & migration.
3. BE-2.

Pengguna juga dapat memberikan jawaban untuk B6, T1, dan T2.

**Status akhir: MENUNGGU PERSETUJUAN PENGGUNA.**

---

*End of Batch 1 Preparation Report — Pesta Kita*  
*Date: 16 November 2026*  
*Mode: DOCUMENTATION-ONLY*
