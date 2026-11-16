# Laporan Wiring Read-only Halaman Sisa — Pesta Kita

Tanggal: 2026-11-17 · Cabang: `refhanda-backend` · Status: **Selesai — terverifikasi** · Jenis: integrasi frontend ke backend yang sudah hidup (BE-1–BE-3b)

Acuan: `docs/reports/Frontend_Backend_Wiring_Review_Pesta_Kita.md` §4 (inventaris status wiring) dan `docs/reports/BE-3b_Wiring_Report_Pesta_Kita.md`. Instruksi pengguna: hubungkan frontend ke backend yang belum terhubung. Scope disepakati **read-only saja** (tanpa fitur tulis baru) dengan verifikasi feature test + regresi penuh.

## 1. Cara menentukan "belum terhubung"

Dua audit sistematis dijalankan lebih dulu (skrip sementara di `storage/`, sudah dihapus setelah dipakai):

1. **Silang nama rute × referensi view.** Seluruh rute non-vendor dicocokkan dengan `route('...')` / `url('/...')` di 122 view Blade. Hasil: hanya `admin.login` yang tidak dirujuk eksplisit (sengaja — halaman login portal ditautkan lewat `url()`), jadi **tidak ada endpoint yatim**.
2. **View ber-controller vs view catch-all.** Ditemukan bahwa metrik (1) lemah: sebuah halaman bisa "dirujuk" tetapi tetap merender data contoh. Karena itu diperiksa halaman mana yang **tabelnya sudah terisi oleh kode backend nyata** namun view-nya masih prototipe statis.

Empat halaman memenuhi kriteria itu; sisanya (±56 halaman) memang belum punya backend tulis dan sengaja tidak disentuh.

## 2. Halaman yang di-wiring

| Halaman                  | Sebelumnya                                                                                                   | Sekarang                                                                                                                                            | Sumber data                              |
| ------------------------ | ------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------- |
| `admin/aktivitas`        | 4 baris fiktif ("Pembayaran valid", "Refund dicatat", "PK/2026-11-0026") + toolbar `onsubmit="return false"` | Daftar entri `audit_logs` nyata; filter `?aksi=` dan pencarian `?q=` diproses **server-side**                                                       | `AuditLog` + `actor` + `subject` (morph) |
| `admin/aktivitas-detail` | Halaman fiktif (LOG-2026-1103-0042, nominal Rp18.750.000, rekening BRI, timeline palsu)                      | Detail per entri: aktor, modul, objek, `from_status → to_status`, alasan, IP; konteks harga untuk subjek `PriceChangeRequest`/`CatalogItem`/`Venue` | `AuditLog` via route model binding       |
| `klien/acara`            | 4 kartu contoh ("Ulang Tahun ke-7 Nadia", "Syukuran Opa 70 Tahun") taut ke halaman detail fiktif             | Acara **mendatang/berjalan** milik akun login (draft, diajukan, menunggu_pembayaran, terkonfirmasi)                                                 | `User::events()`                         |
| `klien/riwayat`          | 3 baris contoh + tombol "Beri penilaian"/"Lihat refund" mati                                                 | Acara **selesai / dinilai / dibatalkan** milik akun login                                                                                           | `User::events()`                         |

## 3. Perubahan file

### 3.1 Controller baru (2, read-only)

| File                                                   | Tanggung jawab                                                                                                                                                                                             |
| ------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Admin/ActivityLogController.php` | `index` (eager load `actor`+`subject`, filter aksi whitelist, pencarian aktor/alasan, `limit 200`), `show` (bind `AuditLog`). Konstanta `ACTION_LABELS` memetakan 6 aksi yang benar-benar ditulis backend. |
| `app/Http/Controllers/Klien/EventController.php`       | `index` (status mendatang, urut `event_date` asc), `riwayat` (status berakhir, urut desc). Query selalu lewat relasi akun login.                                                                           |

### 3.2 Route baru (4, sebelum catch-all `/{page}`)

- `GET /klien/acara` (`klien.acara`), `GET /klien/riwayat` (`klien.riwayat`)
- `GET /admin/aktivitas` (`admin.aktivitas`), `GET /admin/aktivitas/{auditLog}` (`admin.aktivitas.detail`)

Middleware `auth` + `role:klien` / `role:admin` tidak diubah; tidak ada rute tulis baru. Total rute non-vendor: **76** (dari 72).

### 3.3 View diubah (4)

`admin/aktivitas`, `admin/aktivitas-detail`, `klien/acara`, `klien/riwayat`. Memakai kelas CSS dan struktur markup yang sudah ada (`card`, `table-wrap`, `t-wide`, `badge b-*`, `data-note`, `detail-kv`) — **tanpa file CSS/JS baru, tanpa redesign**. Pola mengikuti konvensi wiring BE-3 §4.8: null-guard (`?? collect()`), `@forelse` + empty-state, catatan jujur soal fitur yang belum hidup.

### 3.4 Tes baru (2 file, 11 tes)

| File                                                  | Cakupan                                                                                                                                                                                                                                                                                                                                                       |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/Portal/ActivityLogTest.php` (7 tes)    | Entri nyata tampil & baris fiktif hilang; aksi tak dikenal dihumanisasi (bukan disembunyikan); filter aksi + pencarian bekerja server-side; nilai filter asing diperlakukan sebagai "semua"; detail menampilkan alasan/IP; aktor sistem (`actor_id` null) & subjek terhapus tertangani; empty state jujur; guard guest/role + `POST` ditolak 405 (read-only). |
| `tests/Feature/Portal/KlienEventListTest.php` (4 tes) | Hanya acara milik sendiri; pemisahan mendatang vs riwayat berdasar status; tidak ada kebocoran akun lain; string contoh prototipe hilang; guard guest dan role lain 403.                                                                                                                                                                                      |

## 4. Keputusan desain

- **Tidak ada tautan detail yang dipalsukan.** `klien/acara` dulu menaut tiap kartu ke `acara-detail`/`acara-draft`/`acara-terkonfirmasi` yang isinya data contoh. Karena backend detail (BE-4/BE-5) belum ada, kartu nyata **sengaja tidak diberi tombol "Buka"**, dan CTA header dipindah dari `klien/acara-baru` (form mati `onsubmit="return false"`) ke katalog yang sudah hidup. Hal yang sama untuk tombol "Beri penilaian"/"Lihat refund" di riwayat.
- **Log aktivitas hanya-baca.** Tidak ada rute tulis; halaman memuat entri yang memang ditulis keputusan backend (verifikasi mitra, ubah venue Admin, keputusan harga). Aksi BE-4/BE-5/BE-6 tidak bisa dipilih-palsukan.
- **Pager palsu dihapus.** "Menampilkan 1–5 dari 34 pengguna" / "1–3" diganti badge jumlah total nyata + catatan bila daftar dipotong 200 entri.
- **Kompatibilitas jalur lama.** `/admin/aktivitas-detail` (tanpa id) tetap dilayani catch-all dan tetap 200 lewat null-guard, sehingga `ProjectPortalPagesTest` (yang meminta setiap file view per role) tidak pecah.

## 5. Hasil verifikasi (aktual, dijalankan 2026-11-17)

| Pemeriksaan                                   | Hasil                                                                                                                                                           |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `php artisan test` (regresi penuh)            | **250 tes lulus / 1298 assertions** (baseline sesi ini: 239 / 1225 → +11 tes, +73 assertions)                                                                   |
| Tes terarah 2 file baru                       | 11 lulus                                                                                                                                                        |
| `php artisan view:cache`                      | "Blade templates cached successfully" (seluruh Blade ter-compile), lalu `view:clear`                                                                            |
| `php artisan route:list`                      | 76 rute non-vendor; 4 rute baru terdaftar sebelum catch-all                                                                                                     |
| Pint pada 4 file baru                         | `--test` bersih (line ending CRLF→LF diperbaiki)                                                                                                                |
| Pint proyek keseluruhan                       | 180 files, **6 isu gaya — semuanya pre-existing di file yang tidak disentuh sesi ini** (`MediaController`, migration BE-3b, `scripts/*`, `MediaManagementTest`) |
| Dependency / `.env` / konfigurasi / migration | Tidak diubah. Tidak ada migration baru.                                                                                                                         |
| Git                                           | Tanpa commit/push/merge/PR. Seluruh perubahan BE-3b sebelumnya dipertahankan.                                                                                   |

## 6. Temuan selama audit

1. **Metrik "nama rute dirujuk view" menyesatkan** untuk mengukur wiring — halaman bisa dirujuk tapi tetap merender data contoh. Inventaris §4 laporan wiring sebelumnya perlu dikoreksi: status "prototipe" bukan jaminan backend-nya belum ada.
2. **`audit_logs` masih kosong** di DB lokal (`pesta_kita`), sedangkan `events` 1 baris (draft), `users` 5, `availability_days` 3. Halaman log aktivitas karena itu tampil empty-state sampai Admin benar-benar mencatat keputusan — perilaku yang memang diharapkan.
3. **`admin/pengguna` tidak ikut di-wiring** (di luar scope yang disepakati): seluruh tombol aksinya (Nonaktifkan/Aktifkan/Setujui/Tolak) belum punya backend, jadi mengubah daftar jadi data nyata saja akan menyisakan aksi mati.
4. `klien/acara-baru`, `klien/acara-form`, `admin/pembayaran`, `vendor/pesanan-detail`, `klien/acara-batalkan`, `klien/permintaan-penawaran` masih mengandung form `onsubmit="return false"` — butuh backend BE-4/BE-5/BE-6, bukan wiring.

## 7. Sisa pekerjaan

| Tahap           | Halaman yang menunggu                                                                               | Blocker                                             |
| --------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| BE-4/BE-5       | Pembuatan & detail acara, permintaan penawaran, pesanan, keberatan                                  | Keputusan **B7** (masa keberatan) belum dijawab     |
| BE-6            | Tagihan, pembayaran, refund, pencairan, rekening                                                    | Keputusan **B1, B2, B3, B8–B10, B13** belum dijawab |
| BE-7            | Rundown, evaluasi/ulasan                                                                            | Butuh acara terkonfirmasi + tagihan lunas           |
| Belum terjadwal | `admin/pengguna*`, `admin/katalog*`, `admin/pengaturan`, notifikasi semua portal, rekomendasi klien | Perlu keputusan lingkup produk                      |

Seluruh halaman itu masih dapat dibuka lewat catch-all dan tetap memakai data contoh + `aksi.js` (toast pratinjau) — disengaja sampai backend tahap terkait hidup.
