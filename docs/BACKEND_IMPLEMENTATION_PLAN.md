# Rencana Implementasi Backend — Pesta Kita

Status diperbarui pada 2026-11-17: BE-0 sampai BE-4 telah dilaksanakan dan didokumentasikan. Tahap berikutnya adalah BE-5, tetapi jangan mulai sebelum pengguna memberi instruksi dan keputusan B7 yang dibutuhkan dikonfirmasi.

## 1. Arsitektur yang disarankan

Tetap monolit Laravel 13, session `web`, Blade, CSS, dan JavaScript yang sudah ada.

| Lapisan        | Tempat                                                              | Alasan                                                                                                                                                                        |
| -------------- | ------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Route          | `routes/web.php`                                                    | Sudah menjadi satu-satunya peta URL                                                                                                                                           |
| HTTP           | `app/Http/Controllers` dengan subfolder Admin, Klien, Vendor, Venue | Mengikuti portal yang sudah ada                                                                                                                                               |
| Validasi       | `app/Http/Requests`                                                 | Validasi di luar controller                                                                                                                                                   |
| Otorisasi      | `app/Policies` dan middleware role                                  | Menyembunyikan menu bukan pengaman                                                                                                                                            |
| Enum status    | `app/Enums`                                                         | Selaras dengan label Blade                                                                                                                                                    |
| Model          | `app/Models`                                                        | Folder yang sudah ada                                                                                                                                                         |
| Transaksi uang | kelas di `app/Actions`                                              | Folder baru. Perlu persetujuan karena pedoman proyek melarang folder dasar baru tanpa izin. Menaruh transaksi di controller membuat kunci status dan jejak audit sulit diuji. |

Tidak menambah React, Vue, paket pembayaran, atau API publik. Tidak menambah repository: belum ada pola itu, dan query MVP cukup di Eloquent.

## 2. Otorisasi

- Middleware `auth` lalu role pada setiap prefix portal.
- Policy: Klien hanya acara miliknya. Penyedia hanya item dan kalender miliknya. Admin untuk verifikasi, persetujuan, dan keuangan.
- Form Request menolak field harga final dari Klien dan field komisi dari penyedia.
- Demo bar tidak dirender di luar lingkungan local setelah BE-1.
- Catch-all `/{page}` tetap sementara untuk view yang belum punya controller, di dalam grup middleware. Route POST didaftarkan sebelum catch-all.

## 3. Urutan tahap

### BE-0 — Audit

Selesai sebagai dokumen pada 2026-11-15. Tidak ada perubahan kode aplikasi.

### BE-1 — Fondasi auth dan role

Dependensi: tidak ada tabel domain.

Isi: kolom `role` dan `status` pada `users`, profil kosong sesuai peran, register, login, logout, ganti sandi, middleware, seeder empat peran untuk lingkungan lokal, tes tamu ditolak dan peran lain 403.

Selesai bila halaman portal tetap tampil untuk pemilik peran, dan `ProjectPortalPagesTest` tidak lagi menuntut akses anonim.

### BE-2 — Skema domain non-finansial

Dependensi: BE-1. Keputusan B15 dan B4 telah disetujui 2026-11-16. B5 boleh kolom nullable.

Isi: migration katalog, venue, ruangan, media, ketersediaan, acara, penawaran, pesanan, dan audit log. Factory minimal. Belum ada alur uang.

### BE-3 — Katalog, profil, portofolio, kalender

Dependensi: BE-2.

Mengisi controller untuk halaman Tahap 5.1–5.3 dan 5.5. Harga di halaman Klien tetap estimasi. Verifikasi Admin mengontrol tampilnya katalog.

### BE-4 — Request quote

Dependensi: BE-2/BE-3 untuk katalog dan tabel quote; BE-3b tersedia untuk kalender. Tautan ke acara draft bersifat opsional.

**Selesai — terverifikasi (2026-11-17).** Delapan halaman penawaran terhubung ke database: Klien mengajukan quote dengan snapshot harga katalog; Vendor/Venue mengusulkan harga; Admin menyetujui atau menolak proposal terbaru. Harga katalog, harga usulan, dan harga disetujui tetap terpisah. Pengajuan quote tidak membuat acara; hanya dapat menautkan acara draft milik Klien. Keputusan bersifat final pada skema saat ini.

### BE-5 — Acara dan persetujuan pesanan

Dependensi: BE-4 untuk item yang perlu penawaran. Item harga tetap dapat menjadi pesanan tanpa quote.

Mengisi review Admin, penolakan per item, dan keberatan penyedia. Butuh jawaban B7 sebelum masa keberatan dikunci di kode.

### BE-6 — Tagihan, bayar, refund, cair

Dependensi: BE-5. Blocker produk: B1, B2, B3, B8, B9, B10, B13.

Jangan mengodekan persentase 70%. Jika B1 belum dijawab, tahap ini berhenti di penerbitan tagihan dan verifikasi bayar, tanpa aksi refund final.

Transaksi database, unique constraint, audit log, dan disk privat wajib. Tabel `jobs` bawaan tidak dipakai sampai ada kebutuhan kirim notifikasi.

### BE-7 — Rundown dan ulasan

Dependensi: acara terkonfirmasi dan, untuk ulasan, tagihan lunas.

Venue dan Admin tidak mendapat route tulis rundown. Checklist menunggu view checklist. Saat audit belum ada view checklist terpisah.

### BE-8 — Integrasi dan stabilisasi

Ganti sisa data contoh pada halaman yang tahapnya sudah hidup. Jalankan tes feature per alur, `view:cache`, dan Pint untuk PHP yang berubah. Periksa response Klien agar tidak memuat kontak penyedia atau nomor rekening utuh.

## 4. Strategi tes

- PHPUnit feature test lewat `php artisan make:test --phpunit`.
- Database tes tetap SQLite memori sesuai `phpunit.xml`.
- Tiap tahap menguji peran yang benar, peran yang salah, dan tamu.
- Uang: komisi, penolakan nominal yang tidak cocok, penolakan transisi status, dan penolakan pencairan ganda.
- Factory dengan state, bukan seeder contoh di dalam tes.
- Perintah sempit: `php artisan test --compact` pada file yang berubah.

## 5. Integrasi tampilan

Controller mengisi view yang sama. Penanda prototipe dilepas per halaman yang sudah hidup. Rekomendasi historis setelah auth adalah menyambungkan katalog lebih dahulu; BE-3 dan BE-4 kini selesai. Tahap implementasi berikutnya adalah BE-5 setelah ada instruksi pengguna dan keputusan B7.

## 6. Risiko

| Risiko                                                | Penanganan                                                                                                               |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Working tree frontend belum di-commit                 | Jangan reset, clean, atau checkout paksa.                                                                                |
| Catch-all menelan route baru                          | Daftarkan route eksplisit sebelum `/{page}`.                                                                             |
| Tes lama menuntut portal publik                       | Ubah tes pada BE-1. Jangan melepas middleware agar tes lama lulus.                                                       |
| Database `pesta_kita` telah digunakan                 | Sebelum tahap berikutnya, cek konfigurasi dan status migrasi saat ini; jangan reset atau membuat ulang database.         |
| `laravel/boost` ada di working tree `composer.json`   | Dipertahankan sesuai keputusan 2026-11-16. Jangan menambah, melepas, atau memperbarui dependency tanpa persetujuan baru. |
| Sebagian salinan UI venue masih mengikuti pola vendor | Policy backend memisahkan hak akses. Perbaikan teks UI dicatat terpisah dari migration.                                  |
