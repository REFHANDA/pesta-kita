# Pesta Kita — Platform Manajemen Pesta Ulang Tahun (Laravel + MySQL)

Aplikasi terintegrasi untuk perencanaan dan penyelenggaraan pesta ulang tahun: konsep acara, pemilihan vendor & venue, tata letak denah ruangan, pesanan satu pintu, pembayaran terpusat, koordinasi rundown, dan evaluasi.

Proyek ini dibangun menggunakan **Laravel Framework** dengan database **MySQL** dan antarmuka **Blade Engine** berbasis standar UI/UX Pesta Kita.

---

## 🚀 Fitur Utama & Struktur Antarmuka (86 Halaman)

Proyek ini telah mengintegrasikan seluruh rancangan antarmuka (UI/UX) ke dalam Blade templates Laravel:

### 1. Publik & Autentikasi
- **Landing Page** (`/`): Hero showcase, preview rundown interaktif, 3 langkah pemesanan ("Dari ide sampai hari-H"), katalog layanan siap pesan, CTA, dan demo portal switcher.
- **Masuk (Login)** (`/login`): Pemilih portal dinamis (Klien, Penyedia, Admin) dengan email default dan toggle "Ingat saya".
- **Daftar (Register)** (`/register`): Pendaftaran peran (Klien, Vendor, Pemilik Venue) dengan fieldset khusus profil usaha & kategori.

### 2. Portal Klien (`/klien`) — 28 Halaman
- **Beranda Klien** (`/klien`): Ringkasan acara aktif, tagihan menunggu, dan penilaian.
- **Manajemen Acara**:
  - Rancang Acara (`/klien/acara`), Buat Konsep Baru (`/klien/acara-baru`), Detail Acara (`/klien/acara-detail`)
  - Status Acara: Draft (`/klien/acara-draft`), Diajukan (`/klien/acara-diajukan`), Terkonfirmasi (`/klien/acara-terkonfirmasi`), Pembatalan (`/klien/acara-batalkan`, `/klien/acara-dibatalkan`), Form Edit (`/klien/acara-form`)
- **Eksplorasi Vendor & Venue**:
  - Katalog (`/klien/katalog`), Detail Katalog (`/klien/katalog-detail`), Khusus Vendor (`/klien/katalog-vendor`), Sewa Peralatan (`/klien/katalog-peralatan`), Rekomendasi Pintar (`/klien/rekomendasi`)
- **Pesanan & Keuangan**:
  - Pesanan (`/klien/pesanan`, `/klien/pesanan-detail`)
  - Tagihan Pembayaran (`/klien/tagihan`, `/klien/tagihan-lunas`, `/klien/tagihan-ditolak`)
- **Hari-H & Riwayat**:
  - Rundown Acara (`/klien/rundown`), Riwayat Acara (`/klien/riwayat`), Form Evaluasi (`/klien/evaluasi`, `/klien/evaluasi-selesai`)
- **Akun**: Profil (`/klien/profil`), Notifikasi (`/klien/notifikasi`)

### 3. Portal Penyedia (`/penyedia`) — 21 Halaman
- **Beranda Vendor & Pemilik Venue** (`/penyedia`): Metrik dana menunggu pencairan, rating ulasan, pesanan baru masa keberatan, jadwal rundown terkonfirmasi.
- **Katalog Layanan**:
  - Layanan Saya (`/penyedia/layanan`), Tambah/Edit Item (`/penyedia/item-form`)
  - Manajemen Venue (`/penyedia/venue`), Data Venue (`/penyedia/venue-data`), Form Venue (`/penyedia/venue-form`)
  - Sewa Peralatan (`/penyedia/peralatan`, `/penyedia/peralatan-form`)
  - Pengajuan Harga Khusus (`/penyedia/pengajuan-harga`)
- **Operasional**:
  - Pesanan Masuk (`/penyedia/pesanan`, `/penyedia/pesanan-detail`, `/penyedia/pesanan-venue-detail`)
  - Hak Keberatan (`/penyedia/keberatan`)
  - Rundown Acara (`/penyedia/rundown`)
  - Pencairan Dana (`/penyedia/pencairan`, `/penyedia/pencairan-detail`)
- **Kemitraan & Akun**:
  - Verifikasi Penyedia (`/penyedia/verifikasi-penyedia`), Registrasi Baru (`/penyedia/registrasi-penyedia`), Profil Usaha (`/penyedia/profil`), Notifikasi (`/penyedia/notifikasi`)

### 4. Panel Admin (`/admin`) — 35 Halaman
- **Beranda Admin** (`/admin`): Ringkasan operasional, kartu tindakan cepat (antrean validasi, keberatan terbuka, pengajuan harga, verifikasi).
- **Operasional**:
  - Review Pesanan (`/admin/pesanan`, `/admin/pesanan-detail`, `/admin/admin-pesanan-venue`)
  - Tagihan & Pembayaran (`/admin/pembayaran`, `/admin/pembayaran-detail`, `/admin/tagihan-detail`)
  - Mediasi Keberatan (`/admin/keberatan`, `/admin/keberatan-detail`)
  - Pembatalan & Refund (`/admin/pembatalan`, `/admin/pembatalan-detail`)
  - Pencairan Penyedia (`/admin/pencairan`, `/admin/pencairan-detail`)
- **Penyedia & Pengguna**:
  - Katalog Penyedia (`/admin/katalog`, `/admin/katalog-detail`)
  - Persetujuan Pengajuan Harga (`/admin/pengajuan-harga`, `/admin/pengajuan-harga-detail`)
  - Kelola Mitra & Pengguna (`/admin/pengguna`, `/admin/pengguna-detail`, `/admin/admin-penyedia`, `/admin/mitra-detail`, `/admin/admin-venue-form`)
  - Rekening Perusahaan (`/admin/rekening`)
- **Monitoring & Konfigurasi**:
  - Pantau Acara (`/admin/acara`, `/admin/acara-detail`)
  - Monitoring Rundown Realtime (`/admin/rundown`)
  - Evaluasi & Rating (`/admin/evaluasi`, `/admin/evaluasi-detail`)
  - Log Aktivitas Audit (`/admin/aktivitas`, `/admin/aktivitas-detail`)
  - Pengaturan Sistem (`/admin/pengaturan`), Profil (`/admin/profil`), Notifikasi (`/admin/notifikasi`, `/admin/notifikasi-detail`)

---

## 💻 Cara Menjalankan Proyek

### 1. Menjalankan Server Pengembangan
Jalankan perintah berikut di terminal:
```bash
php artisan serve
```
Buka browser di:
👉 **[http://localhost:8000](http://localhost:8000)**

Di bagian paling atas setiap halaman telah terpasang **Demo Bar Navigasi Cepat**, sehingga Anda dapat berpindah langsung antara **Landing Page**, **Portal Klien**, **Portal Penyedia**, dan **Panel Admin**.

---

## 🗄️ Konfigurasi Database (MySQL)

Konfigurasi database telah disiapkan pada berkas `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pesta_kita
DB_USERNAME=root
DB_PASSWORD=
```

Langkah untuk menyambungkan ke MySQL (misal via XAMPP):
1. Buka XAMPP Control Panel dan jalankan service **MySQL**.
2. Buat database baru bernama `pesta_kita` via phpMyAdmin (`http://localhost/phpmyadmin`) atau terminal:
   ```sql
   CREATE DATABASE pesta_kita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Jalankan migrasi tabel dasar:
   ```bash
   php artisan migrate
   ```

---

## 📁 Struktur Berkas Laravel

```text
pesta-kita/
├── app/                      # Backend Logic & Models (Laravel)
├── config/                   # Konfigurasi aplikasi
├── database/                 # Migrations & Seeders
├── public/
│   └── assets/
│       ├── css/              # admin.css, keselarasan.css, klien.css, penyedia.css, landing.css
│       ├── fonts/            # Figtree & Bricolage Grotesque (Self-hosted woff2)
│       └── favicon.svg       # Brand icon
├── resources/
│   └── views/
│       ├── layouts/          # master layouts: guest, klien, penyedia, admin
│       ├── partials/         # reusable partials: demo-bar
│       ├── auth/             # login.blade.php, register.blade.php
│       ├── klien/            # 28 blade templates portal klien
│       ├── penyedia/         # 21 blade templates portal penyedia
│       ├── admin/            # 35 blade templates panel admin
│       └── landing.blade.php # Landing page utama
├── routes/
│   └── web.php               # Rute web Laravel
├── _prototypes/              # Berkas prototype HTML asli (arsip referensi)
└── .env                      # Pengaturan environment & MySQL
```
