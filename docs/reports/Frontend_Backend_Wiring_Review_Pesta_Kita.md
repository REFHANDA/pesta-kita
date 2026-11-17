# Laporan Review Wiring Frontend–Backend — Pesta Kita

Tanggal: 2026-11-16 · Status: **Selesai — terverifikasi** · Jenis: integrasi tampilan ke backend yang sudah ada (BE-1–BE-3)

Acuan: `docs/BACKEND_IMPLEMENTATION_PLAN.md` §5 (integrasi tampilan), `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md`, `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md` §4.8 (konvensi wiring). Instruksi pengguna: hubungkan frontend yang sudah jadi ke backend yang sudah dibuat, lalu tulis laporan review di folder docs.

## 1. Pendekatan dan batasan

- Wiring hanya memakai backend yang **sudah ada** (BE-1 auth/profil, BE-3 katalog/venue/media/verifikasi). Tidak ada fitur bisnis baru, tidak ada migration, tidak ada keputusan bisnis baru yang dikodekan.
- Tanpa redesign: seluruh perubahan memakai kelas CSS dan struktur yang sudah ada; tidak ada file CSS/JS baru.
- Halaman yang backend-nya belum ada (BE-3b, BE-4–BE-8) **tidak dipalsukan**: data contoh diganti catatan jujur (`data-note`) atau dibiarkan sebagai prototipe dan didokumentasikan di laporan ini.
- Tidak ada perintah destruktif, tidak ada perubahan `.env`/credential/dependency, tidak ada commit/push/merge/PR. Seluruh perubahan sebelumnya dipertahankan.
- Pembersihan terkait pada sesi yang sama: demo/preview bar dihapus total (partial `partials/demo-bar.blade.php`, 5 `@include` layout, dan `DemoBarTest.php` yang usang).

## 2. Perubahan yang dilakukan

### 2.1 Controller baru (4, read-only)

| Controller                                            | Data nyata yang disiapkan                                                                                                                      |
| ----------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Klien/DashboardController.php`  | Acara milik akun login (urut tanggal), jumlah acara berjalan (draft/diajukan/menunggu_pembayaran/terkonfirmasi) dan selesai (selesai/dinilai). |
| `app/Http/Controllers/Vendor/DashboardController.php` | Profil usaha, status verifikasi, seluruh item katalog milik sendiri, jumlah layanan aktif dan peralatan aktif.                                 |
| `app/Http/Controllers/Venue/DashboardController.php`  | Profil usaha, status verifikasi, venue milik sendiri + `rooms_count`, jumlah venue tayang dan total ruangan.                                   |
| `app/Http/Controllers/Admin/DashboardController.php`  | Jumlah akun klien, penyedia terverifikasi (vendor+venue), acara, dan antrean verifikasi menunggu (vendor+venue).                               |

`routes/web.php`: empat route beranda portal (`klien.beranda`, `vendor.beranda`, `venue.beranda`, `admin.beranda`) diganti dari closure view kosong menjadi controller di atas. Middleware `auth` + `role` tidak berubah; catch-all `/{page}` tetap untuk halaman prototipe.

### 2.2 View beranda portal (4) — data nyata + catatan jujur

| View           | Isi baru                                                                                                                                                                                                                                                                                                                                   |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `klien/index`  | Sapaan nama akun asli; statistik acara berjalan/selesai/total dari database; daftar acara nyata dengan badge status `EventStatus`; empty-state. Kartu tagihan (BE-6) dan penilaian (BE-7) diganti catatan penundaan; CTA diarahkan ke katalog yang sudah hidup. Data contoh "Rina", "PK/2026/11/0007", dll. dihapus.                       |
| `vendor/index` | Sapaan `business_name`; alert status verifikasi nyata (menunggu/ditolak+alasan/terverifikasi/nonaktif) dengan tautan ke halaman verifikasi; statistik layanan aktif/peralatan aktif/total item; tabel 5 item terbaru dengan tautan ubah nyata. Angka pencairan/rating/pengajuan harga palsu dihapus, diganti catatan BE-4/BE-6/BE-7/BE-3b. |
| `venue/index`  | Pola sama dengan vendor: statistik venue tayang/total venue/total ruangan; tabel 5 venue terbaru + jumlah ruangan + tautan ubah nyata; catatan penundaan fitur transaksi.                                                                                                                                                                  |
| `admin/index`  | Statistik klien/penyedia terverifikasi/acara/verifikasi menunggu dari database; antrean "Perlu tindakan" nyata hanya verifikasi mitra (tautan `admin.penyedia.index` + jumlah asli); kartu pendapatan dan rating diganti catatan BE-6/BE-7; 10 baris antrean transaksi palsu dihapus.                                                      |

Semua view diberi null guard (`?? collect()` / `?? 0`) dan empty-state, mengikuti konvensi wiring BE-3.

### 2.3 Layout portal (4) — identitas asli, logout nyata, badge palsu dihapus

Berlaku untuk `layouts/klien`, `layouts/vendor`, `layouts/venue`, `layouts/admin` (dipakai seluruh halaman portal):

- **Topbar**: nama/email/avatar hardcoded ("Rina Putri", "Dapur Nusantara", "Graha Melati", "Admin" + email `*@pestakita.test`) diganti identitas akun login. Vendor/venue menampilkan `business_name` profil (fallback nama akun).
- **Keluar**: tautan GET `/login` (tidak pernah logout) diganti form `POST /logout` + `@csrf`; tombol diberi gaya inline menyerupai `.nav a` tanpa mengubah stylesheet.
- **Badge angka palsu** (`1/2/3 perlu tindakan`, `2/3 belum dibaca`) pada navigasi dihapus — backend notifikasi/pesanan belum ada, angka tidak boleh mengimplikasikan data nyata. Tautan menu tetap.
- **Lonceng notifikasi**: klaim "N belum dibaca" dan titik unread palsu dihapus; tautan ke halaman notifikasi tetap.

### 2.4 Jalur pendaftaran yang terputus (diperbaiki)

| Masalah                                                                                                                                              | Perbaikan                                                                                  |
| ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `klien/login` menautkan "Daftar" ke `/klien/register` yang berada di bawah middleware `auth+role:klien` — guest dilempar kembali ke `/login` (loop). | Tautan diganti ke `/register` (form pendaftaran resmi BE-1).                               |
| `vendor/login` menautkan "Daftar sebagai penyedia" ke `/vendor/registrasi-penyedia` yang juga di bawah auth — tidak terjangkau guest.                | Tautan diganti ke `/register`.                                                             |
| `klien/register` berisi form mati (`onsubmit="return false"`), duplikat pendaftaran resmi.                                                           | Diganti halaman penunjuk jujur ke `/register` (tanpa redesign).                            |
| `vendor/registrasi-penyedia` berisi form mati serupa, termasuk opsi peran ganda yang melanggar aturan satu peran per akun.                           | Diganti halaman penunjuk ke `/register` + `/vendor/verifikasi`; klaim peran ganda dihapus. |

`admin/login` sengaja tanpa tautan daftar (tidak ada registrasi Admin publik — aturan terkunci).

### 2.5 Tes baru

`tests/Feature/Portal/DashboardTest.php` — 10 tes / 63 assertions:

1. Beranda klien menampilkan nama akun + acara nyata; tidak memuat acara klien lain maupun data contoh prototipe.
2. Beranda vendor menampilkan nama usaha + item milik sendiri; item vendor lain tidak bocor; alert verifikasi menunggu tampil.
3. Beranda venue menampilkan venue + ruangan milik sendiri; venue pemilik lain tidak bocor.
4. Beranda admin menampilkan antrean verifikasi nyata ("2 menunggu") dan tautan antrean; angka pendapatan palsu tidak ada.
5. Guest ditolak (redirect `/login`) dan role salah 403 di keempat beranda.
6. `registrasi-penyedia` menunjuk pendaftaran resmi; tanpa form mati.
7. Alasan penolakan verifikasi tampil di beranda vendor.
8. Topbar layout memakai identitas asli + form logout POST.
9. Halaman login portal menautkan `/register` yang terjangkau guest.
10. `klien/register` menunjuk pendaftaran resmi; tanpa form mati.

Baseline lama tidak diubah atau dilemahkan; `ProjectPortalPagesTest` (semua halaman portal render untuk role pemilik, termasuk saat database kosong) dan `ProjectPortalAccessibilityTest` tetap hijau.

## 3. Hasil verifikasi (aktual, dijalankan 2026-11-16)

| Pemeriksaan                                              | Hasil                                                        |
| -------------------------------------------------------- | ------------------------------------------------------------ |
| Tes terarah `DashboardTest`                              | 10 lulus                                                     |
| **Regresi penuh `php artisan test` (akhir, pasca-Pint)** | **226 lulus (1176 assertions)**                              |
| `php artisan view:cache`                                 | Seluruh Blade terkompilasi sukses (lalu `view:clear`)        |
| Pint pada file PHP berubah                               | 5 isu gaya diperbaiki (line ending/EOF); `--test` ulang PASS |
| Middleware auth/role                                     | Tidak diubah; guest → `/login`, role salah → 403 (diuji)     |
| Dependency / `.env` / konfigurasi                        | Tidak diubah                                                 |
| Git                                                      | Tanpa commit/push/merge/PR; perubahan lama dipertahankan     |

## 4. Inventaris status wiring seluruh halaman

Keterangan: **BE-1** = wiring auth/profil; **BE-3** = wiring katalog/venue/media/verifikasi; **Sesi ini** = wiring pada laporan ini; **Prototipe** = belum ada backend, menunggu tahap terkait.

### 4.1 Publik & auth

| View                                           | Status                                                                                                                                             |
| ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `landing`                                      | Publik; tautan nyata ke `/login`, `/register`, portal. Konten marketing statis (wajar).                                                            |
| `auth/login`, `auth/register`                  | BE-1 (form nyata + validasi server).                                                                                                               |
| `klien/login`, `vendor/login`, `admin/login`   | BE-1; tautan daftar diperbaiki sesi ini.                                                                                                           |
| `klien/register`, `vendor/registrasi-penyedia` | Sesi ini: halaman penunjuk ke `/register` (form mati dihapus).                                                                                     |
| `welcome`                                      | Sisa bawaan Laravel, tidak dirutekan (`/` memakai `landing`). Kandidat hapus pada pembersihan berikutnya (tidak dihapus sekarang tanpa instruksi). |
| `layouts/*`                                    | Sesi ini: identitas asli, logout POST, badge palsu dihapus. Demo bar dihapus total.                                                                |

### 4.2 Portal Klien (33 view)

| Status                                  | View                                                                                                                                                                                                                                  |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BE-1                                    | `profil`                                                                                                                                                                                                                              |
| BE-3                                    | `katalog-vendor`, `katalog-peralatan`, `katalog` (venue), `katalog-vendor-detail`, `katalog-venue-detail`, `penyedia-vendor`, `penyedia-venue`                                                                                        |
| Sesi ini                                | `index` (beranda), `login`, `register`                                                                                                                                                                                                |
| Prototipe → BE-4/BE-5 (acara & pesanan) | `acara`, `acara-baru`, `acara-form`, `acara-detail`, `acara-draft`, `acara-diajukan`, `acara-terkonfirmasi`, `acara-batalkan`, `acara-dibatalkan`, `pesanan`, `pesanan-detail`, `permintaan-penawaran`, `permintaan-penawaran-detail` |
| Prototipe → BE-6 (tagihan)              | `tagihan`, `tagihan-lunas`, `tagihan-ditolak`                                                                                                                                                                                         |
| Prototipe → BE-7 (rundown & ulasan)     | `rundown`, `evaluasi`, `evaluasi-selesai`, `riwayat`                                                                                                                                                                                  |
| Prototipe → BE-3b (kalender)            | `kalender-ketersediaan`                                                                                                                                                                                                               |
| Prototipe → belum terjadwal             | `notifikasi` (backend notifikasi belum ada), `rekomendasi` (belum ada mesin rekomendasi), `katalog-detail` (duplikat prototipe; detail nyata ada di `katalog-vendor-detail`/`katalog-venue-detail`)                                   |

### 4.3 Portal Vendor (20 view)

| Status                      | View                                                                                            |
| --------------------------- | ----------------------------------------------------------------------------------------------- |
| BE-1                        | `profil` (data akun/usaha + foto)                                                               |
| BE-3                        | `layanan`, `peralatan`, `item-form`, `peralatan-form`, `verifikasi`                             |
| Sesi ini                    | `index` (beranda), `login`, `registrasi-penyedia`                                               |
| Prototipe → BE-4/BE-5       | `pesanan`, `pesanan-detail`, `permintaan-penawaran`, `permintaan-penawaran-detail`, `keberatan` |
| Prototipe → BE-6            | `pencairan`, `pencairan-detail`                                                                 |
| Prototipe → BE-7            | `rundown`                                                                                       |
| Prototipe → BE-3b           | `kalender-ketersediaan`, `pengajuan-harga`                                                      |
| Prototipe → belum terjadwal | `notifikasi`                                                                                    |

### 4.4 Portal Pemilik Venue (18 view)

| Status                      | View                                                                                            |
| --------------------------- | ----------------------------------------------------------------------------------------------- |
| BE-1                        | `profil` (data akun/usaha + foto)                                                               |
| BE-3                        | `data`, `form` (venue + ruangan + fasilitas + foto), `verifikasi`                               |
| Sesi ini                    | `index` (beranda)                                                                               |
| Prototipe → BE-4/BE-5       | `pesanan`, `pesanan-detail`, `permintaan-penawaran`, `permintaan-penawaran-detail`, `keberatan` |
| Prototipe → BE-6            | `pencairan`, `pencairan-detail`                                                                 |
| Prototipe → BE-7            | `rundown`                                                                                       |
| Prototipe → BE-3b           | `kalender-ketersediaan`, `pengajuan-harga`                                                      |
| Prototipe → belum terjadwal | `notifikasi`                                                                                    |

Catatan: portal venue tidak memiliki halaman login khusus; pemilik venue masuk lewat `/login` (redirect server sesuai role — T1).

### 4.5 Panel Admin (40 view)

| Status                      | View                                                                                                                                                                                                                                                                                     |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BE-1                        | `profil`                                                                                                                                                                                                                                                                                 |
| BE-3                        | `admin-penyedia` (antrean verifikasi), `mitra-detail` (approve/tolak), `admin-venue-form` (cleaning_fee + status individual)                                                                                                                                                             |
| Sesi ini                    | `index` (beranda), `login`                                                                                                                                                                                                                                                               |
| Prototipe → BE-4/BE-5       | `pesanan`, `pesanan-detail`, `admin-pesanan-venue`, `permintaan-penawaran`, `permintaan-penawaran-detail`, `keberatan`, `keberatan-detail`, `acara`, `acara-detail`                                                                                                                      |
| Prototipe → BE-6            | `tagihan-detail`, `pembayaran`, `pembayaran-detail`, `pembatalan`, `pembatalan-detail`, `pencairan`, `pencairan-detail`, `rekening`, `rekening-form`                                                                                                                                     |
| Prototipe → BE-7            | `rundown`, `evaluasi`, `evaluasi-detail`                                                                                                                                                                                                                                                 |
| Prototipe → BE-3b           | `pengajuan-harga`, `pengajuan-harga-detail`                                                                                                                                                                                                                                              |
| Prototipe → belum terjadwal | `pengguna`, `pengguna-detail`, `pengguna-form` (manajemen akun), `katalog`, `katalog-detail`, `katalog-form` (tinjauan katalog; tidak ada approve per item — keputusan Gate 4B butir 2), `aktivitas`, `aktivitas-detail` (log audit UI), `notifikasi`, `notifikasi-detail`, `pengaturan` |

## 5. Masalah yang ditemukan dan diselesaikan selama wiring

1. **Identitas palsu di layout bersama** — topbar semua portal memakai nama/email contoh; ditemukan lewat tes wiring (`assertDontSee('Graha Melati')` gagal karena layout, bukan view beranda). Diperbaiki di keempat layout.
2. **"Keluar" tidak pernah logout** — tautan GET `/login` di sidebar; diganti form POST `/logout` ber-CSRF di keempat layout.
3. **Loop pendaftaran guest** — "Daftar" di `klien/login` dan `vendor/login` menuju halaman di bawah middleware auth, sehingga guest dilempar kembali ke login. Tautan diarahkan ke `/register`.
4. **Form mati duplikat pendaftaran** (`onsubmit="return false"`) di `klien/register` dan `vendor/registrasi-penyedia`, termasuk opsi "Vendor dan pemilik venue" yang melanggar aturan satu peran per akun. Diganti halaman penunjuk jujur.
5. **Badge angka palsu di navigasi** memberi kesan ada antrean nyata padahal backend belum ada; dihapus tanpa menghapus menunya.

Tidak ada konflik dengan aturan bisnis terkunci; tidak ada keputusan bisnis baru yang diperlukan.

## 6. Sisa pekerjaan (menunggu tahap backend / instruksi)

| Tahap           | Halaman yang menunggu                                                                                 | Blocker                                                                                                |
| --------------- | ----------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| BE-3b           | Kalender ketersediaan (klien/vendor/venue), pengajuan harga (vendor/venue/admin)                      | Tabel `availability_days` & `price_change_requests` sudah ada sejak BE-2; logika + route belum dibuat. |
| BE-4/BE-5       | Seluruh alur acara, permintaan penawaran, pesanan, keberatan (klien/vendor/venue/admin)               | Keputusan B7 (masa keberatan) belum dijawab; tambah item ke draft ditunda ke tahap ini.                |
| BE-6            | Tagihan, pembayaran, pembatalan/refund, pencairan, rekening perusahaan                                | Keputusan B1 (refund), B2 (kode unik), B3 (dasar komisi), B8–B10, B13 belum dijawab.                   |
| BE-7            | Rundown, evaluasi/ulasan, riwayat                                                                     | Butuh acara terkonfirmasi + tagihan lunas.                                                             |
| Belum terjadwal | Notifikasi semua portal, manajemen pengguna/katalog/log aktivitas/pengaturan Admin, rekomendasi klien | Perlu keputusan lingkup produk.                                                                        |

Halaman prototipe di atas tetap dapat dibuka (catch-all di dalam middleware role) dan masih memakai data contoh serta `aksi.js` (toast pratinjau). Itu disengaja sampai backend tahap terkait hidup; wiring lanjutan mengikuti konvensi BE-3 §4.8.

## 7. Rekomendasi langkah berikutnya

1. Tinjau laporan ini dan halaman beranda keempat portal pada environment lokal dengan akun demo per role.
2. Putuskan prioritas tahap berikutnya: BE-3b (kalender + pengajuan harga, tanpa blocker keputusan) atau BE-4 (quote; perlu jawaban B7 untuk BE-5).
3. Pertimbangkan commit bertahap (baseline frontend, BE-1, BE-2, BE-3, wiring sesi ini) atas persetujuan pengguna untuk mengunci baseline — seluruh perubahan saat ini belum di-commit.
4. Pembersihan kecil opsional: hapus `welcome.blade.php` bawaan Laravel dan variabel CSS `--pk-z-preview` yang kini tak terpakai (menunggu instruksi).

## 8. Catatan status setelah implementasi berikutnya (2026-11-17)

Laporan ini adalah snapshot review wiring BE-1–BE-3 per 2026-11-16. Inventaris pada §4, sisa pekerjaan pada §6, dan rekomendasi prioritas pada §7 tidak lagi mencerminkan status terbaru: BE-3b dan BE-4–BE-7 telah diimplementasikan, dengan batas refund pada BE-6. Status backlog terbaru dicatat pada §10 `Laporan_Update_Terbaru_Frontend_Backend_Pesta_Kita.md`; gunakan bagian tersebut sebagai acuan terkini. Isi laporan ini tetap dipertahankan sebagai rekam hasil review awal.
