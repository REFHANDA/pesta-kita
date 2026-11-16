# Laporan Tahap 5.4 — Request Quote / Custom Quotation

Tanggal: 2026-11-15

Tahap roadmap: **5 — Katalog, Profil Penyedia, dan Ketersediaan** (`docs/roadmap_pembaruan_frontend_pesta_kita.md`, bagian "TAHAP 5", subtahap "5.4 Request Quote / Custom Quotation"). Instruksi resmi tahap ini adalah dokumen `INSTRUKSI TRAE — PENYELARASAN ROADMAP DAN TAHAP 5.4 REQUEST QUOTE PESTA KITA` (Bagian B).

Catatan penomoran: penomoran Tahap 5 telah diselaraskan ke standar final (5.1 Katalog Vendor, 5.2 Katalog Venue, 5.3 Profil dan Portofolio Penyedia, 5.4 Request Quote, 5.5 Kalender Ketersediaan, 5.6 Kriteria Penerimaan dan Evaluasi). Lihat catatan penyelarasan di `docs/roadmap_pembaruan_frontend_pesta_kita.md`. Isi/hasil pekerjaan 5.1–5.3 tidak diubah oleh penyelarasan ini.

Status: **Selesai bersyarat** (frontend selesai; backend belum tersedia).

---

## 1. Lingkup

### Dikerjakan
Alur permintaan penawaran (managed marketplace) dengan empat sisi:
- **Klien** mengajukan permintaan penawaran dan melihat hasil yang disahkan.
- **Vendor** & **Pemilik Venue** menerima permintaan dan mengusulkan harga.
- **Admin** meninjau usulan penyedia dan menetapkan keputusan.

Delapan halaman frontend statis (UI prototipe) beserta integrasi navigasi:
- Klien: daftar + detail permintaan penawaran.
- Vendor: daftar + detail permintaan penawaran.
- Venue: daftar + detail permintaan penawaran.
- Admin: antrean peninjauan + detail keputusan.

Ketentuan harga & bisnis yang diterapkan:
- **Harga katalog ≠ harga usulan penyedia ≠ harga final** dibedakan secara tegas pada setiap halaman.
- **Komisi 10% ditambahkan ke tagihan Klien** (bukan dipotong dari harga penyedia).
- Backend adalah sumber kebenaran harga final; frontend tidak menetapkan nominal final.

### Sengaja tidak dikerjakan
- Subtahap **5.5 (Kalender Ketersediaan)** dan **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5)**.
- Backend, database/migration, route, controller, model, API, autentikasi, middleware, `.env`.
- Penyimpanan permintaan/usulan/keputusan ke server; endpoint baru.
- Notifikasi keberhasilan palsu; submit ke endpoint yang belum ada.
- Perubahan pada `routes/web.php`.
- Screenshot (sesuai kebijakan: tidak membuat screenshot).

---

## 2. Keputusan penting

### 2.1 Memakai auto-routing portal (tanpa perubahan route)
`routes/web.php` memakai `Route::get('/{page}', ...)->where('page','[a-zA-Z0-9_-]+')` per prefix (`klien`, `vendor`, `venue`, `admin`), sehingga view Blade baru otomatis dapat diakses **tanpa perubahan route** (sesuai batasan "jangan mengubah route"). Nama halaman hanya `[a-zA-Z0-9_-]` (tanpa titik).

### 2.2 Penanda prototipe eksplisit di setiap halaman
Karena backend belum ada, setiap halaman memuat penanda: **"Fitur ini masih berupa prototipe frontend. Pengajuan dan perubahan penawaran belum tersimpan ke server."** Ini memenuhi larangan notifikasi sukses palsu.

### 2.3 Semua form/aksi non-submit
Form memakai `onsubmit="return false"`. Tombol tindakan memakai `data-confirm` sehingga dicegah oleh `aksi.js` dan hanya menampilkan dialog pratinjau + toast "Pratinjau frontend: tindakan ini tidak disimpan ke server." Tidak ada POST ke endpoint mana pun.

### 2.4 Tiga jenis nominal dijaga tidak tertukar
- **Klien detail**: tiga baris terpisah — harga katalog (badge `.b-mute`), harga diajukan penyedia (`.b-warn`), harga disetujui Admin (`.b-ok`) + kartu "Arti status harga".
- **Admin detail**: `.price-compare` + catatan tegas **"halaman ini tidak menetapkan nominal final"**.
- **Vendor/Venue**: "Harga katalog (referensi)" sebagai acuan, bukan harga final.

---

## 3. File yang dibuat dan diubah

### Dibuat (8 view)
| File | Tujuan |
|---|---|
| `resources/views/klien/permintaan-penawaran.blade.php` | Daftar permintaan penawaran Klien (tabs status) + form pengajuan (layanan/venue, acara, tanggal, perkiraan tamu, anggaran, kebutuhan) dengan kolom "Harga katalog (referensi)". Banner penanda prototipe; form non-submit. |
| `resources/views/klien/permintaan-penawaran-detail.blade.php` | Detail: kartu "Perbandingan harga" (katalog/`.b-mute`, usulan/`.b-warn`, disetujui/`.b-ok`), rincian tagihan (harga disetujui + komisi 10% + total contoh), kartu "Arti status harga", riwayat `.timeline`, panel tindakan. |
| `resources/views/vendor/permintaan-penawaran.blade.php` | Daftar permintaan penawaran untuk Vendor + kolom harga katalog (referensi); `.alert` komisi 10% tidak dipotong dari harga penyedia. |
| `resources/views/vendor/permintaan-penawaran-detail.blade.php` | Kartu permintaan Klien, kartu harga katalog penyedia (referensi), kartu **"Usulkan harga"** (`#harga-usulan`, `#rincian-usulan`, `#catatan-usulan`) dengan tombol `data-confirm`+`data-need`. |
| `resources/views/venue/permintaan-penawaran.blade.php` | Struktur identik Vendor untuk sisi Pemilik Venue. |
| `resources/views/venue/permintaan-penawaran-detail.blade.php` | Struktur identik Vendor; field usulan `#harga-sewa`, `#biaya-tambahan`; catatan biaya kebersihan/biaya lain ditampilkan terpisah. |
| `resources/views/admin/permintaan-penawaran.blade.php` | Antrean peninjauan Admin (`.workflow-card` + tabs), tiap item memuat `.price-compare` dan tombol Setujui/Tolak (`data-confirm` + `data-ask` alasan wajib). |
| `resources/views/admin/permintaan-penawaran-detail.blade.php` | Detail keputusan: perbandingan harga + perubahan nominal/persentase, harga final "Menunggu keputusan Admin" (`.warn-t`), rincian penawaran per komponen, panel "Tindakan Admin" (`#decision-quote`, `#reason-quote`). |

### Diubah (6 file)
| File | Tujuan |
|---|---|
| `resources/views/klien/katalog-vendor-detail.blade.php` | Menambah aksi "Minta penawaran" + tautan "Ajukan permintaan penawaran" → `/klien/permintaan-penawaran`. |
| `resources/views/klien/katalog-venue-detail.blade.php` | Menambah aksi "Minta penawaran" + tautan "Ajukan permintaan penawaran" → `/klien/permintaan-penawaran`. |
| `resources/views/layouts/klien.blade.php` | Menu "Permintaan penawaran" + klausa `aria-current` pada `request()->is('klien/permintaan-penawaran*')`. |
| `resources/views/layouts/vendor.blade.php` | Menu "Permintaan Penawaran" (badge) + klausa `aria-current` `vendor/permintaan-penawaran*`. |
| `resources/views/layouts/venue.blade.php` | Menu "Permintaan Penawaran" (badge) + klausa `aria-current` `venue/permintaan-penawaran*`. |
| `resources/views/layouts/admin.blade.php` | Menu "Peninjauan penawaran" (badge) + klausa `aria-current` `admin/permintaan-penawaran*`. |

Tidak ada perubahan pada `routes/web.php`, controller, model, migration, `.env`, atau aset inti. Kelas CSS yang dipakai adalah kelas yang sudah ada di `katalog.css`/`klien.css`/`penyedia.css`/`admin.css`.

---

## 4. Alur A — Klien mengajukan permintaan penawaran

- **`/klien/permintaan-penawaran`**: daftar 3 permintaan contoh (Graha Melati Ballroom, Katering prasmanan, Dekorasi balon) dengan kolom "Harga katalog (referensi)" dan badge status; tabs (Semua / Menunggu penawaran / Ditinjau Admin / Penawaran disetujui).
- Form pengajuan: pilih layanan/venue, pilih acara, tanggal (readonly contoh), perkiraan tamu, anggaran, kebutuhan; tombol "Kirim permintaan" (`data-confirm`). Aside "Ringkasan permintaan" + `.warn-box` menegaskan harga katalog hanya referensi.
- **`/klien/permintaan-penawaran-detail`**: kartu "Perbandingan harga" memisahkan katalog/usulan/final; rincian tagihan menunjukkan harga disetujui + komisi 10% + total contoh; kartu "Arti status harga" menjelaskan setiap status.

## 5. Alur B — Vendor/Venue menerima & mengusulkan harga

- **`/vendor/permintaan-penawaran`** & **`/venue/permintaan-penawaran`**: daftar permintaan + kolom harga katalog (referensi); `.alert` menegaskan komisi 10% tidak dipotong dari harga penyedia.
- **`/vendor/permintaan-penawaran-detail`** & **`/venue/permintaan-penawaran-detail`**: kartu permintaan Klien, kartu harga katalog penyedia (referensi), kartu "Usulkan harga" dengan tombol "Kirim usulan" (validasi wajib isi via `data-need`/`data-need-msg`) dan "Simpan draf". Tidak ada nominal final yang ditetapkan.

## 6. Alur C — Admin meninjau

- **`/admin/permintaan-penawaran`**: `.workflow-card` + tabs (Menunggu keputusan + badge n=2 / Disetujui / Ditolak / Semua); tiap item memuat `.price-compare` (Harga katalog → Harga diajukan penyedia) dan tombol Setujui/Tolak (alasan wajib saat menolak via `data-ask`).
- **`/admin/permintaan-penawaran-detail`**: `.price-compare` + perubahan nominal/persentase, harga final "Menunggu keputusan Admin" (`.warn-t`), tabel rincian penawaran per komponen, dan panel "Tindakan Admin" (`#decision-quote`, `#reason-quote` dengan `data-need`). Catatan tegas: **halaman ini tidak menetapkan nominal final**.

## 7. Alur D — Klien melihat hasil

- Detail Klien menampilkan harga yang telah disetujui Admin (`.b-ok`) sebagai dasar tagihan Klien beserta komisi 10%; tombol "Lanjutkan pemesanan" dan "Tarik permintaan" (keduanya `data-confirm`, tanpa penyimpanan server).

---

## 8. Hasil pengujian aktual

| Pemeriksaan | Hasil aktual |
|---|---|
| `php artisan test --compact` | **Lulus** — 4 tests, 234 assertions (naik dari 218 karena 8 view baru). `ProjectPortalPagesTest` mencakup kedelapan halaman `*/permintaan-penawaran*` (`assertOk` + larangan tautan `.html`). |
| `php artisan view:cache` | **Lulus** — Blade templates cached successfully. |
| `node --check` (`aksi.js`, `app-ui.js`) | **Lulus** (tanpa error). |
| `git diff --check` | **Lulus** (exit code 0, tanpa whitespace error). |
| Render-check kernel (`php artisan serve` + `Invoke-WebRequest`) | **Lulus** — 8 halaman 200 dengan penanda konten sesuai: `/klien/permintaan-penawaran`, `/klien/permintaan-penawaran-detail`, `/vendor/permintaan-penawaran`, `/vendor/permintaan-penawaran-detail`, `/venue/permintaan-penawaran`, `/venue/permintaan-penawaran-detail`, `/admin/permintaan-penawaran`, `/admin/permintaan-penawaran-detail`. |

Tidak ada screenshot.

---

## 9. Pemeriksaan regresi

- **Halaman katalog vendor/venue (5.1/5.2):** semua 200; hanya ditambah aksi/tautan "Minta penawaran"; struktur lain tidak diubah.
- **Profil penyedia (5.3):** tidak diubah.
- **Navigasi sidebar 4 role:** `ProjectPortalAccessibilityTest` lulus; menu baru memakai klausa `aria-current` yang konsisten.
- **Tautan `.html`:** tidak ada (dilarang oleh `ProjectPortalPagesTest`).
- **Aset inti:** `design-tokens.css`, `admin.css`, `klien.css`, `penyedia.css`, `katalog.css`, `app-ui.js` tidak diubah.

---

## 10. Kebutuhan backend yang belum tersedia

1. **Endpoint pengajuan permintaan penawaran** (Klien) — saat ini hanya form pratinjau.
2. **Endpoint daftar/detail permintaan** per role — saat ini statis di Blade.
3. **Endpoint usulan harga** (Vendor/Venue) + penyimpanan draf — belum ada.
4. **Endpoint keputusan Admin** (setujui/tolak + alasan) + log aktivitas — belum ada.
5. **Perhitungan harga final & komisi 10%** — harus dihitung backend sebagai sumber kebenaran.
6. **Penomoran permintaan penawaran** — belum ada skema.
7. **Notifikasi** ke Klien/Penyedia atas hasil keputusan — belum ada.
8. **Perlindungan route** (auth/role middleware) — route portal masih belum dilindungi.

---

## 11. Risiko / pekerjaan yang belum selesai

- Seluruh UI 5.4 masih statis/prototipe; tidak ada penyimpanan ke server.
- Harga final, komisi, dan penomoran sepenuhnya menunggu backend.
- Notifikasi transisi status belum ada.
- Subtahap **5.5 (Kalender Ketersediaan)** dan **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5)** belum dikerjakan.

---

## 12. Titik awal berikutnya

Tahap **5.5 (Kalender Ketersediaan)** menurut roadmap, **hanya setelah persetujuan pengguna**. **Berhenti di sini** — jangan melanjutkan tanpa instruksi.
