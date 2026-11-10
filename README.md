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

### 2. Portal Klien (`/klien`) — 28 Halaman
- **Beranda Klien** (`/klien`): Metrik acara aktif, tagihan berjalan, dan antrean penilaian.
- **Manajemen Acara**: Rancang Acara (`/klien/acara`), Buat Konsep (`/klien/acara-baru`), Detail Acara (`/klien/acara-detail`), Draft, Diajukan, Terkonfirmasi, Pembatalan, dan Form Edit.
- **Katalog & Rekomendasi**: Katalog Vendor & Venue (`/klien/katalog`), Detail Katalog (`/klien/katalog-detail`), Khusus Vendor, Sewa Peralatan, dan Rekomendasi Paket Sesuai Budget.
- **Pesanan & Keuangan**: Pesanan (`/klien/pesanan`), Tagihan (`/klien/tagihan`, `/klien/tagihan-lunas`, `/klien/tagihan-ditolak`).
- **Hari-H & Riwayat**: Rundown Acara (`/klien/rundown`), Riwayat Acara (`/klien/riwayat`), Form Evaluasi (`/klien/evaluasi`).

### 3. Panel Vendor (`/vendor`) — 15 Halaman
- **Beranda Vendor** (`/vendor`): Metrik dana menunggu pencairan, rating rata-rata, pesanan baru (masa keberatan 24 jam), dan jadwal rundown terkonfirmasi.
- **Katalog & Peralatan**: Layanan Saya (`/vendor/layanan`), Form Item (`/vendor/item-form`), Peralatan Sewa (`/vendor/peralatan`), Form Alat (`/vendor/peralatan-form`), Pengajuan Perubahan Harga (`/vendor/pengajuan-harga`).
- **Operasional**: Pesanan Masuk (`/vendor/pesanan`), Detail Pesanan (`/vendor/pesanan-detail`), Keberatan (`/vendor/keberatan`), Rundown Kolaboratif (`/vendor/rundown`), dan Pencairan Dana (`/vendor/pencairan`, `/vendor/pencairan-detail`).
- **Akun**: Status Verifikasi (`/vendor/verifikasi`), Profil Usaha (`/vendor/profil`), Notifikasi (`/vendor/notifikasi`).

### 4. Panel Pemilik Venue (`/venue`) — 13 Halaman
- **Beranda Venue** (`/venue`): Metrik dana pencairan, ulasan venue, pesanan reservasi ruangan baru, dan jadwal acara terkonfirmasi.
- **Manajemen Venue**: Data & Denah Ruangan (`/venue/data`), Form Tambah/Edit Venue (`/venue/form`), Pengajuan Harga Sewa (`/venue/pengajuan-harga`).
- **Operasional**: Pesanan Masuk Venue (`/venue/pesanan`), Detail Pesanan Venue (`/venue/pesanan-detail`), Keberatan (`/venue/keberatan`), Pantau Rundown Acara (`/venue/rundown`), dan Pencairan Dana (`/venue/pencairan`, `/venue/pencairan-detail`).
- **Akun**: Status Verifikasi Dokumen (`/venue/verifikasi`), Profil Gedung (`/venue/profil`), Notifikasi (`/venue/notifikasi`).

### 5. Panel Admin (`/admin`) — 35 Halaman
- **Beranda Admin** (`/admin`): Ringkasan operasional dan antrean tindakan (validasi bukti pembayaran, mediasi keberatan terbuka, verifikasi mitra baru).
- **Operasional**: Review Pesanan (`/admin/pesanan`), Validasi Tagihan & Pembayaran (`/admin/pembayaran`), Mediasi Keberatan (`/admin/keberatan`), Pembatalan & Refund (`/admin/pembatalan`), Pencairan Dana (`/admin/pencairan`).
- **Mitra & Katalog**: Katalog Penyedia (`/admin/katalog`), Pengajuan Harga (`/admin/pengajuan-harga`), Kelola Pengguna & Mitra (`/admin/pengguna`), Rekening Perusahaan (`/admin/rekening`).
- **Pemantauan**: Pantau Acara (`/admin/acara`), Monitoring Rundown (`/admin/rundown`), Rekap Evaluasi & Rating (`/admin/evaluasi`), Log Aktivitas Audit (`/admin/aktivitas`), dan Pengaturan Sistem (`/admin/pengaturan`).

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
