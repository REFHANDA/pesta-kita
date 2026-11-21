# Laporan Tahap 5.2 — Katalog Venue

Tanggal: 2026-11-15

Tahap roadmap: **5 — Katalog, Profil Penyedia, dan Ketersediaan** (`docs/roadmap_pembaruan_frontend_pesta_kita.md`, bagian "TAHAP 5", baris 604–725), subtahap **5.2 Katalog venue** (baris 647–662) beserta bagian terkait 5.3–5.5 (media, kebijakan, ketersediaan).

Status: **Selesai bersyarat** (frontend statis; backend belum tersedia).

---

## 1. Lingkup

### Dikerjakan
- Halaman daftar venue Klien (`/klien/katalog`) disusun ulang agar menampilkan: nama venue, penyedia/jenis, lokasi, kapasitas, fasilitas utama, deskripsi singkat, **rincian biaya terpisah** (harga sewa, biaya kebersihan, biaya layanan 10%, estimasi total), **status verifikasi**, dan petunjuk kebijakan.
- Halaman **detail venue terpisah** baru (`/klien/katalog-venue-detail`) dengan rincian biaya transparan, fasilitas, **aturan penggunaan ruangan**, **kebijakan pembatalan**, **informasi ketersediaan**, galeri placeholder, form "tambahkan ke acara" non-submit, dan kartu status penyedia.
- Filter jenis venue, pencarian nama, dan sewa maksimum **berjalan lokal** (progressive enhancement, memakai ulang `katalog-filter.js`).
- Penanda data prototipe eksplisit pada kedua halaman.
- Penyelarasan navigasi sidebar Klien untuk halaman detail venue baru.
- Pembaruan dokumentasi konteks.

### Sengaja tidak dikerjakan
- Tahap 5.3–5.6 (profil & portofolio lengkap, request quote, kalender ketersediaan, dst.).
- Perubahan pada `resources/views/klien/katalog-detail.blade.php` (halaman generik) sesuai keputusan pengguna.
- Backend, database/migration, route, controller, model, API, autentikasi, `.env`.
- Fitur/data/aksi server palsu; aturan bisnis tidak diubah.
- Screenshot tidak dibuat.

---

## 2. Keputusan penting: halaman detail venue terpisah

`resources/views/klien/katalog-detail.blade.php` **dipakai bersama** oleh:
- daftar venue (`klien/katalog`),
- daftar peralatan (`klien/katalog-peralatan`),
- seluruh paket rekomendasi (`klien/rekomendasi` — venue, katering, hiburan, peralatan).

Halaman itu juga memuat konten contoh lama (rating "★ 4,8 dari 36 penilaian" dan kotak ketersediaan "Tersedia pada 12 Desember 2026"). Mengubahnya secara langsung berisiko mengubah detail untuk peralatan & rekomendasi, yang tidak diverifikasi oleh test berbasis konten.

Sesuai instruksi (bagian "Halaman detail venue" yang memberi opsi solusi paling minimal dan wajib dilaporkan), dan setelah konfirmasi pengguna, dipilih: **membuat halaman detail venue terpisah** dan mengarahkan kartu venue ke sana, sehingga halaman generik tidak diubah. Ini konsisten dengan pola Tahap 5.1 (`katalog-vendor` → `katalog-vendor-detail`).

---

## 3. File yang dibuat dan diubah

### Dibuat
| File | Tujuan |
|---|---|
| `resources/views/klien/katalog-venue-detail.blade.php` | Halaman detail venue (5.2). Memuat rincian biaya, fasilitas, aturan ruangan, kebijakan pembatalan, ketersediaan, galeri placeholder, form non-submit, status penyedia. Memuat `katalog.css` via `@push('styles')`. |

### Diubah
| File | Tujuan |
|---|---|
| `resources/views/klien/katalog.blade.php` | Disusun ulang menjadi daftar venue: banner data prototipe, pill jenis venue, toolbar pencarian, kartu venue dengan badge verifikasi + `.cost-list` (rincian biaya terpisah), empty state, dan pemuatan `katalog-filter.js`. Kartu venue diarahkan ke `katalog-venue-detail`. |
| `public/assets/css/katalog.css` | Menambah `.cost-list` (rincian biaya pada kartu) dan `.cost-list .tot`; memperbarui komentar header agar mencakup Venue. Selector Tahap 5.1 tidak diubah. |
| `resources/views/layouts/klien.blade.php` | Menambah klausa `request()->is('klien/katalog-venue*')` pada kondisi `aria-current` menu "Vendor & venue". Struktur, aksesibilitas, dan perilaku drawer tidak diubah. |
| `docs/AI_AGENT_CONTEXT.md` | Status roadmap Tahap 5, bagian hasil 5.2 (6.7), riwayat pembaruan. |
| `docs/reports/tahap-5.2-katalog-venue.md` | Laporan ini. |

`resources/views/klien/katalog-detail.blade.php` **tidak diubah**. Seluruh perubahan lokal Tahap 1–5.1 dipertahankan.

---

## 4. Perubahan tampilan dan fungsi

### Daftar venue (`/klien/katalog`)
- **Banner data prototipe** (`.data-note`) menegaskan seluruh data (nama, harga, biaya, fasilitas, aturan, kebijakan, verifikasi) adalah contoh statis.
- **Rincian biaya terpisah** pada setiap kartu (`.cost-list`): baris "+ Biaya kebersihan", "+ Biaya layanan Pesta Kita (10%)", dan "Estimasi total" — dipisah dari harga sewa agar tidak mengejutkan pada tahap tagihan (roadmap 5.2.2).
- **Badge status verifikasi** tekstual (`Terverifikasi` / `Menunggu verifikasi`) pada foto kartu.
- **Pill jenis venue**: Semua / Ballroom / Aula / Taman & area terbuka; filter lokal (kategori, pencarian nama, sewa maksimum) dan empty state dengan tombol reset.
- Venue tanpa data lengkap menampilkan "Belum ditentukan" dan label estimasi sementara (tidak dikarang).

### Detail venue (`/klien/katalog-venue-detail`)
- Header: penyedia + jenis + badge verifikasi contoh; tanpa kontak pribadi penyedia; tautan kembali ke katalog.
- Informasi: jenis, penyedia, lokasi, kapasitas, deskripsi.
- **Rincian biaya** (tabel): harga sewa, biaya kebersihan, biaya fasilitas tambahan ("Belum ditentukan"), biaya layanan 10%, estimasi total — plus catatan bahwa ini estimasi, bukan tagihan final, dan komisi 10% ditambahkan ke tagihan Klien.
- **Fasilitas** dan **Aturan penggunaan ruangan** (jam, kapasitas, fasilitas, kebersihan, dekorasi) ditandai sebagai contoh statis.
- **Kebijakan pembatalan**: "Informasi kebijakan pembatalan belum tersedia pada prototipe ini." + catatan bahwa kebijakan/refund mengikuti ketentuan venue & sistem.
- **Ketersediaan**: badge "Perlu konfirmasi" + kotak peringatan bahwa ketersediaan perlu dikonfirmasi sebelum pesanan diproses; tanggal contoh ditandai simulasi. Tidak ada kalender interaktif dan tidak ada Instant Book.
- Galeri portofolio placeholder (`role="img"` + `aria-label`, pesan "Belum ada foto venue yang diunggah").
- Form "Tambahkan ke acara" non-submit (`onsubmit="return false"`) dengan peringatan ketersediaan belum dikonfirmasi.

---

## 5. Pemisahan harga sewa dan biaya tambahan

- Harga sewa, biaya kebersihan, biaya fasilitas tambahan (jika ada), dan biaya layanan Pesta Kita 10% **ditampilkan sebagai baris terpisah** dengan label jelas — tidak digabung tanpa penjelasan.
- "Estimasi total" diberi label tegas (`.tot`) dan disertai catatan bahwa itu estimasi dari harga contoh, **bukan** tagihan final; total ditetapkan sistem.
- Biaya yang belum diketahui ditampilkan sebagai "Belum ditentukan" (contoh: kebersihan Pendopo Agung), bukan dikarang.
- Komisi 10% ditampilkan sebagai **tambahan** ke tagihan Klien, konsisten dengan aturan bisnis (bukan dipotong dari harga venue). Angka contoh konsisten: sewa + kebersihan + 10%×sewa = estimasi total.
- Tidak ada perhitungan frontend yang diklaim sebagai total tagihan final.

---

## 6. Status verifikasi dan ketersediaan

- **Verifikasi**: ditampilkan dengan **teks** (`Terverifikasi` / `Menunggu verifikasi`), bukan hanya warna. Pada halaman detail, badge diberi keterangan "Terverifikasi (contoh)". Tidak ada klaim bahwa venue benar-benar diverifikasi sistem.
- **Ketersediaan**: ditandai `Perlu konfirmasi` (badge `b-warn`) disertai peringatan bahwa ketersediaan venue perlu dikonfirmasi sebelum pesanan diproses. Kalender ketersediaan dan Instant Book **tidak** dibuat (ruang lingkup 5.6/berikutnya). Tanggal contoh ditandai sebagai simulasi.

---

## 7. Hasil pengujian aktual

| Pemeriksaan | Hasil aktual |
|---|---|
| `php artisan test --compact` | **Lulus** — 4 tests, 214 assertions (naik dari 212 karena satu view baru). Termasuk `ProjectPortalPagesTest` (`assertOk` + larangan tautan `.html`) yang mencakup `klien/katalog` dan `klien/katalog-venue-detail`. |
| `php artisan view:cache` | **Lulus** — Blade templates cached successfully. |
| `node --check public/assets/js/katalog-filter.js` | **Lulus** (tanpa error). |
| `git diff --check` | **Lulus** (tanpa whitespace error). |
| Render-check kernel | `/klien/katalog` 200 (css+4 kartu+empty state); `/klien/katalog-venue-detail` 200 (rincian biaya, aturan ruangan, kebijakan, badge ketersediaan, sidebar aktif `aria-current`). |

Tidak ada screenshot.

---

## 8. Pemeriksaan regresi

- **Halaman:** semua halaman katalog Klien dapat diakses (200): `/klien/katalog`, `/klien/katalog-venue-detail`, `/klien/katalog-peralatan`, `/klien/katalog-detail`, `/klien/katalog-vendor`, `/klien/katalog-vendor-detail`. Tidak ada tautan `.html`.
- **Peralatan:** `klien/katalog-peralatan` **tidak diubah** dan masih berfungsi; kartunya tetap mengarah ke halaman generik `katalog-detail`.
- **Detail generik & rekomendasi:** `klien/katalog-detail` **tidak diubah**, sehingga alur peralatan dan seluruh paket rekomendasi tetap sama seperti sebelumnya.
- **Katalog vendor (5.1):** tidak diubah; `katalog-vendor` dan `katalog-vendor-detail` tetap 200 dengan `katalog.css` termuat.
- **Navigasi sidebar:** menu "Vendor & venue" kini tersorot juga pada `/klien/katalog-venue-detail` (klausa `klien/katalog-venue*` ditambahkan). `ProjectPortalAccessibilityTest` lulus (markup drawer tidak berubah).
- **Aset inti:** `design-tokens.css`, `admin.css`, `klien.css`, `keselarasan.css`, `penyedia.css`, dan `app-ui.js` tidak diubah.

---

## 9. Kebutuhan backend yang belum tersedia

1. **Endpoint data venue** (nama, penyedia, jenis, lokasi, kapasitas, fasilitas, media) — saat ini statis di Blade.
2. **Biaya tambahan resmi** (kebersihan, fasilitas, biaya lain) dan aturan perhitungannya — nominal harus berasal dari backend, bukan halaman.
3. **Status verifikasi venue** dari sistem/Admin.
4. **Kebijakan pembatalan dan nominal refund** — ditetapkan backend; halaman prototipe hanya menyatakan "belum tersedia".
5. **Ketersediaan tanggal** — belum ada sumber data; kalender belum dibuat.
6. **Perhitungan total & komisi final** — backend sebagai sumber kebenaran.

---

## 10. Risiko / pekerjaan yang belum selesai

- `resources/views/klien/katalog-detail.blade.php` masih memuat konten contoh lama yang menyesatkan (rating "★ 4,8 dari 36 penilaian" dan kotak ketersediaan "Tersedia pada 12 Desember 2026") pada alur peralatan & rekomendasi. Karena halaman dipakai bersama, perbaikannya sebaiknya direncanakan hati-hati (mis. pada 5.3) agar tidak merusak alur lain.
- Seluruh data katalog masih statis/prototipe; belum terhubung backend.
- Route portal belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap 5.3–5.6 belum dikerjakan.

---

## 11. Titik awal berikutnya

Tahap **5.3 (Profil & Portofolio penyedia, termasuk media)** menurut roadmap, sembari mempertimbangkan perbaikan hati-hati konten contoh pada `katalog-detail` generik dan pemisahan biaya pada alur rekomendasi/peralatan. **Berhenti di sini** menunggu instruksi pengguna — jangan melanjutkan tanpa persetujuan.
