# Laporan Wiring BE-3b — Kalender Ketersediaan & Pengajuan Harga (Pesta Kita)

Tanggal: 2026-11-16 · Status: **Selesai — terverifikasi** · Tahap: BE-3b

Acuan: `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md` (butir §2/§3 yang menunda kalender ketersediaan & pengajuan harga ke BE-3b), `docs/reports/Frontend_Backend_Wiring_Review_Pesta_Kita.md`, dan aturan terkunci A11. Kondisi awal: BE-1/BE-2/BE-3 terimplementasi; tabel `availability_days` dan `price_change_requests` sudah ada sejak BE-2; seluruh halaman yang bisa di-wiring ke BE-1/BE-2/BE-3 sudah terhubung, sisanya (~60 halaman) menunggu backend BE-3b/BE-4/BE-5/BE-6/BE-7. BE-3b dipilih karena satu-satunya tahap backend tanpa keputusan bisnis yang menggantung.

## 1. Status

Selesai — terverifikasi. Backend kalender ketersediaan (vendor/venue tulis, klien baca) dan alur pengajuan harga (penyedia ajukan → Admin setujui/tolak) diimplementasikan mengikuti pola BE-3, lalu 6 halaman prototipe terkait di-wiring ke backend tanpa redesign. Tidak ada commit/push/merge/PR; tidak ada perintah destruktif; `.env`, credential, dependency, dan konfigurasi autentikasi tidak diubah; Laravel Boost dipertahankan.

## 2. Lingkup

Dikerjakan:

- **Kalender ketersediaan** (`availability_days`, polimorfik `subject_type`/`subject_id`): vendor menandai status per item per tanggal; pemilik venue menandai status per ruangan per tanggal; klien melihat gambaran jadwal penyedia terverifikasi (read-only).
- **Pengajuan harga** (`price_change_requests`): vendor (harga katalog) & venue (harga sewa) mengajukan perubahan harga dengan harga lama dibaca server; Admin menyetujui (harga aktif berubah + audit log) atau menolak (harga tetap + alasan wajib + audit log).
- **Wiring Blade terbatas** (6 halaman): `vendor/kalender-ketersediaan`, `venue/kalender-ketersediaan`, `klien/kalender-ketersediaan`, `vendor/pengajuan-harga`, `venue/pengajuan-harga`, `admin/pengajuan-harga` (+ `admin/pengajuan-harga-detail`).

Sengaja tidak dikerjakan:

- Stok peralatan, pemesanan/lock tanggal, dan notifikasi ke klien → di luar BE-3b.
- Menambah item/venue ke acara draft, pesanan, tagihan, pembayaran, refund, pencairan → BE-4/BE-5+.
- Halaman prototipe di luar 6 halaman ini tetap prototipe.

## 3. Aturan yang dijaga

- **A11 (terkunci)**: harga katalog/sewa tidak pernah berubah lewat input langsung; hanya lewat `PriceChangeRequest` yang disetujui Admin. Selama status `menunggu`, `catalog_price`/`rental_price` lama tetap berlaku — diuji `test_vendor_mengajukan_perubahan_harga_dengan_harga_lama_dari_server`.
- **Kepemilikan dijaga server**: `item_id`/`room_id`/`venue_id` selalu divalidasi lewat policy/`Gate` (`CatalogItemPolicy`, `VenueRoomPolicy`, `VenuePolicy`) terhadap profil milik user login. Status/ID subjek tidak pernah dipercaya dari input.
- **`old_price` dibaca server**, bukan dari klien; `new_price` wajib berbeda dari harga aktif (diperiksa di controller terhadap nilai sebenarnya).
- **Keputusan Admin menulis `AuditLog`** (`price_change_approved` / `price_change_rejected`, `actor_id` = Admin, `to_status`), dengan `reviewed_by`/`reviewed_at`/`decision_note`.
- **Satu pengajuan `menunggu` per subjek**; pengajuan ganda ditolak.
- **Visibilitas klien**: hanya item/venue dari penyedia terverifikasi yang dirender pada kalender klien.
- **Jujur soal status**: view memakai null-guard (`?? collect()`), `data-note`/`empty-state` menyatakan jadwal diisi penyedia dan tetap perlu dikonfirmasi — bukan jaminan ketersediaan.

## 4. Perubahan file

### 4.1 Migration baru (1, additive, reversible)

| File                                                                                   | Isi                                                                                                           |
| -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `database/migrations/2026_11_17_200003_add_decision_note_to_price_change_requests.php` | `price_change_requests` + `decision_note` (text nullable) untuk catatan keputusan Admin. `down()` drop kolom. |

Diterapkan pada MySQL `pesta_kita` sebagai batch `[4]`; `migrate:status` → **26 Ran, 0 pending** (7 BE-1 + 16 BE-2 + 2 BE-3 + 1 BE-3b). Tidak ada migration lama yang diubah.

### 4.2 Model diubah (1)

- `app/Models/PriceChangeRequest.php` — menambah `decision_note` ke atribut fillable.

### 4.3 Form Request baru (5)

| File                                                     | Tanggung jawab                                                           |
| -------------------------------------------------------- | ------------------------------------------------------------------------ |
| `app/Http/Requests/Vendor/UpdateAvailabilityRequest.php` | validasi `item_id` + `date` (≥ hari ini) + `status` enum.                |
| `app/Http/Requests/Venue/UpdateAvailabilityRequest.php`  | validasi `room_id` + `date` (≥ hari ini) + `status` enum.                |
| `app/Http/Requests/Vendor/StorePriceChangeRequest.php`   | validasi `item_id` + `new_price` (integer, min 1) + `reason` (wajib).    |
| `app/Http/Requests/Venue/StorePriceChangeRequest.php`    | validasi `venue_id` + `new_price` (integer, min 1) + `reason` (wajib).   |
| `app/Http/Requests/Admin/DecidePriceChangeRequest.php`   | `reason` wajib saat `reject`, nullable saat `approve` (min 5, max 2000). |

> Catatan: aturan `different:old_price` sengaja tidak dipakai karena `old_price` bukan input klien; pemeriksaan "harga baru harus berbeda" dilakukan di controller membandingkan ke `catalog_price`/`rental_price` aktual.

### 4.4 Controller baru (6)

| Controller                                               | Tanggung jawab                                                                                                        |
| -------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Vendor/AvailabilityController.php` | `index` (item + jadwal ≥ hari ini, limit 180), `update` (Gate update item → `updateOrCreate` availability).           |
| `app/Http/Controllers/Venue/AvailabilityController.php`  | `index` (venue+ruangan milik pemilik login), `update` (kepemilikan via `venue.venueProfile.user_id`, 403 bila asing). |
| `app/Http/Controllers/Vendor/PriceChangeController.php`  | `index` (riwayat per `subject_type=catalog_item`), `store` (old_price server-side, cek beda & duplikat menunggu).     |
| `app/Http/Controllers/Venue/PriceChangeController.php`   | mirror vendor untuk `subject_type=venue` / `rental_price` / `venue_id`.                                               |
| `app/Http/Controllers/Klien/CalendarController.php`      | `index` — hanya item/venue penyedia terverifikasi (`publikUntukKlien`) dengan jadwal tersingkat 180 hari ke depan.    |
| `app/Http/Controllers/Admin/PriceChangeController.php`   | `index`/`detail`/`decide` (dispatcher approve/reject) + `applyNewPrice` + audit log, dalam transaksi `lockForUpdate`. |

### 4.5 Route baru (12, sebelum catch-all `/{page}`)

- Klien: `GET /klien/kalender-ketersediaan` (`klien.kalender`).
- Vendor: `GET|POST /vendor/kalender-ketersediaan` (`vendor.kalender`, `vendor.kalender.update`), `GET|POST /vendor/pengajuan-harga` (`vendor.pengajuan-harga`, `...store`).
- Venue: `GET|POST /venue/kalender-ketersediaan`, `GET|POST /venue/pengajuan-harga` (nama analogous).
- Admin: `GET /admin/pengajuan-harga`, `GET /admin/pengajuan-harga/{priceChange}`, `POST /admin/pengajuan-harga/{priceChange}/{action}` dengan `{action}` dibatasi `whereIn ['approve','reject']`.

Middleware `role:vendor,venue` / `role:admin` dipertahankan; seluruh route eksplisit ditempatkan sebelum route catch-all prototipe.

### 4.6 View di-wiring (6 + 1 detail)

`vendor/kalender-ketersediaan`, `venue/kalender-ketersediaan`, `klien/kalender-ketersediaan` (read-only), `vendor/pengajuan-harga`, `venue/pengajuan-harga`, `admin/pengajuan-harga`, `admin/pengajuan-harga-detail`. Memakai CSS/struktur yang sudah ada; JS progressive-enhancement membaca blok `<script id="kal-config" type="application/json">`.

## 5. Perbaikan bug saat validasi

- **Compile error Blade `@json([...], 0 | JSON_THROW_ON_ERROR)`** pada tiga view kalender: direktif `@json` salah memadung kurung untuk array multi-baris + ekspresi `|`. Diganti dengan menyusun `$kalConfig = json_encode([...], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)` di blok `@php` lalu `{!! $kalConfig !!}` dalam tag `kal-config`.
- **ParseError `Unmatched ')'`** di `klien/kalender-ketersediaan` pada ekspresi `->merge(...)` (semula tertutupi error `@json`): satu `)` berlebih dibuang; struktur divisualkan ulang.
- **Status kode kepemilikan ruangan venue**: `abort_unless(... , 404)` untuk ruangan milik pemilik lain diubah ke **403** agar konsisten dengan penolakan lintas-kepemilikan pada jalur vendor (bukan "tidak ditemukan" tapi "bukan milik Anda").

## 6. Pengujian

- **Feature baru**: `tests/Feature/Portal/AvailabilityPriceChangeTest.php` — **13 tes / 49 assertion** lulus. Mencakup: vendor menandai ketersediaan sendiri; ditolak untuk item vendor lain (403); tanggal masa lalu ditolak; pemilik venue menandai ruangan sendiri; ditolak untuk ruangan pemilik lain (403); klien hanya melihat penyedia terverifikasi; pengajuan harga dengan old_price server-side & harga tetap (A11); harga sama ditolak; pengajuan ganda ditolak; Admin approve mengubah harga + audit; Admin reject wajib alasan + harga tetap + audit; pengajuan sudah diputus tidak dapat diputus ulang; guard role & guest.
- **Regresi penuh**: `php vendor/bin/phpunit` → **239 tes / 1225 assertion** OK (0 gagal).
- **Gaya kode**: Pint `--test` lulus pada seluruh file BE-3b (line ending/import dirapikan pada file baru; file lama tak terkait tidak diubah).
- **View**: `php artisan view:cache` → "Blade templates cached successfully" (seluruh Blade ter-compile); `view:clear` dijalankan setelahnya.

## 7. Verifikasi manual yang disarankan

Alur browser (login → kalender → tandai tanggal → pengajuan harga → keputusan Admin) dapat dijalankan atas permintaan; verifikasi otomatis sejauh ini lewat suite feature di atas.
