# Laporan Perbaikan Pasca-Review Tahap C dan D (Tahap F)

Basis: ZIP Tahap D (`pesta-kita-tahap-d.zip`, 903 berkas) di branch `sinkronisasi-rancangan`.
Lingkup: hanya F1–F5. Tidak ada fitur baru, dependency baru, atau perubahan pada tagihan, pembayaran, refund, kompensasi, dan pencairan.

Konfirmasi batas: `git diff 4072f2d..HEAD` tidak menyentuh satu baris pun pada `InvoiceIssuer`, `Admin\PaymentController`, `Admin\OrderReviewController`, `CancellationService`, `RefundCalculator`, `DisbursementService`, dan `EventCompletionService` (0 berkas berubah).

---

## 1. Hasil pemeriksaan wajib

### 1.1 F1.1 — Jalur lain yang mengubah visibilitas media

Seluruh penulisan `media.visibility` di aplikasi:

| Lokasi | Perilaku |
| --- | --- |
| `MediaController::storePhoto` | Saat unggah: `publik` bila profil penyedia `terverifikasi`, selain itu `privat`. |
| `MediaController::storeRoomLayout` (denah) | Sama: `publik` bila profil `terverifikasi`, selain itu `privat`. |
| `MediaController::storeDocument` | Selalu `privat` (dokumen verifikasi). |
| `Admin\ProviderVerificationController::approve` | `foto` privat → publik; sejak F1 juga `denah` privat → publik. |
| `Klien\InvoiceController` (bukti bayar), `CancellationService` (bukti refund), `DisbursementService` (bukti pencairan) | Selalu `privat`. |
| `Console\Commands\VerifyManualFlowCommand` | Alat dev/manual, bukan jalur produksi. |

Jalur **penolakan / penonaktifan / pencabutan**:

- `ProviderVerificationController::reject` hanya mengubah status profil menjadi `ditolak`; tidak menyentuh media.
- `Admin\VenueController::update` hanya mengubah `cleaning_fee` dan status venue (`terverifikasi`/`nonaktif`); tidak menyentuh media.
- `VerificationController::submit` hanya dapat mengajukan ulang dari `menunggu`/`ditolak`; tidak ada jalur `terverifikasi` → status lain.
- Tidak ada route/aksi pencabutan verifikasi.

**Kesimpulan:** tidak ada jalur yang mengembalikan foto ke `privat` setelah publik. Karena itu denah **tidak** diberi perilaku pengembalian baru; denah hanya diperlakukan sama pada jalur persetujuan. Tes `test_penolakan_verifikasi_tidak_mengubah_visibilitas_foto_maupun_denah` mendokumentasikan bahwa penolakan tidak mengubah visibilitas foto maupun denah (keduanya tetap privat).

### 1.2 F2.1 — Rumus yang disalin

- **Komisi** (`app/Http/Controllers/Admin/OrderReviewController.php:196-199`):
  - `$commissionPercent = (int) Setting::get('commission_percent', 10);`
  - `$lineTotal = (int) ($providerPrice * max(1, (int) $lockedItem->quantity));`
  - `$commission = intdiv($lineTotal * $commissionPercent + 50, 100);` (pembulatan setengah ke atas, bilangan bulat).
- **Biaya kebersihan** (`app/Services/InvoiceIssuer.php:205-227`):
  - `subtotalProvider = Σ lineTotal()`; `totalCommission = Σ commission`.
  - Kebersihan diambil **sekali per acara** dari venue milik item `venue_room` pertama: `cleaning_fee` bila `> 0`, selain itu 0.
  - Tidak dikalikan jumlah/hari dan **tidak** dikenai komisi (`grandTotal = subtotal + komisi + kebersihan`).
  - Kode unik ditambahkan setelahnya (`transfer_total = grand_total + unique_code`) sehingga **tidak** termasuk estimasi.

`App\Services\ClientCostEstimator` menyalin rumus di atas persis dan hanya membaca `settings`; `OrderReviewController` dan `InvoiceIssuer` tidak diubah agar memakainya.

---

## 2. Commit F1–F5 dan berkas yang berubah

| Commit | Pesan | Berkas |
| --- | --- | --- |
| `b5ebb38` | `F1: denah ikut publik saat verifikasi disetujui + migrasi data` | `app/Http/Controllers/Admin/ProviderVerificationController.php`, `database/migrations/2026_11_21_000003_publish_verified_venue_room_layouts.php`, `tests/Feature/Denah/RoomLayoutTest.php`, `tests/Feature/Denah/RoomLayoutDataMigrationTest.php` |
| `fdeb0be` | `F2: rekomendasi memakai estimasi biaya total (komisi + kebersihan)` | `app/Services/ClientCostEstimator.php`, `app/Services/PackageRecommender.php`, `resources/views/klien/acara-detail.blade.php`, `tests/Feature/Rekomendasi/PackageRecommenderTest.php`, `tests/Feature/Rekomendasi/ClientCostEstimateTest.php` |
| `e8d2e13` | `F3: tes crawl tautan internal per peran` | `tests/Feature/Audit/PortalLinkCrawlTest.php` |
| `9fafb26` | `F4: kebersihan paket, koreksi laporan, dan dokumen` | `.gitignore`, `README.md`, `docs/BACKEND_BUSINESS_RULES.md`, `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`, `docs/LAPORAN_TAHAP_D.md` |
| — | F5: tidak ada commit perbaikan | Tidak ada temuan browser yang sah untuk diperbaiki (lihat bagian 5). |

Total `4072f2d..HEAD`: 16 berkas, +1.424 / −33 baris (termasuk laporan ini).

---

## 3. Hasil `php artisan test --compact` dan `vendor/bin/pint --test`

- `php artisan test --compact`: **524 tes lulus, 3.520 assertion** (159,41 detik).
- `vendor/bin/pint --test`: **bersih, 311 berkas** (tanpa perubahan gaya).
- Baseline sebelum perbaikan: 510 tes, 3.285 assertion (ZIP Tahap D).

**Penjelasan selisih hitungan 485 vs 510 (F4.4):** ZIP Tahap D memuat **485** fungsi uji statis (`public function test_*`; tidak ada atribut `#[Test]`). Hasil run **510** karena tiga `#[DataProvider]` memperluas tiga metode menjadi 28 kasus uji:

- `RefundCalculatorTest::boundaryProvider` → 9 kasus,
- `RefundCalculatorTest::referenceProvider` → 5 kasus,
- `AdminSettingsTest::invalidPayloads` → 14 kasus.

Perhitungan: `485 − 3 + 28 = 510`. Setelah Tahap F fungsi uji statis menjadi **499** (F1 +5, F2 +8, F3 +1), sehingga `499 − 3 + 28 = 524` — sama dengan hasil run di atas.

---

## 4. Hasil tes crawl (F3)

Titik awal: beranda peran **dan** halaman indeks/tab milik peran (kedalaman 0), tautan diikuti sampai **kedalaman 2**, batas 150 URL per peran. DOM diurai dengan `DOMDocument` (bukan regex).

| Peran | URL unik dikunjungi |
| --- | --- |
| Klien | 24 |
| Vendor | 30 |
| Venue | 18 |
| Admin | 66 |
| **Total** | **138** |

- **Tautan mati ditemukan: 0.** Tidak ada respons 404/500; seluruh redirect berakhir pada 200. Karena itu tidak ada tautan yang diperbaiki.
- Pengecualian eksplisit dan bernama: `#jangkar`, `mailto:`, `tel:`, `javascript:`, `wa.me`/`api.whatsapp.com`, tautan eksternal (host berbeda), `logout`, serta unduhan berkas/media (`/media/`, `/cetak`, `/nota`, `/unduh`, `/download`).
- Fixture per peran: acara draft berisi item; acara diajukan (pesanan menunggu review); acara terkonfirmasi lunas dengan rundown dan pencairan belum cair; acara dievaluasi dengan evaluasi; pembatalan aktif; pembatalan selesai dengan kompensasi cair; notifikasi keempat peran; venue dengan denah.

---

## 5. Verifikasi browser (F5)

**Tidak ada verifikasi browser yang sah untuk build ini.** Alat browser tersedia di lingkungan, tetapi instance aplikasi yang dijangkau browser **bukan** build ini:

- Katalog Klien yang dibuka browser menampilkan venue **"Pentagon"** (Rp 5.000.000.000) yang tidak ada di database build ini, dan **tidak** menampilkan "Pendopo Sekar" yang dibuat untuk skenario uji.
- Antrean verifikasi Admin di browser menyatakan `Tidak ada pengajuan verifikasi yang menunggu.`, padahal database build ini berisi profil venue `menunggu`.
- Detail venue "Graha Melati" di browser menampilkan `Belum ada denah untuk ruangan ini.`, padahal build ini memiliki denah pada ruangan tersebut.
- Pembanding langsung: permintaan HTTP terautentikasi ke server build ini (`127.0.0.1:8000`) hanya menampilkan satu venue tayang, "Graha Melati".

Konsekuensinya, pengamatan browser di atas **tidak dipakai sebagai bukti** apa pun, tidak ada klaim "UI terverifikasi", dan tidak ada perbaikan kode yang didasarkan padanya. Data QA sementara yang sempat dibuat untuk skenario sudah dibersihkan (`php artisan migrate:fresh --seed`).

Daftar uji manual (10 butir, dijalankan dengan `LocalDemoSeeder`) diserahkan kepada pemilik proyek:

1. **Venue:** buat venue + ruangan, unggah 1 denah **sebelum** diverifikasi → denah tampil di form venue.
2. **Admin:** setujui verifikasi penyedia itu. **Klien:** buka detail venue → denah tampil (bug F1). Unggah denah ke-4 → ditolak dengan pesan.
3. **Klien:** buat acara dengan anggaran, tamu, tanggal → buka Rekomendasi → angka "Estimasi biaya" tampil dengan rincian komisi/kebersihan; total ringkasan ≤ anggaran; tambah satu kandidat ke acara; bandingkan angka dengan tagihan setelah Admin menyetujui (harus sama, di luar kode unik).
4. **Klien:** pilih susunan ruangan; coba nilai tidak valid; ajukan acara.
5. **Admin → Klien:** setujui pesanan, bayar, validasi → `terkonfirmasi`.
6. **Klien dan Vendor:** susun rundown (uji jam tidak valid, `end_time` ≤ `start_time`); **Venue dan Admin:** hanya lihat, tanpa tombol tulis; venue melihat susunan ruangan tanpa nama klien.
7. **Admin:** tandai selesai (ubah `event_date` data uji ke kemarin) → rundown terkunci di semua portal; pencairan hak pesanan muncul; catat transfer.
8. **Klien:** isi evaluasi (uji kirim tanpa salah satu bintang; uji akses keyboard pada bintang); kirim → `dievaluasi`; tombol kirim hilang. **Vendor/Venue:** rating dan komentar tampil tanpa nama klien; kartu dashboard benar. **Admin:** kartu Rating Admin dan tab Evaluasi benar.
9. **Jalur batal:** acara lain → ajukan pembatalan → proses → refund selesai → kompensasi tampil di pencairan penyedia.
10. Periksa lonceng, tombol WhatsApp, dan tampilan di lebar layar ponsel (±375 px) pada halaman rekomendasi, form evaluasi, dan rundown.

---

## 6. Kebersihan paket dan dokumen (F4)

### 6.1 `.trash/views/`

- Folder **ada** di repositori: `.trash/views/{klien-rundown,klien-evaluasi,klien-rekomendasi}.blade.php` (13 KB, terlacak git), tetapi **tidak** ada di ZIP Tahap D — pernyataan laporan D dikoreksi (lihat `docs/LAPORAN_TAHAP_D.md` bagian D3b).
- `/.trash` ditambahkan ke `.gitignore` dan folder **tidak** disertakan ke bundle.
- Tidak ada berkas yang dihapus. Ketiga view masih terlacak git; `git rm --cached` menunggu keputusan pemilik proyek.

### 6.2 Isi bundle

- `.phpunit.result.cache` **ada** di ZIP Tahap D; bundle F tidak memuatnya (diverifikasi ulang setelah paket dibuat).
- `git ls-files .claude` → **35 berkas terlacak**, sehingga `.claude/` disertakan sesuai instruksi.
- Bundle F: **789 berkas**, 4,7 MB (seluruh berkas terlacak git kecuali daftar pengecualian eksplisit). Tanpa `.git/`, `.trash/`, dependensi `vendor/` (Composer), `node_modules/`, `.env`, `bootstrap/cache/*.php`, `storage/framework`, `storage/logs`, `public/build`, dan `database/database.sqlite`.
- **Koreksi paket:** paket pertama yang diserahkan (770 berkas) keliru mengeluarkan **semua** folder bernama `vendor`, termasuk `resources/views/vendor/` (15 view Portal Vendor), sehingga portal Vendor tidak lengkap. Paket dibangun ulang dari `git ls-files`; verifikasi: 789/789 berkas terlacak ada, `resources/views/vendor/` = 15 berkas, tanpa direktori dependensi `vendor/` dan tanpa `.trash/`.

### 6.3 Kandidat yang tidak dihapus (menunggu keputusan)

| Kandidat | Ukuran | Rujukan `grep` |
| --- | --- | --- |
| `scripts/convert_prototypes.php`, `update_admin_views.php`, `update_project_views.php`, `verify_admin.php` | 21 KB | Hanya dirujuk `docs/LAPORAN_TAHAP_D.md`; tidak dipakai `app/`, `routes/`, `tests/`, `composer.json`. |
| `_prototypes/` | 1,3 MB | Hanya dirujuk `docs/INSTRUKSI_*`. Tidak ada view yang memuatnya. |
| `_updates/` | 1,2 MB | Sama: hanya dokumen instruksi. |
| `docs/frontend-audit-baseline/` | 757 KB | Hanya dokumen audit. |
| `docs/frontend-audit-baseline.md`, `docs/frontend-audit-stage1.md` | 24 KB + 8 KB | Hanya dokumen audit. |
| `docs/reports/` | 437 KB | Dokumen laporan historis. |

### 6.4 Dokumen yang diperbarui

- `docs/BACKEND_BUSINESS_RULES.md`: bagian **H** (estimasi biaya rekomendasi; denah ikut publik saat verifikasi).
- `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`: bagian 5.1 disinkronkan ke estimasi; bagian **6** (Tahap F).
- `README.md`: status modul rekomendasi dan denah.
- `docs/LAPORAN_TAHAP_D.md`: koreksi pernyataan `.trash/views/`.

---

## 7. Pertanyaan tersisa dan kandidat penghapusan

1. **Kandidat penghapusan** pada 6.3: arsipkan, hapus, atau pertahankan? Belum ada yang dihapus.
2. **`.trash/views/`**: masih terlacak git. Setuju `git rm --cached` (berkas tetap ada di disk) atau tetap dilacak?
3. **Estimasi kebersihan multi-venue:** `InvoiceIssuer` menagih satu kebersihan (venue pertama). Rekomendasi menghitung kebersihan per kandidat venue; konsisten dengan aturan B6 (maksimal satu venue per acara). Mohon konfirmasi bila aturan ini berubah.
4. **Verifikasi browser:** perlu instance build ini diekspos agar daftar uji 10 butir dapat dijalankan? Tanpa itu, tidak ada verifikasi visual yang bisa diklaim.
5. **Opsi B F2** tidak dipakai; implementasi mengikuti opsi A (estimasi total) sesuai keputusan bawaan.
