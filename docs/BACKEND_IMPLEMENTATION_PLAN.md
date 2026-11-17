# Rencana Implementasi Backend — Pesta Kita

Status diperbarui pada sesi implementasi backlog: BE-0 sampai BE-7 telah diimplementasikan. Backlog wiring yang disetujui juga sudah ditambahkan, termasuk notifikasi in-app, rekomendasi, checklist, denah, rekening payout penyedia, manajemen status akun, metrik dashboard, dan cetak invoice browser. Tes runtime dan verifikasi migration deployment masih menunggu PHP 8.4.1+ serta lingkungan yang disetujui; kebijakan refund final tetap ditunda.

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

**Selesai — terhubung.** Klien membuat/mengubah draft, menambah atau menghapus item katalog/ruangan, lalu mengajukan acara. Admin menyetujui atau menolak item; penyedia dapat mengajukan keberatan dalam 24 jam sejak persetujuan Admin. Satu acara dapat berisi beberapa venue. Invoice menunggu seluruh keputusan item dan masa keberatan selesai.

### BE-6 — Tagihan, bayar, refund, cair

Dependensi: BE-5.

**Selesai — terhubung dengan batas refund.** Admin menerbitkan versi invoice dengan snapshot baris, kode unik 3 digit yang masuk total, dan komisi 10% dari harga penyedia. Versi baru menonaktifkan versi sebelumnya; invoice kedaluwarsa tidak membatalkan pesanan. Jatuh tempo H-7 pukul 23.59 WIB, atau 24 jam setelah penerbitan jika sudah melewati H-7 (dibatasi akhir tanggal acara karena skema belum menyimpan jam acara). Klien mengunggah bukti privat; Admin memverifikasi nominal. Bukti disimpan privat dan dihapus setelah lima tahun sejak acara ditutup. Pencairan Admin dicatat satu kali per item dan bukti hanya dapat diunduh pemilik/admin sesuai peran.

Refund hanya menerima dan mencatat permohonan Klien. Keputusan, persentase, nominal final, dan transfer refund tetap nonaktif sampai kebijakan B1 disetujui.

Transaksi database, unique constraint, audit log, dan disk privat wajib. Notifikasi disimpan dalam tabel database dan dikirim sinkron; tabel `jobs` bawaan tidak digunakan.

### BE-7 — Rundown dan ulasan

Dependensi: acara terkonfirmasi dan, untuk ulasan, tagihan lunas.

**Selesai — terhubung.** Klien dan Vendor terkait dapat mengelola rundown; Venue dan Admin hanya membaca. Klien dapat mengirim ulasan setelah acara ditutup dan tagihan lunas; Vendor/Venue melihat agregat anonim. Checklist acara kini dikelola Klien, dengan akses baca-saja untuk penyedia terkait dan Admin.

### BE-8 — Integrasi dan stabilisasi

Backlog wiring yang teridentifikasi pada review sudah diimplementasikan: notifikasi database-only, rekomendasi dengan filter server-side, checklist acara, unggah/lihat denah privat, persetujuan rekening payout penyedia, manajemen status akun Admin, metrik komisi/rating, serta cetak invoice melalui browser. Refund final dan PDF server-side tidak dibuat.

Verifikasi deployment migration tetap memerlukan lingkungan yang disetujui. Pada mesin ini PHP 8.2.12 tidak memenuhi kebutuhan Composer PHP ≥8.4.1 sehingga Artisan/PHPUnit dan `view:cache` tidak dapat dijalankan ulang. Syntax check dan Pint dijalankan; hasil tes tidak boleh diklaim lulus pada sesi ini.

## 4. Strategi tes

- PHPUnit feature test lewat `php artisan make:test --phpunit`.
- Database tes tetap SQLite memori sesuai `phpunit.xml`.
- Tiap tahap menguji peran yang benar, peran yang salah, dan tamu.
- Uang: komisi, penolakan nominal yang tidak cocok, penolakan transisi status, dan penolakan pencairan ganda.
- Factory dengan state, bukan seeder contoh di dalam tes.
- Perintah sempit: `php artisan test --compact` pada file yang berubah.

## 5. Integrasi tampilan

Controller mengisi view yang ada dan halaman inbox/checklist/rekomendasi yang mengikuti pola layout portal. Fitur di luar backlog teridentifikasi dapat tetap berupa prototipe sampai wiring disepakati.

## 6. Risiko

| Risiko                                                | Penanganan                                                                                                               |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Working tree frontend belum di-commit                 | Jangan reset, clean, atau checkout paksa.                                                                                |
| Catch-all menelan route baru                          | Daftarkan route eksplisit sebelum `/{page}`.                                                                             |
| Tes lama menuntut portal publik                       | Ubah tes pada BE-1. Jangan melepas middleware agar tes lama lulus.                                                       |
| Database `pesta_kita` telah digunakan                 | Sebelum tahap berikutnya, cek konfigurasi dan status migrasi saat ini; jangan reset atau membuat ulang database.         |
| Migration BE-5–BE-7 belum diverifikasi pada lingkungan deployment | Terapkan hanya melalui proses deployment yang disetujui; tes lokal memakai SQLite in-memory.                     |
| PHP lokal 8.2.12, dependency memerlukan ≥8.4.1 | Artisan, PHPUnit, dan cache view terblokir; jalankan ulang setelah runtime yang sesuai tersedia. |
| `laravel/boost` ada di working tree `composer.json`   | Dipertahankan sesuai keputusan 2026-11-16. Jangan menambah, melepas, atau memperbarui dependency tanpa persetujuan baru. |
| Sebagian salinan UI venue masih mengikuti pola vendor | Policy backend memisahkan hak akses. Perbaikan teks UI dicatat terpisah dari migration.                                  |
