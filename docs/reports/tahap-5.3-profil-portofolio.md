# Laporan Tahap 5.3 — Profil dan Portofolio Penyedia

Tanggal: 2026-11-15

Tahap roadmap: **5 — Katalog, Profil Penyedia, dan Ketersediaan** (`docs/roadmap_pembaruan_frontend_pesta_kita.md`, bagian "TAHAP 5", baris 604–725). Instruksi resmi tahap ini adalah dokumen `INSTRUKSI TRAE — TAHAP 5.3 PROFIL DAN PORTOFOLIO PENYEDIA`.

Catatan penomoran: pada saat pekerjaan ini, roadmap masih memakai penomoran lama ("5.3 Katalog venue" / "5.4 Portofolio dan media"). Per 15 November 2026 penomoran Tahap 5 telah diselaraskan (lihat catatan di `docs/roadmap_pembaruan_frontend_pesta_kita.md`), sehingga subtahap ini bernomor final **5.3 Profil dan Portofolio Penyedia**. Pekerjaan mengikuti instruksi resmi (profil + portofolio + UI kelola foto); isi dan hasil pekerjaan tidak diubah oleh penyelarasan ini.

Status: **Selesai bersyarat** (frontend statis; backend belum tersedia).

---

## 1. Lingkup

### Dikerjakan
- **5.3.1** Halaman profil penyedia yang terlihat Klien:
  - Vendor → `/klien/penyedia-vendor` (`resources/views/klien/penyedia-vendor.blade.php`).
  - Pemilik Venue → `/klien/penyedia-venue` (`resources/views/klien/penyedia-venue.blade.php`).
  - Keduanya memuat info relevan sesuai jenis penyedia, dengan penanda data prototipe, tanpa kontak pribadi, tanpa klaim verifikasi palsu.
- **5.3.2** Integrasi navigasi dua arah katalog ↔ profil (tanpa memutus tautan detail yang ada, tanpa menyentuh katalog peralatan).
- **5.3.3** Galeri portofolio responsif dengan rasio gambar konsisten + empty state jujur (tidak ada foto palsu).
- **5.3.4** Form UI pengelolaan foto pada `vendor/profil` dan `venue/profil` (area pilih file, daftar/pratinjau media, catatan bahwa media **belum tersimpan ke server**, empty state).
- **5.3.5** Batas format/ukuran unggah ditampilkan jelas dan ditandai sebagai konfigurasi contoh.
- **5.3.6** Memperbaiki konten lama yang menyesatkan pada `resources/views/klien/katalog-detail.blade.php` secara terarah (bukan penggantian global).
- Pembaruan dokumentasi konteks dan laporan ini.

### Sengaja tidak dikerjakan
- Subtahap **5.4 (Request Quote), 5.5 (Kalender), dan seterusnya**.
- Backend, database/migration, route, controller, model, API, autentikasi, `.env`.
- Penyimpanan media ke server, endpoint unggah, atau notifikasi keberhasilan palsu.
- Foto portofolio asli (tidak ada aset di repo → memakai placeholder jujur, bukan foto karangan).
- Perubahan massal/refactor besar pada halaman profil penyedia.
- Perubahan luas pada katalog peralatan/rekomendasi.
- Screenshot (sesuai kebijakan: tidak membuat screenshot).

---

## 2. Keputusan penting

### 2.1 Dua halaman profil terpisah (Vendor vs Venue)
Struktur informasi Vendor dan Pemilik Venue berbeda signifikan:
- **Vendor**: kategori layanan, area layanan, batas layanan, paket, kebijakan.
- **Venue**: jenis venue, lokasi, kapasitas, fasilitas, aturan penggunaan ruangan, kebijakan, ketersediaan.

Instruksi mengizinkan penggunaan halaman terpisah bila isi berbeda. Dibuat **dua halaman** (`penyedia-vendor`, `penyedia-venue`) untuk menghindari pemaksaan struktur yang berbeda ke satu template.

### 2.2 Menghormati pola auto-routing
`routes/web.php` memakai `Route::get('/{page}', ...)->where('page','[a-zA-Z0-9_-]+')` untuk tiap portal, sehingga view `klien.penyedia-vendor` / `klien.penyedia-venue` otomatis dapat diakses **tanpa perubahan route** (sesuai batasan "jangan mengubah route").

### 2.3 Memakai ulang konvensi unggah yang sudah ada di repo
Batas "JPG atau PNG · maksimal 5 MB per foto" sudah dipakai di repo (`venue/form.blade.php`, `vendor/verifikasi.blade.php`). Konvensi ini **dipakai ulang** (bukan dikarang), dan tetap ditandai sebagai **konfigurasi contoh** yang perlu disepakati backend.

### 2.4 5.3.6 dikerjakan secara bedah (surgical), bukan global
`klien/katalog-detail.blade.php` dipakai bersama oleh katalog peralatan **dan** seluruh paket rekomendasi. Sesuai instruksi 5.3.6, hanya **dua penggantian teks setempat** dilakukan (rating & ketersediaan), tanpa menyentuh struktur/blok lain, agar alur peralatan/rekomendasi tidak rusak.

---

## 3. File yang dibuat dan diubah

### Dibuat
| File | Tujuan |
|---|---|
| `resources/views/klien/penyedia-vendor.blade.php` | Halaman profil penyedia Vendor untuk Klien (5.3.1). Info Vendor, paket, kebijakan, galeri placeholder + empty state, tanpa kontak pribadi, penanda prototipe. Memuat `katalog.css` via `@push('styles')`. |
| `resources/views/klien/penyedia-venue.blade.php` | Halaman profil Pemilik Venue untuk Klien (5.3.1). Info venue, fasilitas, aturan ruangan, kebijakan, ketersediaan "Perlu konfirmasi", galeri placeholder + empty state. Memuat `katalog.css`. |
| `public/assets/css/profil-media.css` | Gaya grid pratinjau media (`.media-list`, `.media-item`) untuk form kelola foto (5.3.4/5.3.3). Hanya selector baru; rasio `aspect-ratio:4/3` + `object-fit:cover`. |
| `public/assets/js/profil-media.js` | Pratinjau gambar **lokal** + validasi format/ukuran tanpa server (5.3.4/5.3.5). Memakai `URL.createObjectURL`, pembersihan via `URL.revokeObjectURL` (saat ganti & `beforeunload`). |

### Diubah
| File | Tujuan |
|---|---|
| `resources/views/klien/katalog-vendor-detail.blade.php` | Menambahkan aksi "Lihat profil penyedia" → `/klien/penyedia-vendor` dan "Kembali ke katalog" → `/klien/katalog-vendor` (5.3.2). |
| `resources/views/klien/katalog-venue-detail.blade.php` | Menambahkan aksi "Lihat profil penyedia" → `/klien/penyedia-venue` dan "Kembali ke katalog" → `/klien/katalog` (5.3.2). |
| `resources/views/layouts/klien.blade.php` | Menambah klausa `request()->is('klien/penyedia-*')` pada kondisi `aria-current` menu "Vendor & venue" agar halaman profil ikut tersorot (5.3.2). 1 klausa; struktur/aksesibilitas tidak diubah. |
| `resources/views/klien/katalog-detail.blade.php` | 5.3.6: mengganti rating contoh "★ 4,8 dari 36 penilaian" → "Rating belum tersedia pada prototipe"; mengganti kotak ketersediaan "Tersedia pada 12 Desember 2026 dan muat untuk 30 tamu." → kotak peringatan yang menyatakan tanggal hanya contoh dan ketersediaan belum dikonfirmasi. Dua penggantian setempat saja. |
| `resources/views/vendor/profil.blade.php` | 5.3.4: menambah form UI pengelolaan foto (area pilih file, hint batas format/ukuran, `.media-list` pratinjau, empty state, `field-error role="alert"`). Form profil & kata sandi lama dipertahankan. |
| `resources/views/venue/profil.blade.php` | 5.3.4: pola sama untuk Venue (id `foto-venue*`). Form lama dipertahankan. |
| `docs/AI_AGENT_CONTEXT.md` | Status roadmap Tahap 5, bagian hasil 5.3 (6.8), riwayat pembaruan. |
| `docs/reports/tahap-5.3-profil-portofolio.md` | Laporan ini. |

Seluruh perubahan lokal Tahap 1–5.2 dipertahankan.

---

## 4. Halaman profil penyedia (5.3.1)

### `/klien/penyedia-vendor`
- Remah navigasi ("Katalog vendor / Profil penyedia") + tombol **Kembali ke katalog** ke `/klien/katalog-vendor`.
- Banner data prototipe (`.data-note`) menegaskan seluruh data adalah contoh statis dan **kontak pribadi penyedia disembunyikan**.
- Info Vendor: Kategori layanan, Area layanan, Batas layanan (kapasitas/jumlah pesanan).
- Paket (`.pkg-list`) dan kebijakan (`.policy`), ditandai contoh.
- Status verifikasi ditampilkan sebagai teks **"Terverifikasi (contoh)"** — tidak mengklaim verifikasi sistem sebenarnya.
- Bagian "Kontak penyedia" menampilkan pesan bahwa kontak hanya dibuka lewat sistem (aturan managed marketplace).
- Tautan "Layanan dari penyedia ini" ke `/klien/katalog-vendor-detail`.
- Galeri portofolio placeholder + empty state.

### `/klien/penyedia-venue`
- Remah navigasi + tombol **Kembali ke katalog** ke `/klien/katalog`.
- Banner data prototipe.
- Info venue: Jenis venue, Lokasi, Kapasitas; Fasilitas (`.policy`); Aturan penggunaan ruangan (`.policy`).
- Kebijakan venue: "Informasi kebijakan venue dan pembatalan belum tersedia pada prototipe ini."
- Status verifikasi: teks contoh; Ketersediaan: badge `b-warn` **"Perlu konfirmasi"** + `.warn-box` (tanpa kalender/Instant Book).
- Tautan "Venue dari penyedia ini" ke `/klien/katalog-venue-detail`.
- Galeri foto venue placeholder + empty state.

Keduanya **tidak** menampilkan nomor telepon, email pribadi, atau alamat kontak pribadi penyedia.

---

## 5. Galeri portofolio (5.3.3)

- Grid galeri memakai rasio konsisten (placeholder `aspect-ratio` via `.gallery-ph`; pratinjau media `aspect-ratio:4/3`). Gambar nyata (bila ada di sistem) memakai `object-fit` agar tidak terpotong sembarangan.
- Setiap placeholder galeri memakai `role="img"` + `aria-label` deskriptif ("Contoh foto portofolio belum tersedia") — bukan nama file.
- Empty state jujur: "Belum ada foto portofolio yang diunggah. Foto akan tampil di sini setelah penyedia mengunggahnya melalui sistem."
- Responsif: grid `repeat(auto-fill, minmax(120px,1fr))` untuk pratinjau media; di ≤480px turun menjadi 2 kolom. Tidak ada overflow horizontal pada breakpoint kecil.
- **Tidak ada foto asli** dibuat/dikarang karena tidak tersedia di repo.

---

## 6. Form UI pengelolaan foto (5.3.4) dan batas unggah (5.3.5)

- Form non-submit (`onsubmit="return false"`) — **tidak ada POST** ke endpoint mana pun.
- Area pilih file (`.upload-box`) dengan `input type="file"` `accept="image/png,image/jpeg"` `multiple`, `aria-describedby` ke hint.
- Hint batas: "Format JPG atau PNG, maksimal 5 MB per foto." + penjelasan bahwa pratinjau hanya tampil di browser dan **belum disimpan ke server** pada prototipe, serta batas tersebut adalah **konfigurasi contoh** yang perlu disepakati backend.
- Pesan galat validasi (`field-error`, `role="alert"`): format tidak didukung, atau ukuran melebihi 5 MB (disebut nama file & ukurannya).
- Daftar pratinjau (`.media-list`) menampilkan gambar lokal + nama file; empty state (`.photo-placeholder`) tampil saat belum ada berkas diterima.
- JS (`profil-media.js`): pratinjau lokal memakai `URL.createObjectURL`; object URL dibersihkan saat perubahan pilihan dan pada `beforeunload` (`URL.revokeObjectURL`). Tidak ada klaim tersimpan; tidak ada keberhasilan palsu.

---

## 7. Perbaikan konten menyesatkan `katalog-detail` (5.3.6)

Dua penggantian setempat pada `resources/views/klien/katalog-detail.blade.php`:
1. Rating contoh → **"Rating belum tersedia pada prototipe"** (tidak lagi menampilkan angka rating/ulasan contoh).
2. Kotak ketersediaan contoh → kotak peringatan (`warn-box`) yang menyatakan **tanggal 12 Desember 2026 hanya contoh prototipe** dan ketersediaan **belum dikonfirmasi** serta perlu diperiksa sistem sebelum pesanan diproses.

Tidak ada penggantian global; struktur lain halaman generik (peralatan & rekomendasi) tidak diubah.

---

## 8. Hasil pengujian aktual

| Pemeriksaan | Hasil aktual |
|---|---|
| `php artisan test --compact` | **Lulus** — 4 tests, 218 assertions (naik dari 214 karena 2 view baru). `ProjectPortalPagesTest` mencakup `klien/penyedia-vendor` & `klien/penyedia-venue` (`assertOk` + larangan tautan `.html`). |
| `php artisan view:cache` | **Lulus** — Blade templates cached successfully. |
| `node --check` (`profil-media.js`, `katalog-filter.js`, `aksi.js`, `app-ui.js`) | **Lulus** (tanpa error). |
| `git diff --check` | **Lulus** (tanpa whitespace error). |
| Render-check kernel (`php artisan serve` + `Invoke-WebRequest`) | **Lulus** — 11 halaman 200 dengan penanda konten sesuai: `/klien/penyedia-vendor`, `/klien/penyedia-venue`, `/klien/katalog`, `/klien/katalog-vendor`, `/klien/katalog-vendor-detail`, `/klien/katalog-venue-detail`, `/klien/katalog-peralatan`, `/klien/katalog-detail`, `/klien/rekomendasi`, `/vendor/profil`, `/venue/profil`. |

Tidak ada screenshot.

---

## 9. Pemeriksaan regresi

- **Halaman katalog:** semua 200; tautan detail vendor/venue tetap berfungsi; tidak ada tautan `.html`.
- **Peralatan:** `klien/katalog-peralatan` tidak diubah; kartunya tetap mengarah ke halaman generik `katalog-detail`.
- **Detail generik & rekomendasi:** tetap dapat diakses 200; hanya dua teks pada `katalog-detail` yang diperbaiki; struktur lain tidak diubah.
- **Katalog vendor/venue (5.1/5.2):** tidak diubah selain penambahan aksi navigasi ke profil.
- **Navigasi sidebar:** menu "Vendor & venue" kini tersorot juga pada `/klien/penyedia-*`. `ProjectPortalAccessibilityTest` lulus.
- **Form lama Vendor/Venue:** formulir profil & kata sandi tetap ada dan tidak diubah strukturnya; hanya ditambah blok kelola foto.
- **Aset inti:** `design-tokens.css`, `admin.css`, `klien.css`, `keselarasan.css`, `penyedia.css`, `katalog.css` inti, dan `app-ui.js` tidak diubah (hanya menambah file baru `profil-media.css`/`.js`).

---

## 10. Kebutuhan backend yang belum tersedia

1. **Endpoint data penyedia** (nama usaha, kategori, area, kapasitas/batas layanan, paket, kebijakan) — saat ini statis di Blade.
2. **Endpoint data venue** (jenis, lokasi, kapasitas, fasilitas, aturan, kebijakan) — statis.
3. **Sumber status verifikasi** (dari Admin/sistem) — saat ini hanya label contoh.
4. **Endpoint unggah media + penyimpanan** (format, ukuran, kompresi, penyimpanan) — belum ada; batas 5 MB/JPG-PNG adalah contoh.
5. **Endpoint daftar foto portofolio** penyedia — belum ada; galeri hanya placeholder.
6. **Kebijakan venue/pembatalan** — belum ada sumber data.
7. **Ketersediaan tanggal** — belum ada sumber data; kalender belum dibuat (di luar 5.3).

---

## 11. Risiko / pekerjaan yang belum selesai

- `resources/views/venue/profil.blade.php` (sebelum perubahan 5.3) memuat data bergaya Vendor ("Dapur Nusantara", "Katering") — potensi ketidakkonsistenan konten lama. **Tidak diubah** pada tahap ini karena di luar lingkup 5.3 (hanya form kelola foto yang ditambahkan). Perlu ditinjau pada tahap yang relevan.
- Galeri portofolio masih placeholder karena belum ada aset foto maupun endpoint media.
- Seluruh data masih statis/prototipe; belum terhubung backend.
- Route portal belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap **5.4–5.6 belum dikerjakan**.

---

## 12. Titik awal berikutnya

Tahap **5.4 (Request Quote)** menurut instruksi/roadmap, atau penanganan terpisah untuk konten lama `venue/profil` dan pemisahan biaya pada alur rekomendasi. **Berhenti di sini** menunggu instruksi pengguna — jangan melanjutkan tanpa persetujuan.
