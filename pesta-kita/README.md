# Pesta Kita

Backend API untuk platform perencanaan acara yang menghubungkan klien dengan vendor dan venue. Aplikasi menggunakan Laravel, Laravel Sanctum untuk token API, dan MySQL sebagai database utama.

## Persiapan lokal

1. Salin `.env.example` menjadi `.env`, lalu isi `APP_KEY` dengan menjalankan `php artisan key:generate`.
2. Buat database MySQL bernama `pesta_kita`, lalu sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.
3. Pasang dependency dan jalankan migrasi:

   ```sh
   composer install
   php artisan migrate
   ```

4. Jalankan aplikasi:

   ```sh
   php artisan serve
   ```

Test suite menggunakan SQLite in-memory agar tidak memerlukan koneksi MySQL.

## Akun demo

Jalankan `php artisan db:seed` pada environment `local` untuk membuat akun demo berikut. Seeder aman dijalankan berulang kali dan menolak berjalan di luar environment `local` atau `testing`.

| Role | Email |
|---|---|
| Klien | `client@pestakita.test` |
| Admin | `admin@pestakita.test` |
| Vendor | `vendor@pestakita.test` |
| Venue | `venue@pestakita.test` |

Password seluruh akun: `password123`

## API

Semua endpoint memiliki prefix `/api`. Endpoint yang membutuhkan autentikasi memakai `Authorization: Bearer <token>` dari API register/login.

| Area | Endpoint utama |
|---|---|
| Auth & profil | `POST /register`, `POST /login`, `POST /logout`, `GET /me`, `PATCH /profile`, `PUT /profile/password` |
| Acara klien | `GET/POST /events`, `GET/PUT/PATCH/DELETE /events/{event}` |
| Katalog publik & pengelolaan | `GET /catalog-items`, `GET /catalog-items/{item}`, `POST /catalog-items`, `PUT/PATCH/DELETE /catalog-items/{item}` |
| Pesanan & tagihan | `GET/POST /orders`, `GET /orders/{order}`, `PATCH /orders/{order}/status`, `GET /invoices/{invoice}` |
| Ulasan | `POST /orders/{order}/reviews`, `GET /catalog-items/{item}/reviews` |
| Dashboard & notifikasi | `GET /dashboard`, `GET /notifications`, `PATCH /notifications/{id}/read` |
| Profil vendor/venue | `GET/PATCH /vendor/profile` |

Pembuatan pesanan otomatis membuat tagihan berstatus `pending`. Integrasi payment gateway dan konfirmasi pembayaran belum termasuk; status pembayaran tidak dapat diubah lewat API sampai provider pembayaran ditentukan.

Jalankan pengujian dengan `php artisan test`.
