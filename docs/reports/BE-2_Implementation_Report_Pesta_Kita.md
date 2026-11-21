# BE-2 Implementation Report — Pesta Kita

Non-Financial Domain Schema (Gate 3). Disusun 2026-11-16 berdasarkan kondisi repository aktual dan hasil verifikasi yang benar-benar dijalankan.

### Status

**Selesai — terverifikasi.** Seluruh lingkup BE-2 (16 tabel, model + relasi, enum, factory, perluasan seeder demo lokal, integrity tests) diimplementasikan, dimigrasi ke database lokal, dan lulus pengujian. Tidak ada blocker. Pekerjaan BE-3 dan seterusnya tidak dikerjakan.

### Lingkup

Yang dikerjakan (sesuai Gate 3 dan Preparation Report bagian C):

- 16 migration additive, satu per tabel, urutan FK-safe (C.3), seluruhnya reversible (`down()` benar).
- 16 model Eloquent baru beserta relasi (C.2), plus penyelarasan model BE-1 (`User`, `ClientProfile`, `VendorProfile`, `VenueProfile`) dan registrasi morph map.
- 10 enum status baru berdasar label Blade dan aturan bisnis; kolom yang belum menjadi himpunan tertutup dibiarkan string.
- 19 factory (16 model BE-2 + 3 profil) dengan states sesuai C.5.
- Perluasan `LocalDemoSeeder` dengan data domain kecil yang saling terhubung; tetap local-only dan idempoten.
- 38 integrity tests pada 3 file mencakup 14 kategori instruksi §8 / C.6, berjalan pada SQLite memory dengan foreign-key enforcement aktif.

Keputusan bisnis terkunci yang diterapkan: **B4** (cleaning_fee integer rupiah nullable, terpisah dari rental_price), **B15** (satu tabel `catalog_items` dengan `kind` layanan/peralatan, tanpa tabel equipment), **B5** (deposit_amount nullable tanpa logika), **B6** (tanpa `events.venue_id`; venue direpresentasikan lewat `order_items`→`venue_room`, beberapa venue per acara mungkin), **B7** (objection_deadline hanya kolom nullable, tanpa hitungan 24 jam), **B11** (availability hanya 3 status manual, `booked` tidak disimpan), **T2** (status akun ≠ verifikasi bisnis; provider baru `verification_status=menunggu`, `verified_at=null`).

Yang sengaja tidak dikerjakan (di luar lingkup BE-2, milik BE-3+): controller domain, endpoint CRUD katalog, workflow verifikasi Admin, UI manajemen pengguna, wiring frontend katalog, workflow request quote, persetujuan order, invoice/payment/refund/payout, rundown/review/notification, serta perubahan layout/CSS/JS. Tidak ada folder arsitektur baru, package, atau dependency. Laravel Boost dipertahankan. `.env` tidak diubah. Tidak ada commit/push/merge/PR. Tidak ada screenshot.

### File yang dibuat/diubah

Migration (16 baru, `database/migrations/`): skema 16 tabel domain non-finansial, urut FK-safe `2026_11_16_100001`–`100016`.

Model (16 baru, `app/Models/`): `ProviderBankAccount`, `CompanyBankAccount`, `CatalogItem`, `Venue`, `VenueRoom`, `VenueFacility`, `Media`, `PriceChangeRequest`, `AvailabilityDay`, `Event`, `QuoteRequest`, `QuoteProposal`, `QuoteDecision`, `OrderItem`, `OrderObjection`, `AuditLog`. Fillable via attribute, casts integer/enum/datetime, relasi typed + docblock Indonesia; `CatalogItem`/`Venue` memakai SoftDeletes; `Media` memakai `#[Table('media')]`.

Model BE-1 diselaraskan (4 diubah): `User` (inverse `events`, `quoteRequests`, `quoteDecisions`, `reviewedPriceChanges`, `orderObjections`, `auditLogs`), `ClientProfile` (HasFactory), `VendorProfile` (HasFactory + `catalogItems`, `bankAccounts`, `media`), `VenueProfile` (HasFactory + `venues`, `bankAccounts`, `media`).

Provider (1 diubah): `app/Providers/AppServiceProvider.php` — `Relation::enforceMorphMap` dengan 20 alias pendek (`user`, `client_profile`, `vendor_profile`, `venue_profile`, `provider_bank_account`, `company_bank_account`, `catalog_item`, `venue`, `venue_room`, `venue_facility`, `media`, `price_change_request`, `availability_day`, `event`, `quote_request`, `quote_proposal`, `quote_decision`, `order_item`, `order_objection`, `audit_log`).

Enum (10 baru, `app/Enums/`): `CatalogKind`, `PricingMode`, `CatalogItemStatus`, `AvailabilityStatus`, `MediaVisibility`, `PriceChangeStatus`, `EventStatus`, `QuoteRequestStatus`, `QuoteDecisionResult`, `OrderItemStatus`.

Factory (19 baru, `database/factories/`): satu per model BE-2 + `ClientProfileFactory`, `VendorProfileFactory`, `VenueProfileFactory`. Factory profil memakai `User::factory()->state(['role' => ...])` (bukan state role UserFactory) agar profil tidak dibuat ganda lewat afterCreating.

Seeder (1 diubah): `database/seeders/LocalDemoSeeder.php` — blok BE-2 idempoten (firstOrCreate) di atas data BE-1 yang tetap utuh.

Tests (3 baru, `tests/Feature/Database/`): `SchemaIntegrityTest.php`, `DomainRelationsTest.php`, `LocalDemoSeederTest.php`.

Dokumentasi: `docs/AI_AGENT_CONTEXT.md` (bagian 6.14, baris riwayat 9.5, bagian 14), `docs/BACKEND_AGENT_CONTEXT.md` (addendum BE-2), `docs/BACKEND_DATABASE_DESIGN.md` (status skema), dan laporan ini.

### Database

Koneksi aplikasi: MySQL `pesta_kita` @127.0.0.1, `APP_ENV=local` (diverifikasi tanpa menampilkan credential). Migration dijalankan additive; BE-1 tidak di-rollback; tidak ada `migrate:fresh`/`db:wipe`.

|   # | Migration                                               | Tabel                    | File dibuat | Dijalankan (MySQL) |
| --: | ------------------------------------------------------- | ------------------------ | ----------- | ------------------ |
|   1 | `2026_11_16_100001_create_provider_bank_accounts_table` | `provider_bank_accounts` | Ya          | `[2] Ran`          |
|   2 | `2026_11_16_100002_create_company_bank_accounts_table`  | `company_bank_accounts`  | Ya          | `[2] Ran`          |
|   3 | `2026_11_16_100003_create_catalog_items_table`          | `catalog_items`          | Ya          | `[2] Ran`          |
|   4 | `2026_11_16_100004_create_venues_table`                 | `venues`                 | Ya          | `[2] Ran`          |
|   5 | `2026_11_16_100005_create_venue_rooms_table`            | `venue_rooms`            | Ya          | `[2] Ran`          |
|   6 | `2026_11_16_100006_create_venue_facilities_table`       | `venue_facilities`       | Ya          | `[2] Ran`          |
|   7 | `2026_11_16_100007_create_media_table`                  | `media`                  | Ya          | `[2] Ran`          |
|   8 | `2026_11_16_100008_create_price_change_requests_table`  | `price_change_requests`  | Ya          | `[2] Ran`          |
|   9 | `2026_11_16_100009_create_availability_days_table`      | `availability_days`      | Ya          | `[2] Ran`          |
|  10 | `2026_11_16_100010_create_events_table`                 | `events`                 | Ya          | `[2] Ran`          |
|  11 | `2026_11_16_100011_create_quote_requests_table`         | `quote_requests`         | Ya          | `[2] Ran`          |
|  12 | `2026_11_16_100012_create_quote_proposals_table`        | `quote_proposals`        | Ya          | `[2] Ran`          |
|  13 | `2026_11_16_100013_create_quote_decisions_table`        | `quote_decisions`        | Ya          | `[2] Ran`          |
|  14 | `2026_11_16_100014_create_order_items_table`            | `order_items`            | Ya          | `[2] Ran`          |
|  15 | `2026_11_16_100015_create_order_objections_table`       | `order_objections`       | Ya          | `[2] Ran`          |
|  16 | `2026_11_16_100016_create_audit_logs_table`             | `audit_logs`             | Ya          | `[2] Ran`          |

`php artisan migrate:status` akhir: 23 migration Ran — 7 BE-1 batch `[1]` utuh + 16 BE-2 batch `[2]`.

Ketentuan skema utama: uang `unsignedBigInteger` (integer rupiah); softDeletes hanya `catalog_items`/`venues`; cascadeOnDelete hanya `venue_rooms`/`venue_facilities`/`quote_proposals`, FK lain restrict; unique `availability_days(subject_type,subject_id,date)`, `venue_rooms(venue_id,name)`, `venue_facilities(venue_id,name)`, `quote_decisions(quote_request_id)`, `users.email`; `quote_decisions.decided_at` NOT NULL; `audit_logs.ip` string(45) nullable; `order_items.objection_deadline` nullable tanpa logika; tanpa `events.venue_id`.

Reversibility diverifikasi nyata: pada file SQLite sementara, `migrate` (23 tabel) lalu `migrate:rollback --step=16` men-drop 16 tabel BE-2 dalam urutan FK-safe terbalik tanpa error, menyisakan 7 tabel BE-1. File sementara dihapus dan koneksi dikembalikan ke MySQL. Tidak ada rollback pada database lokal yang berisi data.

### Model dan relasi

Seluruh 16 model BE-2 menerapkan relasi sesuai C.2 dan lulus `DomainRelationsTest`:

- `VendorProfile` hasMany `CatalogItem`; `CatalogItem` belongsTo `VendorProfile`.
- `VenueProfile` hasMany `Venue`; `Venue` belongsTo `VenueProfile`, hasMany `VenueRoom`/`VenueFacility`.
- `CatalogItem` & `VenueRoom` morphMany `AvailabilityDay` (subject).
- `User` (Klien) hasMany `Event`; `Event` belongsTo `User` (client_id), hasMany `QuoteRequest`/`OrderItem`.
- `QuoteRequest` belongsTo `User`/`Event`, morphTo subject, hasMany `QuoteProposal`, hasOne `QuoteDecision`.
- `OrderItem` belongsTo `Event`/`QuoteRequest`, morphTo provider (profil penyedia) & subject (catalog_item/venue_room), hasMany `OrderObjection`.
- Morph owner/subject: `Media`, `PriceChangeRequest`, `AvailabilityDay`, `AuditLog`, `ProviderBankAccount` — seluruhnya resolve benar lewat alias `enforceMorphMap` (bukan nama kelas penuh).
- Inverse pada `User`: events, quoteRequests, quoteDecisions, reviewedPriceChanges, orderObjections, auditLogs.

B6 diverifikasi: `events` tidak memiliki kolom `venue_id`, dan satu event dapat memuat beberapa `order_items` yang menunjuk `venue_room` berbeda.

### Factory dan seeder

Factory states (C.5): provider `menunggu`/`terverifikasi`; catalog `peralatan`, `perluPenawaran`, `menungguPersetujuan`, `nonaktif` (default layanan+tetap+aktif); venue `terverifikasi`, `tanpaCleaningFee`, `denganDeposit`; event `diajukan` (default draft); quote `denganEvent`, `denganProposal`, `denganKeputusan` (default tanpa proposal/decision); pendukung lain: media `publik`, price change `disetujui`/`ditolak`, availability `tidakTersedia`/`belumDikonfirmasi`, order item `disetujui`/`ditolak`/`untukVenueRoom`, decision `ditolak`, company bank `nonaktif`, audit log `sistem`. Seluruh factory tervalidasi membuat record (smoke test sementara lulus lalu dihapus, digantikan integrity suite).

Data demo BE-2 (saling terhubung, idempoten): 2 item katalog Dapur Nusantara (`Paket Katering Nusantara` layanan + `Sewa Kursi Tiffany` peralatan), venue `Graha Melati` + 2 ruangan (`Ballroom Utama`, `Aula Serbaguna`) + 4 fasilitas + 3 availability, 1 acara draft Klien (`Resepsi Pernikahan Rina & Dimas`), 1 quote request menunjuk katalog + acara, 1 proposal.

Verifikasi idempotensi: `LocalDemoSeederTest` menjalankan seeder dua kali dan asserts jumlah record identik; di MySQL lokal `php artisan db:seed` dijalankan dua kali dengan hasil identik — `users=4, catalog=2, venues=1, rooms=2, facilities=4, availability=3, events=1, quotes=1, proposals=1`. Akun demo BE-1 tidak digandakan (user tetap 4). Seeder tetap guard `app()->environment('local')` dan dilewati di testing/production (diverifikasi test).

### Hasil pengujian

| Pemeriksaan                                                    | Hasil aktual                                          |
| -------------------------------------------------------------- | ----------------------------------------------------- |
| `php artisan test tests/Feature/Database --compact`            | Lulus — 38 tests, 88 assertions                       |
| `php artisan test --compact` (suite penuh)                     | Lulus — 96 tests, 704 assertions (58 BE-1 + 38 BE-2)  |
| `php artisan migrate` (MySQL lokal, additive)                  | Lulus — 16 tabel BE-2 dibuat                          |
| `php artisan migrate:status`                                   | 23 Ran (7 BE-1 `[1]`, 16 BE-2 `[2]`)                  |
| `php artisan db:seed --force` ×2 (MySQL)                       | Idempoten — jumlah record identik                     |
| Reversibility (SQLite sementara: migrate + rollback --step=16) | Lulus — 16 tabel BE-2 ter-drop FK-safe, BE-1 bertahan |
| Pint (`php vendor/bin/pint` file PHP BE-2)                     | 64 isu gaya diperbaiki; tes ulang tetap lulus         |
| `git diff --check`                                             | Lulus (exit 0)                                        |

Cakupan 14 kategori §8: (1) FK orphan ditolak — 6 test; (2) UQ availability(subject,date); (3) UQ email; (4) UQ nama room per venue (+ boleh lintas venue); (5) UQ nama facility per venue; (6) cascade room/facility saat venue dihapus (forceDelete) + cascade proposal; (7) cleaning_fee/deposit null; (8) kind menerima kedua nilai; (9) peralatan satu tabel catalog_items, tanpa tabel equipment; (10) uang integer (cast + nilai mentah DB); (11) catalog/venue soft-deleted tersembunyi dari query default; (12) morph resolve (media/price change/availability/order provider+subject/audit/quote) + alias tersimpan; (13) relasi quote/proposal/decision/event/order item valid + B6 multi-venue; (14) UQ satu keputusan final per quote request. Ditambah 3 test seeder (local-only, data terhubung, idempoten).

Tidak ada constraint yang dilemahkan dan tidak ada test yang dihapus agar lulus. Foreign-key enforcement aktif via `config/database.php` (`foreign_key_constraints` default true).

### Git status

Branch `master`, HEAD `50bb69f` (tanpa commit baru). Working tree memuat perubahan baseline frontend/BE-1 yang sudah ada sebelumnya (dipertahankan, tidak ditimpa) ditambah artefak BE-2 baru: 16 migration, 16 model, 10 enum, 19 factory, 3 test, perluasan `LocalDemoSeeder`, penyelarasan `User`/profil/`AppServiceProvider`, dan pembaruan dokumentasi. `git diff --check` bersih. Tidak ada commit/push/merge/PR (menunggu instruksi eksplisit).

### Hal yang belum selesai

- Bukan blocker BE-2, melainkan lingkup tahap berikutnya: controller/endpoint CRUD, workflow verifikasi Admin, alur quote/order, dan seluruh fitur keuangan (invoice/payment/refund/payout) belum ada.
- Halaman katalog/venue/acara frontend masih prototipe dan belum terhubung ke skema baru (wiring di luar lingkup BE-2).
- Keputusan bisnis terbuka tidak dikodekan: B6 (batas jumlah venue per acara), B7 (logika kedaluwarsa objection), B11 (status booked turunan), B12/B14 (unggah media & dokumen verifikasi). Kolom terkait sudah tersedia sebagai nullable/string tanpa logika.
- `order_objections.status`/`admin_decision` dan `venues.kind` sengaja string karena belum menjadi himpunan tertutup pada rancangan; perlu keputusan sebelum dijadikan enum.

### Pembaruan konteks

- `docs/AI_AGENT_CONTEXT.md`: bagian 6.14 (hasil BE-2), baris riwayat 9.5 (2026-11-16 BE-2), bagian 14 (status backend + tabel dokumen).
- `docs/BACKEND_AGENT_CONTEXT.md`: addendum kondisi BE-2 terverifikasi.
- `docs/BACKEND_DATABASE_DESIGN.md`: penanda status skema domain non-finansial sudah dimigrasi.
- Laporan ini: `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md`.

### Langkah berikutnya

Titik awal BE-3 yang disarankan (tanpa dikerjakan otomatis): membaca `docs/BACKEND_IMPLEMENTATION_PLAN.md` bagian BE-3 dan `docs/BACKEND_API_CONTRACT.md`, lalu mengaudit skema BE-2 yang kini sudah terpasang sebagai fondasi. Kandidat lingkup BE-3 sesuai plan: katalog CRUD penyedia + verifikasi Admin atas katalog/venue, dengan keputusan terbuka (B14 sembunyikan katalog sebelum terverifikasi, B12 batas unggah) perlu dijawab lebih dulu. Jangan memulai BE-3 tanpa instruksi eksplisit pengguna.
