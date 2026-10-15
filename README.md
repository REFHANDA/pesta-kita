# Pesta Kita

**Event Management System (EMS) berbasis web untuk acara ulang tahun privat.**

Pesta Kita membantu penyelenggara merencanakan acara ulang tahun dalam satu tempat: menyusun konsep acara,
memesan vendor, venue, dan peralatan teknis, mencatat tagihan dan pembayaran, menyusun rundown, sampai
mengevaluasi acara setelah selesai.

> **Status:** tahap perancangan selesai, implementasi sedang dimulai.
> Kode aplikasi dikerjakan di branch [`develop`](../../tree/develop). Branch `main` hanya berisi dokumentasi.

## Pengguna

| Aktor             | Peran                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------- |
| **Klien**         | Menyusun konsep acara, memesan vendor/venue/peralatan, membayar tagihan, menyusun rundown, mengisi evaluasi |
| **Vendor**        | Mengelola layanan dan peralatan sewa, menerima atau menolak pesanan, mengonfirmasi pembayaran               |
| **Pemilik Venue** | Mengelola data venue, menerima atau menolak pesanan, mengonfirmasi pembayaran, melihat rundown dan layout   |
| **Admin**         | Mengelola pengguna, memverifikasi akun penyedia jasa, memantau seluruh acara                                |

## Fitur Utama

- Registrasi, login, dan verifikasi akun vendor/pemilik venue oleh Admin
- Kelola konsep acara
- Cari dan pesan vendor; pesan venue beserta layout ruangan; sewa peralatan teknis
- Kelola layanan, peralatan sewa, dan data venue
- Pesanan masuk: terima atau tolak
- Tagihan, unggah bukti pembayaran, dan konfirmasi pembayaran
- Susun dan lihat rundown acara
- Evaluasi acara dan pemantauan oleh Admin
- Notifikasi perubahan status pesanan dan pembayaran
- Pencegahan bentrok jadwal venue dan stok peralatan

## Alur Sistem

```
Registrasi/Login → Susun Konsep Acara → Pilih Vendor/Venue/Peralatan → Pesanan Masuk
→ Persetujuan Penyedia → Tagihan → Bukti Pembayaran → Konfirmasi Pembayaran
→ Susun Rundown → Evaluasi
```

Jika pesanan ditolak: Notifikasi ke Klien → Pilih Layanan Lain → Pesanan Masuk.

## Batasan Sistem

Pembayaran dilakukan klien di luar sistem (misalnya transfer bank). Sistem hanya mencatat tagihan, bukti
pembayaran, dan konfirmasi pembayaran, tanpa modul pembukuan akuntansi.

## Teknologi

Laravel, Blade, MySQL.

## Tim

**Kelompok 3 · Teknik Informatika 4B**
Fakultas Teknik dan Ilmu Komputer, Universitas Nusantara PGRI Kediri, 2026

| Nama                          | NPM        |
| ----------------------------- | ---------- |
| Refanda Setyadi Eko Wicaksono | 2313020058 |
| Dimas Maulana                 | 2313020062 |
| Septia Anggraini              | 2313020067 |
| Aditya Bagas Bramatyo Kuzaky  | 2313020056 |
