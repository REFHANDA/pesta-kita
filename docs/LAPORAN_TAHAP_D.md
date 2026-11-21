# Laporan Tahap D — Rekomendasi Paket, Ruangan, Pembersihan

Tanggal: 21 November 2026
Branch: `sinkronisasi-rancangan`
Basis: hasil Tahap C (510 tes hijau).

## Ringkasan

| Sub-tahap | Isi | Commit |
|---|---|---|
| D0 | Pemeriksaan dan jawaban pertanyaan | — (bagian di bawah) |
| D1 | `PackageRecommender`, UI rekomendasi, rating katalog | `D1: rekomendasi paket berbasis anggaran dan ketersediaan` |
| D2 | Denah per ruangan, susunan ruangan | `D2: denah per ruangan dan susunan ruangan acara` |
| D3a | Audit route/tautan/teks/float/migrasi | `D3a: audit ...` + `D3a: rollback migrasi legacy idempoten ...` |
| D3b | Pembersihan komentar dan berkas yatim | termasuk di commit D3 |
| D3c | Sinkronisasi dokumen | commit dokumen |
| D3d | Verifikasi akhir | 510 tes, pint bersih, route cache berhasil |

## D0 — Jawaban pemeriksaan

### Harga bagi klien dan cara perhitungannya
- `POST /klien/acara/{event}/pesanan` (`klien.acara.pesanan.store`) → `Klien\EventController::addItem`.
  - Item katalog: `provider_price` = `catalog_price` (snapshot `catalog_price_snapshot`).
  - Venue: `provider_price` = `venues.rental_price`.
  - **Komisi tidak ditambahkan di titik ini** (`commission = 0`).
- Komisi 10% dihitung saat Admin menyetujui pesanan (`Admin\OrderReviewController`) dan biaya kebersihan venue ditambahkan `InvoiceIssuer` saat tagihan terbit.
- Karena itu rekomendasi memakai harga tambah item (`catalog_price`/`rental_price`) agar tombol "Tambah ke acara" menampilkan angka yang sama dengan yang tersimpan pada pesanan. Dibuktikan tes `test_harga_rekomendasi_sama_dengan_harga_saat_item_ditambahkan` (12.345.000 pada kandidat = 12.345.000 pada `order_items.provider_price`).

### Kategori
- `catalog_items.category` adalah string bebas. Seeder demo memakai `Katering & F&B` dan `Peralatan Pesta`.
- Pemetaan yang dipakai (huruf kecil, di-trim): `katering & f&b`, `katering`, `catering`, `makanan & minuman` → catering; `dekorasi`, `dekorasi & tata rias`, `dekorasi & bunga` → dekorasi; `hiburan`, `musik & hiburan`, `entertainment` → hiburan. Peralatan memakai `kind = peralatan`. Kategori lain (mis. `Fotografi`, `Venue / Gedung pertemuan`, `Undangan`) diabaikan dan tidak menghasilkan kandidat.

### Media
- `MediaKind` awal: `foto`, `dokumen_verifikasi`, `bukti_refund`, `bukti_pencairan`, `bukti_pembayaran`; Tahap D menambah `denah`.
- `Media` polymorphic `owner` + `visibility` (`publik`/`privat`), disk `private`, `serve=false`; `StorePhotoRequest` hanya JPG/JPEG/PNG/WebP ≤5 MB.
- Foto profil/portofolio maksimal 10; denah maksimal 3 per ruangan.

### Tab "Data & denah venue"
- `venue/data.blade.php` berisi daftar venue + tombol kelola; ruangan dikelola pada `venue/form.blade.php`. Sebelum D2 tidak ada unggahan denah sama sekali; sekarang ada blok denah per ruangan pada form tersebut.

## D1 — Rekomendasi paket

`app/Services/PackageRecommender.php` (tanpa AI, deterministik, read-only):

1. **Alokasi** dari `Setting::get('budget_allocation')`, bawaan 35/30/15/10/5/5. Setiap kategori `intdiv(budget × persen, 100)`; dana cadangan = `budget − jumlah kategori` sehingga selalu sama dengan anggaran (contoh 100.000.001 → cadangan 5.000.001).
2. **Kandidat venue**: `publikUntukKlien()`, `capacity ≥ tamu`, `rental_price ≤ alokasi`, punya ruangan dengan kapasitas cukup yang tersedia pada tanggal (`AvailabilityService::isVenueRoomAvailable`).
3. **Kandidat layanan**: `publikUntukKlien()`, kategori terpetakan, `catalog_price ≤ alokasi`, tersedia (`isCatalogItemAvailable`).
4. **Kandidat peralatan**: `kind = peralatan`, harga ≤ alokasi, tersedia.
5. **Urutan**: rating menurun (tanpa rating di akhir) → harga menaik → id; maksimal 3.
6. **Ringkasan**: kandidat teratas per kategori yang masih muat sisa anggaran, total estimasi, sisa anggaran. Seluruh nominal `int`.
7. **Empty state**: bila anggaran/tamu/tanggal kosong, `ready=false` + daftar kekurangan; UI menampilkan "Rekomendasi belum dapat dibuat karena …".

UI: bagian "Rekomendasi" pada detail acara **draft**, memuat teks persis:

> Rekomendasi otomatis berdasarkan anggaran, jumlah tamu, tanggal, dan aturan alokasi. Harga dan ketersediaan final dikonfirmasi saat pesanan diajukan.

Ditambah tabel alokasi, dana cadangan, tabel kandidat (nama, penyedia, harga, rating hanya bila jumlah > 0), tombol "Tambah ke acara" (POST ke endpoint pesanan yang sudah ada), dan ringkasan paket. Route eksplisit `klien.acara.rekomendasi` mengarah ke `#rekomendasi`. `Route::view('/rekomendasi')` dan `klien/rekomendasi.blade.php` dihapus (`/klien/rekomendasi` → 404).

Rating katalog: kartu katalog vendor/peralatan menampilkan bintang + jumlah penilaian vendor, katalog venue menampilkan rating venue, hanya jika jumlah > 0.

Tes: `tests/Feature/Rekomendasi/PackageRecommenderTest.php` (9 tes) mencakup alokasi, perubahan setting, filter kapasitas/harga/ketersediaan/verifikasi, pemetaan kategori, urutan rating, batas 3, ringkasan ≤ anggaran dan `int`, akses pemilik, hanya draft, tanpa penulisan database.

## D2 — Denah dan susunan ruangan

### Denah
- `MediaKind::Denah`; `VenueRoom::media()`; `Media::providerProfile` mengenali `VenueRoom` lewat venue induk.
- Route `venue.ruangan.denah.store` (POST, multipart `photo`), otorisasi `VenueRoomPolicy::manageMedia`.
- Maksimal 3 per ruangan; kelebihan ditolak dengan pesan validasi.
- Visibilitas: `publik` bila profil penyedia `terverifikasi`, selain itu `privat` (tidak dapat diakses klien).
- UI unggah/hapus pada form venue per ruangan; klien melihat denah pada detail venue dengan alt "Denah {ruangan} di {venue}", atau "Belum ada denah untuk ruangan ini."

### Susunan ruangan
- Enum `App\Enums\RoomLayout`: Teater, Meja Bundar, Kelas, Huruf U, Prasmanan Berdiri, Lesehan.
- Migrasi `2026_11_21_000002_add_room_layout_to_events_table`: `room_layout` varchar(30) nullable, `room_layout_notes` varchar(500) nullable.
- `PUT /klien/acara/{event}/susunan-ruangan` (`klien.acara.layout.update`), enum tetap + catatan ≤500, audit `event_room_layout_updated`.
- Dapat diubah pada `draft`, `diajukan`, `menunggu_pembayaran`, `terkonfirmasi`; **403** setelah `selesai`/`dibatalkan`.
- Venue melihatnya pada halaman rundown acara; Admin pada detail acara.

Tes: `tests/Feature/Denah/RoomLayoutTest.php` (7 tes) mencakup batas 3, penolakan bukan pemilik/bukan gambar, transisi privat → publik, perubahan enum, nilai tidak dikenal, penguncian, pembacaan venue/Admin, dan tidak ada perubahan data uang.

## D3 — Pembersihan dan sinkronisasi

### D3a — Audit otomatis (`tests/Feature/Audit/TahapDAuditTest.php`, 5 tes)
- Semua route bernama `klien.*`/`vendor.*`/`venue.*`/`admin.*` (kecuali `*.login`) memiliki middleware `auth` **dan** `role:`.
- 26 halaman utama tiap portal merespons 200 dan tidak memuat `href="#"` maupun `javascript:void`.
- Tidak ada teks `belum terhubung`, `prototipe`, `lorem ipsum`, `data dummy`, `TODO`, `FIXME`, `coming soon`, `akan hadir`, `segera hadir` di `app/`, `resources/views/`, `routes/`.
- URL lama `/klien/rundown`, `/klien/evaluasi`, `/klien/rekomendasi`, `/vendor/layout`, `/venue/denah` → 404.
- Tidak ada `round(`, `floor(`, `ceil(`, `(float)` pada `app/Services` dan `app/Http`, kecuali `RatingSummary` dan `PackageRecommender` (rating/desimal, bukan uang). `OrderReviewController` diubah dari `round(...)` ke `intdiv($lineTotal × persen + 50, 100)` (pembulatan setengah ke atas, bilangan bulat).
- Migrasi: `migrate:fresh`, `migrate:rollback`, `migrate`, `migrate:reset`, dan `db:seed` berhasil. `down()` migrasi legacy `2026_11_20_000002` dibuat idempoten (cek `hasColumn`, lewati drop kolom ber-FK di SQLite karena batasan mesin).

### D3b — Pembersihan
- Komentar menyesatkan diperbaiki (contoh: komentar "entri palsu prototipe" pada `ActivityLogController`).
- Berkas view yatim dipindahkan ke `.trash/views/` (dapat dipulihkan): `klien-rundown`, `klien-evaluasi`, `klien-rekomendasi`. **Koreksi (Tahap F):** folder `.trash/` ada di repositori git tetapi **tidak ikut** ke ZIP Tahap D, sehingga pernyataan ini tidak berlaku untuk paket ZIP. Sejak Tahap F `/.trash` masuk `.gitignore` dan tidak disertakan ke bundle.
- `vendor/bin/pint --test`: **306 berkas bersih**.
- **Kandidat yang tidak dihapus** (menunggu keputusan pemilik proyek): `scripts/convert_prototypes.php`, `scripts/update_admin_views.php`, `scripts/update_project_views.php`, `scripts/verify_admin.php`, folder `_prototypes/`, `_updates/`, `docs/frontend-audit-*`, `docs/reports/`.
- Seeder demo dijalankan ulang: "Akun demo local siap …".

### D3c — Dokumen
- `docs/BACKEND_DATABASE_DESIGN.md`: tabel C, kolom D, media denah, `settings.budget_allocation`.
- `docs/BACKEND_API_CONTRACT.md`: 13 endpoint C + 4 endpoint D beserta body dan kode status.
- `docs/AI_AGENT_CONTEXT.md`, `docs/BACKEND_AGENT_CONTEXT.md`: layanan dan policy baru.
- `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`: bagian 4 (C) dan 5 (D).
- `README.md`: status modul A–D, deskripsi menu per portal, cara menjalankan.
- `docs/INSTRUKSI_TAHAP_C_Rundown_Evaluasi.md`, `docs/INSTRUKSI_TAHAP_D_Penutup.md`.

### D3d — Verifikasi akhir
- `php artisan test --compact`: **510 tes lulus, 3.285 assertion** (152 detik).
- `vendor/bin/pint --test`: bersih.
- `php artisan route:cache`: berhasil (lalu `route:clear`).
- `php artisan migrate:fresh` + `db:seed`: berhasil.

## Keterbatasan yang harus diketahui

1. **Tidak ada verifikasi browser manual.** Lingkungan sesi ini tidak menyediakan browser interaktif; semua verifikasi UI dilakukan lewat tes HTTP yang merender Blade dan memeriksa markup. Uji visual (lebar layar, fokus keyboard nyata, pembaca layar) belum dilakukan.
2. **Ambang "tanpa float"** hanya diperiksa secara statis pada `app/Services` dan `app/Http`; view memakai `round()` untuk bintang rating (bukan uang).
3. **Kategori tak terpetakan diabaikan** sesuai instruksi. Bila klien ingin kategori lain masuk rekomendasi, tambahkan pemetaan pada `PackageRecommender::CATEGORY_MAP` atau normalkan kategori katalog.
4. **Rekomendasi tidak menyimpan apa pun.** Tidak ada tabel rekomendasi; angka dapat berubah bila harga/ketersediaan berubah, dan itu disengaja.
5. **Dana cadangan** menampung persentase cadangan ditambah selisih pembulatan; label UI menyebut hal ini.
6. Empty state tabel generik "Belum ada data." pada beberapa halaman Admin dipertahankan karena faktual (bukan klaim prototipe) dan sudah diuji sejak Tahap B.
