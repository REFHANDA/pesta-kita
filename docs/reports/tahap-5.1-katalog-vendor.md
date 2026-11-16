# Laporan Tahap 5.1 — Katalog Vendor

Tanggal: 2026-11-15

Tahap roadmap: **5 — Katalog, Profil Penyedia, dan Ketersediaan** (`docs/roadmap_pembaruan_frontend_pesta_kita.md`, bagian "TAHAP 5", baris 604–725), subtahap **5.1 Katalog vendor** (baris 630–645).

Status: **Selesai bersyarat** (frontend statis; backend belum tersedia).

---

## 1. Lingkup

### Dikerjakan
- Halaman daftar katalog vendor Klien (`/klien/katalog-vendor`) disusun ulang agar menampilkan informasi roadmap 5.1: nama usaha, kategori, deskripsi singkat, lokasi layanan, kapasitas/batas layanan, harga atau keterangan "berdasarkan penawaran", serta **status verifikasi penyedia** dengan penanda jelas.
- Halaman detail layanan vendor baru (`/klien/katalog-vendor-detail`) dengan paket layanan & cakupan, kebijakan/kebutuhan khusus, galeri portofolio, rincian harga (harga layanan + biaya layanan Pesta Kita 10%), form "tambahkan ke acara", dan kartu status penyedia.
- Filter kategori, pencarian nama, dan harga maksimum **berjalan lokal di browser** (progressive enhancement).
- Penanda data prototipe yang eksplisit pada kedua halaman.
- Pembaruan dokumentasi konteks.

### Sengaja tidak dikerjakan
- Tahap 5.2–5.6 (katalog venue, request quote, kalender ketersediaan, dst.).
- Backend, database/migration, route, controller, model, API, autentikasi, `.env`.
- Fitur/data/aksi server palsu; aturan bisnis tidak diubah.
- Screenshot tidak dibuat.

---

## 2. File yang dibuat dan diubah

### Dibuat
| File | Tujuan |
|---|---|
| `public/assets/css/katalog.css` | Gaya khusus katalog Klien: penanda data prototipe (`.data-note`), badge status pada foto kartu (`.cat .ph .status`, `.status-ok`, `.status-pending`), meta kartu, label jenis harga (`.ptag`, `.ptag-fixed`, `.ptag-quote`, `.price-quote`), empty state (`.empty-state`), serta gaya halaman detail (`.vendor-sub`, `.pkg-list`/`.pkg`, `.policy`, `.gallery-ph`). Memakai design token `--pk-*` dan alias dari `admin.css`. |
| `public/assets/js/katalog-filter.js` | Filter lokal (tanpa request server, tanpa penyimpanan). Menyaring kartu `[data-kategori]` berdasarkan kategori (`?kategori=`), kata kunci nama, dan harga maksimum; menampilkan empty state `#kat-kosong`; tombol reset. Tanpa JS seluruh kartu tetap tampil. |
| `resources/views/klien/katalog-vendor-detail.blade.php` | Halaman detail layanan vendor (5.1). Memperluas layout `layouts.klien`, memuat `katalog.css` via `@push('styles')`. |

### Diubah
| File | Tujuan |
|---|---|
| `resources/views/klien/katalog-vendor.blade.php` | Disusun ulang: banner data prototipe, tab Vendor/Venue/Peralatan, pill kategori, toolbar pencarian, kartu vendor dengan badge verifikasi, meta lokasi/kapasitas, deskripsi singkat, harga tetap vs "Perlu penawaran", empty state, dan pemuatan `katalog-filter.js` via `@push('scripts')`. |
| `docs/AI_AGENT_CONTEXT.md` | Status roadmap Tahap 5, bagian hasil 5.1, dan riwayat pembaruan. |
| `docs/reports/tahap-5.1-katalog-vendor.md` | Laporan ini. |

Tidak ada file layout, route, konfigurasi, atau aset Tahap 1–4 yang diubah. Seluruh perubahan lokal yang sudah ada dipertahankan.

---

## 3. Perubahan tampilan dan fungsi

### Halaman daftar (`/klien/katalog-vendor`)
- **Banner data prototipe** (`.data-note`) menyatakan bahwa daftar layanan, harga, lokasi, kebijakan, dan status verifikasi adalah **contoh statis**, dan status verifikasi baru tampil setelah terhubung ke sistem.
- **Badge status pada kartu** membedakan `Terverifikasi` (`.status-ok`) dan `Menunggu verifikasi` (`.status-pending`) secara tekstual — tidak ada klaim "terverifikasi" untuk data yang belum diverifikasi sistem.
- **Label jenis harga**: `Harga tetap` untuk layanan berharga katalog, dan `Perlu penawaran` + "Berdasarkan penawaran" untuk layanan tanpa harga katalog (sesuai roadmap: harga katalog vs harga yang diajukan tidak boleh tertukar).
- **Filter lokal**: pill kategori, pencarian nama, dan harga maksimum menyaring kartu di sisi klien. Mendukung parameter `?kategori=` saat halaman dibuka (mis. dari tautan pill). Bila tidak ada yang cocok, muncul empty state dengan tombol "Tampilkan semua layanan".
- Setiap kartu menautkan ke halaman detail.

### Halaman detail (`/klien/katalog-vendor-detail`)
- **Header**: sub-judul penyedia + kategori + badge status verifikasi (contoh), tanpa menampilkan kontak pribadi penyedia.
- **Informasi**: kategori, penyedia, lokasi layanan, kapasitas; deskripsi; paket layanan & cakupan; kebijakan & kebutuhan khusus; galeri portofolio (placeholder dengan `role="img"` + `aria-label`, pesan "Belum ada foto portofolio").
- **Kartu harga**: rincian harga layanan (Rp 2.720.000) + biaya layanan Pesta Kita 10% (Rp 272.000) = total tagihan Klien (Rp 2.992.000), ditandai sebagai **data contoh** dan bahwa harga akhir ditetapkan sistem/backend setelah pesanan disetujui Admin.
- **Form "Tambahkan ke acara"**: field pilih acara, perkiraan jumlah tamu, catatan; tombol menggunakan `onsubmit="return false"` sehingga **tidak mengirim/menyimpan apa pun**. Diberi peringatan `.warn-box` bahwa ketersediaan tanggal belum dikonfirmasi dan pesanan disahkan Admin.

---

## 4. Catatan kejujuran implementasi

- Semua angka, nama usaha, lokasi, paket, dan kebijakan bersifat **contoh statis/prototipe** dan ditandai eksplisit pada kedua halaman.
- Tidak ada notifikasi "berhasil disimpan/terkirim" dan tidak ada penyimpanan server.
- Status verifikasi ditampilkan hanya sebagai contoh/badge dan dinyatakan mengikuti keputusan Admin.
- Form tidak melakukan submit (`onsubmit="return false"`), konsisten dengan perilaku preview `aksi.js`.
- Tidak ada klaim integrasi backend, API, atau penyimpanan.

---

## 5. Hasil pengujian aktual

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test --compact` | **Lulus** — 4 tests, 212 assertions. Termasuk `ProjectPortalPagesTest` (memeriksa setiap `{role}/*.blade.php` dapat diakses/`assertOk` dan tidak ada tautan `.html`) yang kini mencakup `klien/katalog-vendor` dan `klien/katalog-vendor-detail`, serta `ProjectPortalAccessibilityTest`. |
| `php artisan view:cache` | **Lulus** — Blade templates cached successfully. |
| `node --check public/assets/js/katalog-filter.js` | **Lulus** (tanpa error). |
| `node --check public/assets/js/app-ui.js` | **Lulus** (tanpa error). |
| `node --check public/assets/js/aksi.js` | **Lulus** (tanpa error). |
| `git diff --check` | **Lulus** (tanpa whitespace error). |

Tidak ada screenshot.

---

## 6. Regresi

- **Halaman & navigasi:** `ProjectPortalPagesTest` mengiterasi seluruh view tiap role (termasuk kedua halaman katalog vendor baru) dan memastikan setiap halaman mengembalikan HTTP 200 serta tidak mengandung tautan `.html` — lulus.
- **Aksesibilitas drawer:** `ProjectPortalAccessibilityTest` memastikan markup kontrol drawer bernama/berkeyboard pada `/admin`, `/klien`, `/vendor`, `/venue` — lulus (tidak ada perubahan layout).
- **Aset/Tahap 1–4:** tidak ada layout, CSS inti (`admin.css`, `klien.css`, `keselarasan.css`, `penyedia.css`, `design-tokens.css`), route, atau file tahap sebelumnya yang diubah. Halaman katalog lama tetap ada sebagai halaman terpisah.
- **Tautan navigasi sidebar Klien:** menu "Vendor & venue" menargetkan `/klien/katalog` dan sudah mencakup pola `klien/katalog-vendor*`; kedua halaman baru masuk ke pola ini tanpa perubahan layout.

---

## 7. Kebutuhan backend yang ditemukan

1. **Endpoint data katalog vendor** (nama usaha, kategori, deskripsi, lokasi, kapasitas, paket, kebijakan, media) — saat ini seluruhnya statis di Blade.
2. **Status verifikasi penyedia** yang bersumber dari sistem/Admin — frontend hanya menampilkan badge contoh dan tidak boleh mengklaim verifikasi tanpa data backend.
3. **Endpoint request quote / penawaran** dan alur persetujuan Admin (roadmap 5.4) — belum ada; form saat ini tidak mengirim.
4. **Ketersediaan tanggal** — belum ada sumber data; halaman hanya memberi peringatan bahwa ketersediaan belum dikonfirmasi. Instant Book tidak diterapkan.
5. **Unggahan/penyajian media portofolio** — belum ada; galeri masih placeholder.
6. **Perhitungan harga & komisi final** ditetapkan backend; angka rincian pada halaman detail adalah contoh.

---

## 8. Titik awal berikutnya

Tahap **5.2 Katalog venue** (roadmap baris 647–662), memakai pola dan stylesheet `katalog.css` yang sudah dibuat, dengan penekanan pada pemisahan biaya (mis. biaya kebersihan) agar tidak mengejutkan Klien. **Berhenti di sini** menunggu instruksi pengguna — jangan melanjutkan tanpa persetujuan.
