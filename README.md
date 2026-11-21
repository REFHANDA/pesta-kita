# Pesta Kita — Platform Manajemen Pesta Ulang Tahun (Laravel + MySQL)

Aplikasi terintegrasi untuk perencanaan dan penyelenggaraan pesta ulang tahun privat: konsep acara, pemilihan vendor & venue, tata letak denah ruangan, pesanan satu pintu, pembayaran terpusat ke rekening perusahaan, koordinasi rundown, dan evaluasi berjenjang.

Proyek ini dibangun menggunakan **Laravel Framework** dengan database **MySQL** dan arsitektur **Blade Templates** berbasis standar UI/UX Pesta Kita.

---

## 👥 4 Peran Pengguna (Sesuai Dokumen Perancangan EMS)

Sesuai dokumen perancangan sistem (*Use Case*, *Activity*, dan *Class Diagram*), platform membagi hak akses ke dalam **4 aktor utama**:

| Aktor | URL Portal | Kredensial Demo | Fitur & Tanggung Jawab Utama |
|---|---|---|---|
| **Klien** | `/klien` | `klien@pestakita.test` | Rancang acara & budget, pilih rekomendasi paket, pilih vendor & venue, atur denah/layout ruangan, bayar 1 tagihan gabungan, susun rundown kolaboratif, dan beri rating evaluasi. |
| **Vendor** | `/vendor` | `vendor@pestakita.test` | Kelola katalog menu & paket layanan (katering, dekorasi, hiburan), sewa peralatan, pantau pesanan masuk, ajukan keberatan 24 jam & perubahan harga, isi rundown sendiri, dan cek pencairan dana. |
| **Pemilik Venue** | `/venue` | `venue@pestakita.test` | Kelola data & denah venue, pantau jadwal reservasi gedung, ajukan keberatan & perubahan harga sewa, melihat rundown acara (*read-only*), dan cek pencairan dana. |
| **Admin Perusahaan** | `/admin` | `admin@pestakita.test` | Verifikasi mitra, review pesanan (setujui/tolak/alihkan), validasi bukti transfer pembayaran klien, mediasi keberatan penyedia, kelola rekening perusahaan, catat pencairan, dan audit log aktivitas. |

---

## 🚀 Fitur Utama & Struktur Antarmuka

### 1. Publik & Autentikasi
- **Landing Page** (`/`): Showcase fitur, live rundown preview, 3 langkah ("Dari ide sampai hari-H"), katalog layanan siap pakai, dan switcher 4 portal peran.
- **Masuk (Login)** (`/login`): Pemilih 4 peran akun terpisah (Klien, Vendor, Pemilik Venue, Admin) dengan auto-fill email default dan pengalihan ke dashboard masing-masing.
- **Daftar (Register)** (`/register`): Pendaftaran peran terpisah dengan form khusus profil usaha dan kategori usaha untuk Vendor dan Pemilik Venue.

### 2. Portal Klien (`/klien`) — 5 menu
- **Beranda** · **Acara saya** (detail memuat pesanan, tagihan, rekomendasi paket untuk draft, susunan ruangan, rundown, dan evaluasi) · **Katalog** (tab Vendor/Venue/Peralatan, dengan rating) · **Notifikasi** · **Profil**.
- Tombol mengambang **Tanya Admin** (WhatsApp) — nomor diatur Admin di Pengaturan. Klien tidak melihat kontak vendor/venue.

### 3. Panel Vendor (`/vendor`) — 6 menu
- **Beranda** (termasuk kartu rating dan penilaian terbaru) · **Katalog saya** (Layanan/Peralatan/Pengajuan harga) · **Pesanan** (Aktif/Riwayat/Keberatan 24 jam) · **Rundown** (tulis item sendiri) · **Pencairan** · **Profil & verifikasi**.

### 4. Panel Pemilik Venue (`/venue`) — 6 menu
- **Beranda** (termasuk kartu rating) · **Venue saya** (Data, denah per ruangan, Pengajuan harga) · **Pesanan** · **Rundown** (hanya-baca) · **Pencairan** · **Profil & verifikasi**.

### 5. Panel Admin (`/admin`) — 9 menu
- **Beranda** · **Pesanan** · **Tagihan & pembayaran** · **Pembatalan & refund** · **Pencairan** · **Penyedia & katalog** · **Acara** (tab semua/evaluasi; detail memuat rundown, evaluasi, susunan ruangan) · **Pengaturan** · **Log aktivitas**.

**Status modul:** pembatalan acara (otomatis sebelum bayar dan pengajuan setelah lunas), refund, tandai acara selesai, dan pencairan penyedia (hak pesanan dan kompensasi) sudah berjalan (Tahap B). Rundown dan evaluasi menyusul.

Semua halaman memakai route eksplisit bernama (tanpa catch-all); URL yang tidak terdaftar menghasilkan 404. Notifikasi in-app dibaca dari basis data per pengguna, dengan lencana jumlah belum dibaca di header. Rincian perubahan: `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md` bagian 2 dan 3.

---

## 💻 Cara Menjalankan Aplikasi

Jalankan perintah berikut di terminal:
```bash
php artisan serve
```

Buka browser di:
👉 **[http://localhost:8000](http://localhost:8000)**

Di bagian paling atas layar terdapat **Demo Bar Navigasi**, memungkinkan Anda berpindah langsung ke portal mana pun dengan 1 klik.

---

## 🗄️ Konfigurasi Database (MySQL)

Konfigurasi database telah terpasang di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pesta_kita
DB_USERNAME=root
DB_PASSWORD=
```

Langkah untuk menghubungkan ke MySQL (misal via XAMPP):
1. Buka XAMPP Control Panel dan aktifkan service **MySQL**.
2. Buat database `pesta_kita` di phpMyAdmin (`http://localhost/phpmyadmin`) atau melalui query:
   ```sql
   CREATE DATABASE pesta_kita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Jalankan migrasi tabel:
   ```bash
   php artisan migrate
   ```

---

## Status Modul

Seluruh modul Tahap A–D selesai dan diuji otomatis (`php artisan test`, 509 tes).

| Modul | Status |
|---|---|
| Autentikasi, peran, dan navigasi 4 portal | Selesai |
| Katalog vendor/venue, ketersediaan, pengajuan harga | Selesai |
| Acara draft, penambahan item, pengajuan, tagihan, pembayaran manual | Selesai |
| Review pesanan Admin, keberatan 24 jam, pembatalan & refund | Selesai |
| Acara selesai, pencairan hak pesanan, kompensasi | Selesai |
| Rundown acara (klien, vendor, venue hanya lihat, Admin hanya lihat) | Selesai (Tahap C) |
| Evaluasi dan rating (satu per acara, final) | Selesai (Tahap C) |
| Notifikasi in-app (rundown, evaluasi, pembayaran, pencairan) | Selesai (Tahap C) |
| Rekomendasi paket berbasis anggaran | Selesai (Tahap D; estimasi biaya total sejak Tahap F) |
| Denah ruangan dan susunan ruangan | Selesai (Tahap D; denah ikut publik saat verifikasi sejak Tahap F) |
| Rekening pencairan penyedia: diisi penyedia, diverifikasi Admin | Selesai (Tahap G) |

#### Menjalankan lokal

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed      # LocalDemoSeeder hanya berjalan di APP_ENV=local
php artisan serve
```

Akun demo (kata sandi `password`): `klien@pestakita.test`, `vendor@pestakita.test`, `venue@pestakita.test`, `admin@pestakita.test`.

### Menjalankan tes

```bash
php artisan test --compact
vendor/bin/pint --dirty
```
