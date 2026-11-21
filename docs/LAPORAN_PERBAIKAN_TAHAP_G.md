# Laporan Perbaikan Tahap G — Rekening Pencairan Penyedia dan Bug UI

Basis: ZIP `pesta-kita-tahap-g.zip` di branch `sinkronisasi-rancangan`.
Lingkup: hanya G1–G6 sesuai `INSTRUKSI_PERBAIKAN_Rekening_Penyedia_dan_Bug_UI.md`. Tidak ada dependency baru.

Konfirmasi batas: `git diff 1867a81..HEAD -- app/Services/DisbursementService.php app/Services/InvoiceIssuer.php app/Http/Controllers/Admin/PaymentController.php app/Http/Controllers/Admin/OrderReviewController.php app/Services/CancellationService.php app/Services/RefundCalculator.php app/Services/EventCompletionService.php` → **0 berkas berubah**. `Disbursement::verifiedBankAccount()` juga tidak diubah.

---

## 1. Daftar commit G1–G6 dan berkas yang berubah

| Commit | Pesan | Berkas utama |
|---|---|---|
| `f3d3bb6` | `G0: simpan pekerjaan Tahap G sebelumnya (kartu Estimasi Biaya acara)` | `Klien\EventController`, `ClientCostEstimator`, `klien/acara-detail.blade.php`, dokumen, `tests/Feature/Klien/EventCostCardTest.php` |
| `6907b63` | `G1: rekening pencairan penyedia (isi, verifikasi Admin, notifikasi, seeder)` | migration `2026_11_21_000004`, `Models\ProviderBankAccount`, `Enums\VerificationStatus`, `Provider\{BankAccountController,ProfilePageController}`, `Requests\Provider\StoreProviderBankAccountRequest`, `Admin\ProviderBankAccountController`, 2 notification, `routes/web.php`, `partials/rekening-pencairan-{card,banner}.blade.php`, `vendor|venue/profil`, `vendor|venue/pencairan`, `vendor|venue/index`, `admin/{mitra-detail,pencairan,admin-penyedia,pengguna,index}.blade.php`, `NotificationController`, `LocalDemoSeeder`, factory, `tests/Feature/RekeningPenyedia/ProviderBankAccountTest.php` (34 berkas, +1422/−25) |
| `a19fd9e` | `G2: tab status Admin Acara memakai subnav` | `Admin\EventController`, `admin/acara.blade.php`, `admin.css`, `tests/Feature/Portal/AdminUiLayoutTest.php` |
| `c64e344` | `G3: padding kartu rekening perusahaan` | `admin/rekening.blade.php`, `admin/rekening-form.blade.php`, `admin.css`, tes |
| `d29817e` | `G4: perbaikan tabel Pencairan dan Pembatalan Admin` | `admin/pencairan.blade.php`, `admin/pembatalan.blade.php`, `admin/katalog.blade.php`, `admin/admin-penyedia.blade.php`, `admin.css`, tes |
| `018a221` | `G5: tes empty state empat portal (audit fitur)` | `tests/Feature/Audit/EmptyStatePagesTest.php`, `docs/BACKEND_BUSINESS_RULES.md`, `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`, `README.md` |
| (G6) | `G6: dokumen, kebersihan, dan hasil verifikasi` | `docs/LAPORAN_PERBAIKAN_TAHAP_G.md` |

Catatan jujur: commit `f3d3bb6` (G0) menampung perubahan **yang sudah ada di dalam ZIP** (kartu Estimasi Biaya acara) dan belum ter-commit, agar commit G1–G6 bersih. Dokumen §8/§I ikut masuk commit G5, bukan G6.

## 2. Hasil `php artisan test --compact` dan `vendor/bin/pint --test`

- `php artisan test --compact`: **555 tests, 3760 assertions, 0 failures** (durasi 168,11 s).
- `vendor/bin/pint --test`: **PASS (322 files)**.
- Baseline ZIP sebelum perubahan: 530 tests / 3548 assertions (semua lulus). Ditambahkan 25 tes baru (G1: 20, G2–G4: 4, G5: 1) sehingga total 555 tes / 3760 assertions.

## 3. Kelas CSS yatim hasil audit G2

Cakupan audit: seluruh `class="…"` pada `resources/views/admin/**` dan `layouts/admin.blade.php` (209 kelas unik) dibandingkan dengan selektor di `design-tokens.css`, `admin.css`, `admin-pages.css`, `keselarasan.css`, `password-toggle.css`.

**Diperbaiki (berdampak visual):**

| Kelas | Dampak sebelum | Perbaikan |
|---|---|---|
| `.pill` (+ `.pill-row`, `.pill-bar`) | tab status tanpa jarak | diganti komponen `<x-subnav>` (`.tabs`) |
| `.alert.warn` | peringatan tampil biru-ungu seperti info | ditambahkan (palet amber, sama dengan `penyedia.css`) |
| `.alert-success`, `.alert-danger` | kotak sukses/galat tanpa warna | ditambahkan (palet hijau/merah yang dipakai inline sebelumnya) |
| `.cols2` | `grid cols2` jadi satu kolom | ditambahkan + media ≤1100 px |
| `.link-plain` | tautan tabel bergaris bawah biru | ditambahkan |
| `.help` | angka "Rp …" pada Beranda Admin ikut tebal | ditambahkan |
| `.i-check` | ikon sukses kosong | mask SVG ditambahkan |
| `.form-actions`, `.form-actions .spacer` | tombol menumpuk (hanya ada di media ≤700 px) | basis ditambahkan |
| `.mb-8`, `.mb-16`, `.mt-4`, `.mt-24`, `.p-16`, `.pt-16`, `.ml-8`, `.text-center`, `.text-sm`, `.d-block`, `.border`, `.border-top`, `.rounded` | kelas utilitas tanpa definisi | ditambahkan |

**Dilaporkan, tidak diperbaiki (tidak merusak tampilan):**
- `.refined-card`, `.proof-comparison-grid`, `.proof-claim-col`, `.proof-preview-col` (`admin/pembayaran-detail`) — sudah ditopang `class="card …"` dan gaya inline; tidak ada efek visual.
- `.table-note` (`admin/aktivitas`) — elemen memakai `.note-box` yang sudah bergaya.
- `.alert-success`/`.alert-danger` pada view lain tetap memakai inline style lama; dibiarkan agar tidak melebar ke refactor.
- `.auth-*` pada `admin/login.blade.php` — **bukan** yatim: halaman memakai `layouts.guest` + `auth.css`.

## 4. Hasil tes crawl G5 dan temuan

- Tes crawl F3 (`tests/Feature/Audit/PortalLinkCrawlTest.php`) dijalankan: beranda tiap peran → kedalaman 2, batas 150 URL/peran. URL dikunjungi: **klien 24, vendor 30, venue 18, admin 67** — tidak ada 404/500, tidak ada tautan mati.
- Route tulis: **73** (termasuk `_boost/browser-logs` internal). Semua memiliki FormRequest/`validate()` atau merupakan aksi status terkontrol, dan menampilkan sukses/galat pada halaman.
- Empty state: tes baru `EmptyStatePagesTest` menutup **42 halaman** pada 4 peran (penyedia tanpa rekening, tanpa foto, tanpa pesanan, tanpa katalog/venue; Klien tanpa acara; Admin pada basis data kosong) — semuanya 200.
- Beban gaya inline (dicatat, menunggu keputusan pemilik): `klien/acara-detail` **57**, `admin/pembayaran-detail` **48**, `klien/tagihan` **14**.
- Tidak ada `href="#"` atau teks "prototipe/BE-x" yang tersisa; tombol "Konfirmasi Valid (Terkunci)" tetap bersyarat `@else`.

## 5. Verifikasi browser

**Tidak ada verifikasi browser.** Lingkungan kerja ini tidak menyediakan browser interaktif, sehingga daftar uji manual pada instruksi G6 **belum dijalankan** dan diserahkan kepada pemilik proyek. Klaim "UI terverifikasi" tidak dibuat berdasarkan tes HTTP.

Yang hanya dapat dibuktikan lewat tes HTTP/markup: tab `admin/acara` memuat `class="tabs card subnav"` tanpa `class="pill"` dan menandai tab aktif `aria-current="page"`; `<main>` pada `admin/rekening` memuat `page-bank-accounts`; tabel Pencairan/Pembatalan tidak memuat `<small>` tanpa kelas; halaman Pencairan Admin menampilkan tautan "Verifikasi rekening" dan tombol nonaktif/aktif sesuai kondisi rekening.

## 6. Pertanyaan tersisa dan kandidat refactor

1. **Database lokal yang sudah berjalan**: jalankan `php artisan db:seed --class=LocalDemoSeeder` (idempoten) atau isi rekening lewat UI. Seeder kini memberi Dapur Nusantara (`BCA 1234567890`) dan Graha Melati (`Bank Mandiri 0987654321`) satu rekening `terverifikasi` (nomor fiktif).
2. **Contoh antrean rekening `menunggu` di seeder**: instruksi meminta rekening `menunggu` "bila ada penyedia demo lain". Seeder hanya membuat dua penyedia demo (keduanya sudah `terverifikasi`), jadi tidak ada baris `menunggu` tambahan agar tidak menambah akun demo baru.
3. **Kandidat refactor (menunggu persetujuan)**: refactor gaya inline pada tiga berkas di §4; penyatuan komponen `alert`/`form-actions` yang masih dobel antara `klien.css`, `penyedia.css`, dan `admin.css`; `.t` pada topbar dan `.timeline small` pada halaman verifikasi masih memakai selektor turunan, bukan kelas eksplisit.
4. **Interpretasi yang diambil**: (a) tab "Evaluasi" pada Admin → Acara menghitung `selesai + dievaluasi`; (b) filter `?rekening=` juga menerima `terverifikasi`, `ditolak`, dan `belum` selain `menunggu`; (c) nomor rekening pada kartu Profil ditampilkan penuh untuk baris `menunggu` maupun `terverifikasi` karena pemilik berhak melihat nomornya sendiri.

## 7. Alur pencairan setelah perbaikan

Pesanan → validasi Admin → acara selesai → penyedia mengisi rekening → Admin memverifikasi → **Catat transfer** aktif → pencairan `cair` dengan bukti. Tes integrasi `test_alur_uang_rekening_disetujui_lewat_route_lalu_transfer_dicatat` menjalankan alur ini dari "rekening belum ada" sampai `DisbursementService::recordTransfer` berhasil, tanpa mengisi `verification_status` lewat factory.
