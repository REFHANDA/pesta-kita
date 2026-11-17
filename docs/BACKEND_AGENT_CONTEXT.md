# Backend Agent Context — Pesta Kita

> Audit read-only BE-0 pada 2026-11-15. Dokumen ini mencatat kondisi backend yang benar-benar ada, lalu memisahkan rancangan yang belum diterapkan.
>
> Frontend tetap acuan kebutuhan. Jangan menganggap rancangan di dokumen saudara sebagai kode yang sudah jalan.
>
> **Pembaruan 2026-11-17:** bagian 1–8 adalah snapshot historis sebelum BE-1. Bagian 11 mencatat BE-1–BE-3; **bagian 12** memperbarui status hingga BE-7. Jangan membaca snapshot lama sebagai kondisi saat ini.

## 1. Status sesi

| Item                                             | Status                                                                                                                                                                      |
| ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tahap                                            | BE-0 audit dan pemetaan                                                                                                                                                     |
| Kode aplikasi                                    | Tidak diubah pada sesi ini                                                                                                                                                  |
| Migration, model domain, controller, route, auth | Belum dibuat                                                                                                                                                                |
| Persetujuan implementasi kode                    | Belum diberikan. Berhenti di rancangan. Keputusan bisnis B4/B15/B18, Boost, dan wiring frontend terbatas disetujui 2026-11-16 (lihat `BACKEND_BUSINESS_RULES.md` bagian E). |

## 2. Fakta repository (diperiksa 2026-11-15)

- Branch aktif: `master`, HEAD `50bb69f` — pemisahan antarmuka Vendor dan Pemilik Venue.
- Working tree kotor dan belum di-commit: view, CSS, JS, `routes/web.php`, `composer.json`, `composer.lock`, `CLAUDE.md`, plus file tak terlacak (`docs/`, `boost.json`, `.mcp.json`, `.claude/`, skrip, tes portal).
- PHP `8.4.4`. Laravel Framework `13.40.0`. `composer.json` mensyaratkan `php: ^8.3` dan `laravel/framework: ^13.17`.
- `laravel/boost` ada di `require-dev` pada working tree. Bukan bagian commit HEAD. Pada sesi ini namespace MCP Boost tidak tersedia. Jangan menganggap Boost sebagai alat yang sudah terpasang di editor.
- Tidak ada `routes/api.php`. Tidak ada controller domain, Form Request, Policy, Resource, Event, Listener, Job, atau Service.
- Isi `app/` yang ada: `Models/User.php` (name, email, password), `Http/Controllers/Controller.php` kosong, `Providers/AppServiceProvider.php`.
- `bootstrap/app.php` mendaftarkan `routes/web.php`, `routes/console.php`, dan health `/up`. Closure middleware kosong. Tidak ada alias role.
- `routes/console.php` hanya perintah `inspire`. Tidak ada jadwal.
- Tes yang ada: `tests/Feature/ProjectPortalPagesTest.php` (setiap view role harus `assertOk` tanpa login), `tests/Feature/ProjectPortalAccessibilityTest.php`, plus contoh PHPUnit. `phpunit.xml` memakai SQLite memori untuk tes, terpisah dari MySQL aplikasi.
- Jumlah Blade: 121. Per folder: admin 40, klien 35, vendor 20, venue 16, auth 2, layouts 5, partials 1, ditambah view di luar folder itu (termasuk landing). Angka README (Klien 28, Vendor 15, Venue 13, Admin 35) sudah tertinggal.

## 3. Database aktual

- Konfigurasi default: `database.default = mysql` (`php artisan config:show database.default`).
- `php artisan migrate:status` gagal: `SQLSTATE[HY000] [1049] Unknown database 'pesta_kita'` di `127.0.0.1:3306`. Database belum dibuat. Migrasi belum pernah diterapkan pada instance ini.
- File migrasi yang ada hanya bawaan Laravel:
    - `users`, `password_reset_tokens`, `sessions`
    - `cache`, `cache_locks`
    - `jobs`, `job_batches`, `failed_jobs`
- `users` tidak punya kolom role, telepon, atau status.
- `DatabaseSeeder` membuat satu `test@example.com`, bukan empat akun portal.
- Tidak ada foreign key domain, soft delete domain, atau data katalog.

## 4. Autentikasi dan role aktual

Empat nama portal ada sebagai prefix URL, bukan sebagai role yang ditegakkan server.

| Mekanisme            | Fakta                                                                                                                                                                                                 |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Guard                | `web` session, provider Eloquent `User`. File `config/auth.php`. Tidak ada guard terpisah per role.                                                                                                   |
| Login `/login`       | Form mencegah submit (`handleLogin`). Peran dipilih di browser, lalu `window.location` diarahkan ke `/klien`, `/vendor`, `/venue`, atau `/admin`. Email dan kata sandi tidak dikirim dan tidak dicek. |
| Register `/register` | Form mencegah submit. Radio `klien` / `vendor` / `venue`. Admin tidak bisa mendaftar sendiri. Teks UI: akun mitra aktif setelah Admin memverifikasi dokumen dan rekening. Tidak ada penyimpanan.      |
| Logout `/logout`     | GET yang mengarahkan ke `/login`. Tidak ada sesi yang dihancurkan.                                                                                                                                    |
| Portal               | Empat grup GET. Beranda eksplisit. `/{page}` merender `resources/views/{role}/{page}.blade.php` jika file ada. Tidak ada middleware `auth` atau role.                                                 |
| Demo bar             | `partials/demo-bar.blade.php` menautkan langsung ke empat portal.                                                                                                                                     |
| Tes                  | `ProjectPortalPagesTest` mengharapkan halaman role tetap 200 tanpa autentikasi.                                                                                                                       |

Menyembunyikan menu di Blade bukan pengaman. Siapa pun yang mengetahui URL dapat membuka halaman role lain, termasuk form yang menampilkan nomor rekening contoh dan data prototipe.

## 5. Backend domain yang sudah ada

Tidak ada. Seluruh halaman operasional adalah Blade statis. Form bermakna memakai `onsubmit="return false"` atau hanya membuka dialog pratinjau `aksi.js`. Nominal, status, dan nomor transaksi di HTML adalah contoh.

Satu-satunya perilaku server di luar merender view: health `/up`, redirect logout, dan query string `tanggal` yang hanya ditampilkan ulang di `klien/permintaan-penawaran` tanpa disimpan.

## 6. Hubungan dokumen

| Dokumen                                         | Isi                                              | Sifat                                |
| ----------------------------------------------- | ------------------------------------------------ | ------------------------------------ |
| `docs/AI_AGENT_CONTEXT.md`                      | Konteks frontend dan aturan bisnis terkunci      | Fakta implementasi frontend + aturan |
| `docs/roadmap_pembaruan_frontend_pesta_kita.md` | Kebutuhan tampilan Tahap 0–8                     | Acuan FE, bukan skema DB             |
| `docs/reports/tahap-4` sampai `tahap-6`         | Hasil UI yang sudah dibuat                       | Fakta tampilan                       |
| `docs/BACKEND_AGENT_CONTEXT.md`                 | Kondisi backend hasil audit                      | Fakta                                |
| `docs/BACKEND_BUSINESS_RULES.md`                | Aturan terkunci dan keputusan yang masih terbuka | Aturan + pertanyaan                  |
| `docs/BACKEND_DATABASE_DESIGN.md`               | Skema usulan                                     | Rancangan, belum dimigrasi           |
| `docs/BACKEND_API_CONTRACT.md`                  | Route dan data per halaman                       | Rancangan, belum ada endpoint        |
| `docs/BACKEND_IMPLEMENTATION_PLAN.md`           | Urutan BE-1 sampai BE-8                          | Rekomendasi, menunggu persetujuan    |

Jika dokumen rancangan bertentangan dengan kode, kode menang untuk kondisi saat ini. Jika rancangan bertentangan dengan aturan bisnis terkunci, aturan bisnis menang.

## 7. Perbedaan dokumentasi lama terhadap kode

- Laporan Tahap 4–6 yang menyebut “tidak ada controller/model/API domain” masih benar pada 2026-11-15.
- Route portal masih tanpa auth. Catatan historis itu masih berlaku.
- README menggambarkan login empat peran seolah kredensial demo berfungsi. Kode login tidak memverifikasi kredensial.
- README menulis contoh konfigurasi database. Jangan menyalin rahasia environment ke dokumen atau chat.
- `admin/pembatalan.blade.php` masih menampilkan persentase 70% dan estimasi refund berlabel contoh. Halaman detail pembatalan sudah menyatakan nominal menunggu backend. Jangan mengunci 70% sebagai kebijakan.
- `venue/rundown.blade.php` menyalin teks bahwa pemilik venue hanya melihat, tetapi toolbar masih punya tombol tambah item. Hak akses final harus diputuskan di backend: venue read-only.
- Kalender frontend hanya punya tiga status manual. Status “sudah dipesan” harus diturunkan dari pesanan terkonfirmasi, bukan diketik penyedia.

## 8. Larangan sampai ada persetujuan

Jangan membuat migration, mengubah database, menambah controller, model, endpoint, atau middleware, mengubah frontend, memasang dependency, atau mengubah `.env` sebelum pengguna menyetujui rancangan dan keputusan terbuka di `BACKEND_BUSINESS_RULES.md`.

## 9. Addendum 2026-11-16 — audit ulang BE-0 dan keputusan Batch 1

- Audit BE-0 dijalankan ulang secara read-only terhadap repository aktual dan mengonfirmasi seluruh fakta pada dokumen ini (Laravel 13.40.0, PHP 8.4.4, route portal tanpa auth, database `pesta_kita` belum ada, 121 view Blade). Baseline `php artisan test --compact`: 4 lulus, 240 assertions, lingkungan tes terisolasi (SQLite memori).
- Keputusan bisnis disetujui pengguna 2026-11-16: **B4, B15, B18**, mempertahankan **Laravel Boost**, dan **wiring frontend terbatas** (login, registrasi, profil pengguna, ganti sandi). Rincian: `BACKEND_BUSINESS_RULES.md` bagian E.
- Persetujuan implementasi kode **belum diberikan**. Persiapan Batch 1 (rencana teknis BE-1 dan rancangan skema BE-2) disusun sebagai laporan; tidak ada migration, model, controller, middleware, route, atau frontend yang diubah pada sesi persiapan.
- Dampak keputusan ke skema: B15 mengonfirmasi `catalog_items.kind` tanpa tabel peralatan terpisah; B4 membuat `venues.cleaning_fee` nullable dan terpisah dari `rental_price`; B5 tetap ditunda dengan kolom nullable; B18 tanpa dampak skema. B6 masih terbuka: relasi acara–venue melalui `order_items` netral terhadap kedua opsi, validasi aplikasi menunggu jawaban.
- Temuan baru audit ulang: placeholder kata sandi demo tertulis di HTML `auth/login`; ekstensi `gd` belum aktif; disk `local` (`serve=true`) dapat diakses lewat route `storage/{path}` saat env local; belum ada UI logout di view mana pun; `welcome.blade.php` bawaan masih ada.

## 10. Addendum 2026-11-16 — BE-1 dan BE-2 terimplementasi (kondisi terverifikasi)

Bagian ini menggantikan klaim "belum dibuat" pada bagian 1–8 untuk skema domain non-finansial. Fakta di bawah diverifikasi nyata terhadap repository dan database lokal, bukan rancangan.

- **Gate:** BE-1 (Gate 2) dan BE-2 (Gate 3) disetujui pengguna dan sudah dilaksanakan. Jangan mengulang implementasinya. BE-3 dan seterusnya menunggu instruksi eksplisit.
- **Database:** MySQL `pesta_kita` @127.0.0.1, `APP_ENV=local`. `php artisan migrate:status` → **23 Ran**: 7 migration BE-1 batch `[1]` (bawaan + `users` role/phone/status + `client_profiles`/`vendor_profiles`/`venue_profiles`) utuh, dan 16 migration BE-2 batch `[2]`. Migrasi additive; BE-1 tidak di-rollback; tanpa `migrate:fresh`/`db:wipe`.
- **16 tabel BE-2:** `provider_bank_accounts`, `company_bank_accounts`, `catalog_items`, `venues`, `venue_rooms`, `venue_facilities`, `media`, `price_change_requests`, `availability_days`, `events`, `quote_requests`, `quote_proposals`, `quote_decisions`, `order_items`, `order_objections`, `audit_logs`. Satu migration per tabel, urut FK-safe (`2026_11_16_100001`–`100016`), seluruhnya reversible (`down()` benar).
- **Model & relasi:** 16 model Eloquent baru + penyelarasan `User`/`ClientProfile`/`VendorProfile`/`VenueProfile`. `Relation::enforceMorphMap` mendaftarkan 20 alias pendek di `AppServiceProvider`; kolom morph menyimpan alias, bukan nama kelas penuh. Uang `unsignedBigInteger` (integer rupiah) + cast integer; softDeletes hanya `catalog_items`/`venues`; cascadeOnDelete hanya `venue_rooms`/`venue_facilities`/`quote_proposals`, FK lain restrict.
- **Enum:** 10 enum status baru (`CatalogKind`, `PricingMode`, `CatalogItemStatus`, `AvailabilityStatus`, `MediaVisibility`, `PriceChangeStatus`, `EventStatus`, `QuoteRequestStatus`, `QuoteDecisionResult`, `OrderItemStatus`). Kolom yang belum menjadi himpunan tertutup (`order_objections.status`/`admin_decision`, `venues.kind`) sengaja string.
- **Factory & seeder:** 19 factory (16 BE-2 + 3 profil) dengan states C.5. `LocalDemoSeeder` diperluas dengan data domain kecil saling terhubung, tetap guard `app()->environment('local')` dan idempoten (`firstOrCreate`). `db:seed` ×2 di MySQL menghasilkan jumlah identik (users=4, catalog=2, venues=1, rooms=2, facilities=4, availability=3, events=1, quotes=1, proposals=1); akun BE-1 tidak digandakan.
- **Keputusan terkunci diterapkan:** B4 (cleaning_fee integer rupiah nullable, terpisah dari rental_price), B15 (satu `catalog_items` dengan `kind` layanan/peralatan, tanpa tabel equipment), B5 (deposit_amount nullable tanpa logika), B6 (tanpa `events.venue_id`; venue via `order_items`→`venue_room`, boleh banyak venue per acara), B7 (objection_deadline hanya kolom nullable), B11 (availability hanya 3 status manual, tanpa booked), T2 (status akun ≠ verifikasi bisnis; provider baru `verification_status=menunggu`, `verified_at=null`).
- **Pengujian:** `php artisan test --compact` → **96 lulus (704 assertions)**; 58 BE-1 + 38 BE-2. Integrity suite (`tests/Feature/Database/`: `SchemaIntegrityTest`, `DomainRelationsTest`, `LocalDemoSeederTest`) mencakup 14 kategori §8 pada SQLite memory dengan foreign-key enforcement aktif. Tidak ada constraint dilemahkan atau test dihapus agar lulus.
- **Reversibility:** dibuktikan pada file SQLite sementara — `migrate` (23 tabel) lalu `migrate:rollback --step=16` men-drop 16 tabel BE-2 urut FK-safe terbalik, menyisakan 7 tabel BE-1; file sementara dihapus, koneksi kembali ke MySQL. Tidak ada rollback pada database lokal berisi data.
- **Batasan yang tetap berlaku:** belum ada controller domain, endpoint CRUD, workflow verifikasi Admin, alur quote/order, atau fitur keuangan (invoice/payment/refund/payout), rundown/review/notification. Halaman frontend masih prototipe, belum terhubung ke skema baru. Laravel Boost dipertahankan; tanpa package/dependency baru, tanpa perubahan `.env`, tanpa commit/push/merge/PR.
- **Rujukan:** `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md`, `docs/AI_AGENT_CONTEXT.md` bagian 6.14, dan `docs/BACKEND_DATABASE_DESIGN.md` (penanda status skema).

## 11. Addendum 2026-11-16 — BE-3 terimplementasi (Gate 4B, kondisi terverifikasi)

Bagian ini memperbarui bagian 10 untuk lingkup BE-3 (media, katalog vendor, venue, verifikasi mitra, visibilitas Klien, wiring Blade terbatas). Fakta diverifikasi terhadap repository, database lokal, dan hasil tes aktual.

- **Gate:** Gate 4A (laporan persiapan) dan Gate 4B (implementasi) disetujui pengguna 2026-11-16 dengan 10 ketentuan (lihat `BACKEND_BUSINESS_RULES.md` bagian F). BE-3 selesai; BE-3b (kalender, pengajuan harga) dan BE-4+ menunggu instruksi eksplisit.
- **Database:** `migrate:status` → **25 Ran**: 7 BE-1 batch `[1]` + 16 BE-2 batch `[2]` utuh, 2 migration additive BE-3 batch `[3]` (`2026_11_16_200001` kolom media: kind/doc_type/disk/mime_type/size/original_name + index; `2026_11_16_200002` kolom verifikasi vendor_profiles/venue_profiles: verification_note/reviewed_by FK users nullOnDelete/verification_submitted_at). Reversibility dibuktikan ulang pada SQLite sementara (rollback + migrate ulang kedua migration); tanpa rollback di MySQL berisi data.
- **Enum baru (3):** `VerificationStatus` (menunggu/terverifikasi/ditolak/nonaktif), `MediaKind` (foto/dokumen_verifikasi), `VerificationDocType` (ktp/nib/npwp/bukti_venue + label()).
- **Disk & keamanan media:** disk `private` (root `storage/app/private`, `serve=false`); disk `local` juga diubah `serve=false`. Satu-satunya jalur baca `GET /media/{media}` (MediaController@show) berotorisasi policy; dokumen verifikasi privat selamanya; foto menjadi publik saat approve. Tanpa symlink `public/storage`.
- **Controller baru (8):** `MediaController`, `VerificationController`, `Vendor\CatalogItemController`, `Venue\VenueController`, `Venue\VenueRoomController`, `Admin\ProviderVerificationController`, `Admin\VenueController`, `Klien\CatalogController`. Form Request baru (12) dan Policy baru (5: CatalogItem, Media, ProviderProfile, Venue, VenueRoom) terdaftar di `AppServiceProvider`.
- **Aturan bisnis diterapkan:** B12 (foto JPG/JPEG/PNG/WebP ≤5 MB, maks 10/subjek; dokumen PDF/JPG/JPEG/PNG ≤5 MB; validasi MIME server), B14 (Vendor wajib KTP; Venue wajib KTP+bukti_venue; NIB/NPWP opsional; tanpa bukti rekening; satu dokumen aktif per jenis, ganti setelah unggah baru sukses; tolak wajib alasan; approve mempublikasikan foto dan venue mengikuti profil kecuali yang dinonaktifkan individual; keputusan tidak menyentuh status akun T2), keputusan butir 2 (tanpa persetujuan Admin per item; perubahan harga langsung tersimpan), butir 8 (`cleaning_fee` hanya Admin via `Admin\VenueController`).
- **Visibilitas Klien:** scope `publikUntukKlien` pada `CatalogItem` (item aktif + vendor terverifikasi) dan `Venue` (venue terverifikasi + profil terverifikasi); detail/profil penyedia 404 bila tidak memenuhi syarat; kontak pribadi tidak pernah dirender; filter q/category/max_price (vendor) dan q/kind/max_price (venue) di server.
- **Wiring Blade (20 view, tanpa redesign):** vendor (layanan, peralatan, item-form, peralatan-form, verifikasi, profil), venue (data, form, verifikasi, profil), admin (admin-penyedia, mitra-detail, admin-venue-form), klien (katalog-vendor, katalog-peralatan, katalog, katalog-vendor-detail, katalog-venue-detail, penyedia-vendor, penyedia-venue). Form memakai route()+@csrf+@method; atribut prototipe `data-form`/`data-confirm` dicegah agar tidak dibajak `aksi.js`; semua view tahan render tanpa data (catch-all portal) lewat null guard.
- **Pengujian:** `php artisan test` → **220 lulus (1139 assertions)**; 96 baseline BE-1/BE-2 + 124 BE-3 (schema additions, policy, media, catalog CRUD, venue CRUD, verification workflow, catalog visibility, portal pages/accessibility diperbarui). Trait `Tests\Concerns\MakesUploadedFiles` untuk unggahan tanpa ekstensi GD. Pint lulus 142 file; `view:cache` seluruh Blade sukses; `route:list` 62 route; `git diff --check` bersih.
- **Batasan yang tetap berlaku:** kalender ketersediaan dan pengajuan harga (BE-3b), tambah item ke acara draft (BE-4/BE-5), stok peralatan, rekening bank UI, dan seluruh fitur keuangan belum ada backend-nya. Halaman di luar 20 view wiring tetap prototipe. Laravel Boost dipertahankan; tanpa dependency baru, tanpa perubahan `.env`, tanpa commit/push/merge/PR.
- **Rujukan:** `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md`, `docs/reports/BE-3_Preparation_Report_Pesta_Kita.md`, `docs/AI_AGENT_CONTEXT.md` bagian 6.15, `docs/BACKEND_BUSINESS_RULES.md` bagian B/E/F.

## 12. Addendum 2026-11-17 — BE-5–BE-7 terimplementasi

Bagian ini memperbarui status backend terkini. Detail baseline dan hasil BE-1–BE-4 di atas tetap menjadi catatan historis tahap masing-masing.

- **BE-5 — acara/pesanan:** Klien membuat/mengubah draft, mengelola item katalog/ruangan, lalu mengajukan acara. Admin memutuskan item; Vendor/Venue mengajukan keberatan dalam 24 jam sejak persetujuan. Satu acara dapat mempunyai beberapa venue. Invoice hanya dapat terbit setelah semua item dan masa keberatan selesai.
- **BE-6 — finansial:** invoice berisi snapshot dan versi, kode 3 digit masuk total, komisi 10% dihitung dari harga penyedia, unggah bukti privat dan verifikasi nominal Admin, rekening perusahaan, pencairan satu kali per item, serta penghapusan bukti setelah lima tahun sejak acara ditutup. Invoice kedaluwarsa tidak membatalkan pesanan otomatis.
- **Refund:** hanya intake permohonan dan tampilan antrean/tinjauan. Tidak ada endpoint keputusan, nominal final, atau transfer refund sampai kebijakan B1 disetujui.
- **BE-7 — rundown/ulasan:** Klien dan Vendor terkait mengelola rundown; Venue/Admin read-only. Klien dapat mengulas setelah acara selesai dan invoice lunas; penyedia hanya melihat agregat anonim.
- **Batas teknis:** jika invoice diterbitkan setelah H-7, jatuh tempo 24 jam kemudian, dengan batas akhir tanggal acara karena waktu acara belum tersedia dalam skema. Bukti tetap tersimpan privat.
- **Validasi:** suite penuh PHPUnit **315 tests / 1.609 assertions lulus**; Pint, `view:cache`, `route:list --except-vendor`, dan `git diff --check` berhasil. Tes memakai SQLite in-memory. Migration tidak dijalankan pada database MySQL lokal selama sesi ini; verifikasi deployment masih terpisah.
- **Rujukan:** `docs/BACKEND_API_CONTRACT.md`, `docs/BACKEND_BUSINESS_RULES.md` bagian B/C/H, dan addendum §8 `docs/reports/Laporan_Update_Terbaru_Frontend_Backend_Pesta_Kita.md`.
