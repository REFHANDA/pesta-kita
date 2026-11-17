# AI Agent Context — Pesta Kita

> **Dokumen konteks utama untuk seluruh AI Agent yang mengerjakan repository Pesta Kita melalui TRAE atau editor lain.**
>
> Baca dokumen ini sebelum melakukan pekerjaan. Verifikasi semua informasi terhadap repository aktif karena kode terbaru adalah sumber kebenaran untuk kondisi implementasi.

---

## 1. Tujuan Dokumen

Dokumen ini menjaga kesinambungan konteks proyek ketika:

- berpindah ke chat baru;
- berpindah model AI atau sesi TRAE;
- pekerjaan dilanjutkan oleh Agent lain;
- token percakapan sebelumnya tidak lagi tersedia.

`docs/AI_AGENT_CONTEXT.md` harus terus diperbarui ketika ada perubahan yang memengaruhi konteks proyek. Pembaruan dokumentasi merupakan bagian dari penyelesaian tugas, bukan pekerjaan opsional.

## 2. Ringkasan Proyek

Nama proyek: **Pesta Kita**  
Jenis sistem: **Event Management System (EMS)** dengan model managed marketplace.

Portal yang diketahui:

1. Admin
2. Klien
3. Vendor
4. Pemilik Venue

Teknologi yang pernah diverifikasi pada audit sebelumnya:

- Laravel 13.40.0
- PHP 8.4.4
- Laravel Blade
- CSS statis
- JavaScript vanilla

Aset portal sebelumnya ditemukan di `public/assets`. **Semua versi, lokasi folder, struktur, route, dan teknologi di atas adalah informasi historis dan wajib diverifikasi kembali sebelum digunakan.** Jangan menganggap repository saat ini pasti sama.

## 3. Sumber Kebenaran dan Prioritas

Gunakan urutan berikut:

1. **Repository aktif** untuk mengetahui kondisi kode, struktur, route, komponen, dan implementasi terkini.
2. **Aturan bisnis pada dokumen ini** untuk menjaga keputusan bisnis yang telah ditetapkan.
3. **Roadmap** `docs/roadmap_pembaruan_frontend_pesta_kita.md` untuk lingkup dan urutan tahap.
4. **Dokumen audit dan laporan tahap** untuk konteks historis, temuan, dan alasan keputusan.
5. **Dokumen backend** untuk kondisi server dan rancangan yang belum diterapkan: `docs/BACKEND_AGENT_CONTEXT.md`, `docs/BACKEND_BUSINESS_RULES.md`, `docs/BACKEND_DATABASE_DESIGN.md`, `docs/BACKEND_API_CONTRACT.md`, dan `docs/BACKEND_IMPLEMENTATION_PLAN.md`. Rancangan itu bukan kode yang sudah ada. Kondisi backend yang berlaku tetap hasil pemeriksaan repository.

Jika dokumentasi bertentangan dengan repository:

- jangan menebak;
- periksa kode dan riwayat perubahan yang tersedia;
- tentukan apakah dokumentasi tertinggal atau implementasi berubah;
- catat perbedaannya;
- perbarui dokumentasi setelah fakta terverifikasi.

Jika roadmap atau dokumen penting tidak tersedia, jangan mengarang isi atau kriteria penerimaannya. Laporkan dokumen/bagian yang diperlukan.

## 4. Aturan Bisnis yang Tidak Boleh Diubah Tanpa Izin

Pesta Kita merupakan **managed marketplace**, bukan open marketplace.

Aturan yang sudah ditetapkan:

- Admin menyetujui pesanan pada MVP.
- Informasi kontak pribadi Vendor dan Pemilik Venue tidak ditampilkan kepada Klien.
- Klien membayar satu invoice gabungan untuk satu event.
- Pembayaran MVP dilakukan melalui transfer bank manual ke rekening perusahaan.
- Komisi 10% ditambahkan ke tagihan Klien, bukan dipotong dari harga penyedia.
- Pembayaran kepada penyedia dilakukan oleh Admin setelah event selesai.
- Ulasan hanya dapat diberikan setelah event selesai dan transaksi memenuhi syarat.
- Backend adalah sumber kebenaran untuk nominal akhir, komisi, kode transfer unik, refund, payout, dan status transaksi.
- Frontend tidak boleh menghitung atau menetapkan sendiri nilai akhir refund maupun aturan bisnis lain.

Jangan mengubah, memperluas, atau menafsirkan ulang aturan tersebut tanpa instruksi eksplisit pengguna.

## 5. Batasan Teknis Wajib

### 5.1 Teknologi dan arsitektur

- Pertahankan Laravel Blade, CSS, dan JavaScript vanilla selama pengguna tidak menyetujui perubahan.
- Jangan menambahkan React, Vue, Alpine, framework, library, atau dependency baru tanpa izin.
- Jangan mengubah backend, database/migration, route, controller, model, autentikasi, API, atau `.env` dalam pekerjaan frontend kecuali diminta secara eksplisit.
- Jangan menganggap API atau endpoint tersedia sebelum ditemukan dan diverifikasi di repository.

### 5.2 Keamanan perubahan repository

- Sebelum mengedit, jalankan `git status --short` dan periksa diff yang relevan.
- Pertahankan seluruh perubahan lokal yang sudah ada, termasuk perubahan yang bukan dibuat oleh Agent saat ini.
- Jangan menggunakan perintah destruktif seperti `git reset`, `git clean`, forced checkout, atau perintah lain yang berpotensi menghapus perubahan.
- Jangan menimpa file secara massal.
- Hindari refactor besar dan migrasi massal view tanpa kebutuhan dan persetujuan.
- Cari penggunaan selector CSS sebelum menghapus atau mengganti selector lama.

### 5.3 Kejujuran implementasi

- Jangan membuat aksi demo terlihat seperti penyimpanan atau transaksi server sungguhan.
- Jangan menampilkan notifikasi “berhasil disimpan”, “terkirim”, “diproses”, atau status sukses palsu jika backend tidak benar-benar melakukan tindakan tersebut.
- Bedakan UI statis, simulasi lokal, dan fitur yang terintegrasi dengan backend.
- Jangan mengarang data, API, route, selector, field, enum, status, atau hasil pengujian.
- Jangan menyatakan WCAG-compliant, aman, terintegrasi, atau production-ready tanpa bukti pengujian yang sesuai.

### 5.4 Aksesibilitas dan responsivitas

Pertahankan perbaikan aksesibilitas dari Tahap 1 dan responsivitas/UI states dari Tahap 3.

- Jangan merusak navigasi keyboard, focus management, Escape handling, focus return, accessible names, atau target sentuh yang telah diperbaiki.
- Pertahankan perilaku responsif dan pencegahan overflow yang sudah diterapkan.
- Jangan menghapus perbaikan yang sudah ada hanya demi menyederhanakan kode.

### 5.5 Screenshot

Pengguna tidak menginginkan pengambilan screenshot atau penggunaan perintah screenshot. **Jangan membuat, meminta, atau menjalankan screenshot.**
Gunakan pemeriksaan kode, test, DOM/browser inspection bila tersedia, dan laporan tekstual.

## 6. Status Roadmap dan Konteks Historis

Status berikut berasal dari laporan pekerjaan sebelumnya. Verifikasi repository dan dokumen laporan sebelum menganggap implementasi saat ini tidak berubah.

| Tahap | Nama                                           | Status historis                                                                                                 | Catatan                                                                                                                            |
| ----- | ---------------------------------------------- | --------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| 0     | Audit dan persiapan                            | Selesai                                                                                                         | Baseline audit; temuan harus diverifikasi bila relevan                                                                             |
| 1     | UI/UX blockers dan accessibility               | Selesai                                                                                                         | Perbaikan aksesibilitas pada empat portal                                                                                          |
| 2     | Design system dan reusable components          | Selesai bersyarat                                                                                               | Token utama dan dokumentasi design system; belum semua view dimigrasikan                                                           |
| 3     | Responsiveness dan UI states                   | Selesai bersyarat                                                                                               | Perbaikan responsif dan penghapusan feedback demo palsu                                                                            |
| 4     | Penyempurnaan portal per role                  | Selesai bersyarat                                                                                               | Navigasi (4 layout), 4 dashboard, halaman inti tiap role; rincian pada 6.5 dan `docs/reports/`                                     |
| 5     | Katalog, profil, availability                  | Selesai bersyarat — 5.1–5.5 selesai bersyarat; **5.6 evaluasi selesai (2026-11-15), hasil: Diterima Bersyarat** | Lihat 6.6–6.11, dan `docs/reports/`; penomoran final diselaraskan 2026-11-15; jangan lanjut ke Tahap 6 sebelum instruksi           |
| 6     | Transaksi, invoice, pembayaran, refund, payout | **Selesai bersyarat (2026-11-15)** — frontend selesai; backend transaksi/keuangan belum tersedia                | Lihat 6.12 dan `docs/reports/tahap-6-transaksi-tagihan-pembayaran-refund-pencairan.md`; jangan lanjut ke Tahap 7 sebelum instruksi |
| 7     | Koordinasi event dan evaluasi                  | Belum dikerjakan menurut catatan                                                                                | Jangan dikerjakan sebelum instruksi                                                                                                |
| 8     | Fitur lanjutan dan optimasi                    | Belum dikerjakan menurut catatan                                                                                | Jangan dikerjakan sebelum instruksi                                                                                                |

### 6.1 Temuan historis Tahap 0

- Audit awal mencatat 96 view portal: Admin 38, Klien 28, Vendor 17, Venue 13.
- Pada saat audit, banyak halaman/aksi masih statis atau demo.
- Pada saat audit, belum ditemukan domain API untuk workflow operasional inti.
- Ditemukan risiko route portal tanpa perlindungan role/auth yang memadai.
- Pengujian historis: `php artisan test` dan `php artisan view:cache` pernah lulus; empat dashboard HTTP pernah diperiksa.
- Vite build, status migration, dan Pint pernah terblokir/gagal pada lingkungan saat itu.
- Semua temuan ini **bukan hasil pemeriksaan terkini**. Jangan menyatakan kondisi sekarang sama tanpa verifikasi.

### 6.2 Hasil historis Tahap 1

- Perbaikan aksesibilitas drawer: keyboard, Escape, focus trap, dan focus return.
- Perbaikan fokus dialog, accessible names, target sentuh 44px, dan navigasi keyboard.
- Audit historis mencatat 90 halaman diperiksa untuk accessible names dan 210 assertions pada empat test lulus.
- Audit screen reader belum dilakukan dan audit contrast belum menyeluruh.
- Jangan mengklaim audit aksesibilitas menyeluruh atau kepatuhan WCAG.

### 6.3 Hasil historis Tahap 2

- `design-tokens.css` menjadi sumber token utama.
- Alias lama didefinisikan satu kali di `admin.css`.
- `design-system.md` menjelaskan variant dan contoh markup Blade aktual untuk tombol, input, badge, card, tabel, dialog, dan notifikasi.
- Token digunakan pada layout/CSS Admin, Guest/public, Klien, Vendor, dan Venue.
- Tidak semua view telah dimigrasikan dan tidak ada klaim bahwa seluruh komponen telah dibuat ulang.
- Test historis: `php artisan test --compact` lulus (4 tests, 210 assertions), `php artisan view:cache`, dan `git diff --check` lulus.
- Pemeriksaan Pint untuk Blade terblokir karena dependency Prettier tidak tersedia; dependency tidak dipasang.
- Pemeriksaan contrast belum mencakup audit WCAG penuh.

### 6.4 Hasil historis Tahap 3

- Pemeriksaan responsif historis pada viewport 1440, 768, dan 375 px.
- Form katalog menjadi satu kolom pada mobile; dialog tetap di viewport.
- Area tabel penyedia dibuat dapat di-scroll horizontal dan dapat difokuskan.
- Clipping pagination payout pada mobile diperbaiki dan dapat difokuskan dengan keyboard.
- Overflow dokumen diperiksa dan tidak ditemukan pada halaman yang diuji saat itu.
- Feedback sukses palsu dan indikator “Processing…” pada form demo dihapus.
- `aksi.js` dan `app-ui.js` memberi penjelasan bahwa aksi preview tidak mengirim atau menyimpan data; simulasi tetap lokal.
- Test historis: `php artisan test --compact` lulus (4 tests, 210 assertions), `php artisan view:cache`, `node --check`, dan `git diff --check` lulus.
- Loading, empty, dan error state yang benar-benar server-driven belum dibuat karena domain API belum tersedia pada saat itu.
- Tidak ada screenshot untuk pekerjaan Tahap 3.

### 6.5 Hasil Tahap 4 — Penyempurnaan Portal per Role (2026-11-15)

Lingkup yang disetujui pengguna: **navigasi (4 layout sidebar) + 4 dashboard + 1 halaman inti per role** (Admin = Tinjau pesanan, Klien = Detail acara, Vendor = Detail pesanan, Venue = Daftar pesanan venue). Badge angka pada sidebar dibiarkan apa adanya sesuai keputusan pengguna (bagian data prototipe, di luar lingkup).

Yang dikerjakan:

- **Navigasi:** pola `.role-chip` diseragamkan menjadi satu aturan berbasis token di `keselarasan.css` (`--pk-color-brand-soft`/`--pk-color-brand`) dan dipakai keempat layout sidebar (Admin, Klien, Vendor, Venue). Sebelumnya Vendor/Venue memakai inline style hex.
- **Verifikasi lintas role:** pencarian tautan lintas portal pada seluruh view tiap portal tidak menemukan pencampuran (hasil "No matches") — kriteria 4.7 "Navigasi empat role tidak saling tercampur" terpenuhi.
- **Dashboard:** ditambahkan aksi utama (tombol `.btn`) pada `page-head` dashboard Admin (`Tinjau pesanan`), Vendor (`Lihat pesanan`), dan Venue (`Lihat pesanan venue`) agar tujuan/tindakan berikutnya jelas.
- **Halaman inti:**
    - `admin/pesanan.blade.php` — ditambahkan alert agar Admin melihat objek, nilai, dan konsekuensi sebelum menyetujui/menolak (roadmap 4.5/4.6).
    - `venue/pesanan.blade.php` — defek pencampuran role diperbaiki: baris item katering (lingkup Vendor) diganti ruangan venue, header kolom `Pesanan` menjadi `Ruangan`, dan judul diperbaiki menjadi "Panel Pemilik Venue Pesta Kita".
    - `vendor/pesanan-detail.blade.php` — ditambahkan kartu "Status pesanan" memakai komponen `.timeline` yang sudah ada, agar konsisten dengan halaman paralel `venue/pesanan-detail` (roadmap 4.3 butir 4: timeline pesanan dan riwayat keputusan Admin).
    - `klien/acara-detail.blade.php` — ditinjau dan dinilai sudah memenuhi (daftar penyedia terkait, status pesanan, tagihan gabungan, tindakan utama jelas, kontak penyedia tidak ditampilkan); **tidak diubah**.

Batasan dan hal yang belum diselesaikan:

- Route portal masih belum dilindungi middleware auth/role. Ini pekerjaan backend (roadmap 4.6/4.7 "Backend tetap memvalidasi seluruh hak akses") dan **di luar lingkup frontend Tahap 4**.
- Halaman tetap UI statis/prototipe: tidak ada penyimpanan ke server. Interaksi tetap memakai perilaku preview `aksi.js` (notifikasi tidak tersimpan).
- Audit aksesibilitas menyeluruh dan audit kontras belum dilakukan pada tahap ini.
- Working tree juga memuat perubahan lokal dari pekerjaan Tahap 1–3 dan penyelarasan massal sebelumnya; seluruhnya dipertahankan tanpa perubahan.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 210 assertions).
- `php artisan view:cache` → **Lulus** (Blade templates cached successfully).
- `node --check public/assets/js/aksi.js` dan `node --check public/assets/js/app-ui.js` → **Lulus**.
- `git diff --check` → **Lulus** (tanpa whitespace error).
- Tidak ada screenshot, tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-4-penyempurnaan-portal-per-role.md`.

### 6.6 Hasil Tahap 5.1 — Katalog Vendor (2026-11-15)

Lingkup yang disetujui pengguna: mulai **Tahap 5.1 (Katalog Vendor)** saja. Pengguna menyetujui pengerjaan subtahap 5.1–5.6 secara bertahap dan mengizinkan penambahan file Blade, CSS, dan JavaScript baru secara terpisah (tanpa dependency/framework baru). Pengguna juga menyetujui pembuatan UI statis yang aman untuk Request Quote (5.4) dan Kalender Ketersediaan (5.5) pada subtahap berikutnya — semua simulasi wajib diberi penanda jelas dan tidak boleh menampilkan keberhasilan transaksi/penyimpanan palsu.

Yang dikerjakan (frontend statis, tanpa backend):

- **`resources/views/klien/katalog-vendor.blade.php`** (diubah, disusun ulang) — kartu vendor menampilkan nama usaha, kategori, deskripsi singkat, lokasi layanan, kapasitas, harga tetap vs "Perlu penawaran", dan **badge status verifikasi** (contoh, diberi penanda). Banner data prototipe (`.data-note`), pill kategori, toolbar pencarian, dan empty state.
- **`resources/views/klien/katalog-vendor-detail.blade.php`** (baru) — detail layanan: paket & cakupan, kebijakan/kebutuhan khusus, galeri placeholder, rincian harga (layanan + biaya layanan 10% sebagai contoh), form "tambahkan ke acara" bersifat non-submit (`onsubmit="return false"`), dan kartu status penyedia.
- **`public/assets/css/katalog.css`** (baru) — gaya katalog Klien memakai token `--pk-*` dan alias `admin.css` (`.data-note`, `.cat .ph .status`, `.status-ok`/`.status-pending`, `.ptag*`, `.price-quote`, `.empty-state`, `.vendor-sub`, `.pkg-list`, `.policy`, `.gallery-ph`). Akan diperluas untuk 5.2.
- **`public/assets/js/katalog-filter.js`** (baru) — filter kategori/nama/harga maksimum **lokal** (tanpa request server, tanpa penyimpanan); progressive enhancement (tanpa JS seluruh kartu tetap tampil).

Catatan kejujuran implementasi:

- Semua angka, nama usaha, lokasi, paket, kebijakan, dan status verifikasi adalah **contoh statis** dan ditandai pada kedua halaman.
- Tidak ada notifikasi sukses palsu, tidak ada penyimpanan server, tidak ada submit form.
- Status verifikasi dinyatakan mengikuti keputusan Admin; frontend tidak mengklaim verifikasi tanpa data backend.

Batasan dan hal yang belum diselesaikan:

- Katalog masih UI statis/prototipe: tidak ada controller/model/DB/API; seluruh data berasal dari Blade.
- Route portal masih belum dilindungi middleware auth/role (pekerjaan backend, di luar lingkup frontend).
- Belum ada endpoint status verifikasi, request quote, maupun ketersediaan tanggal.
- Subtahap **5.2–5.6 belum dikerjakan**. Jangan lanjut tanpa instruksi pengguna.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 212 assertions). `ProjectPortalPagesTest` otomatis menguji setiap view role termasuk `klien/katalog-vendor` dan `klien/katalog-vendor-detail` (`assertOk` + tanpa tautan `.html`); `ProjectPortalAccessibilityTest` lulus (tanpa perubahan layout).
- `php artisan view:cache` → **Lulus**.
- `node --check` pada `katalog-filter.js`, `app-ui.js`, dan `aksi.js` → **Lulus**.
- `git diff --check` → **Lulus** (tanpa whitespace error).
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.1-katalog-vendor.md`.

### 6.7 Hasil Tahap 5.2 — Katalog Venue (2026-11-15)

Lingkup yang disetujui pengguna: hanya **Tahap 5.2 (Katalog Venue)**. Pengguna memilih opsi **halaman detail venue terpisah** ketika dikonfirmasi bahwa `klien/katalog-detail.blade.php` dipakai bersama oleh katalog venue, peralatan, dan seluruh paket rekomendasi (katering/hiburan/peralatan). Halaman generik tersebut **tidak diubah** agar tidak berisiko mengubah detail peralatan & rekomendasi.

Yang dikerjakan (frontend statis, tanpa backend):

- **`resources/views/klien/katalog.blade.php`** (diubah, disusun ulang menjadi daftar venue) — kartu venue menampilkan nama, penyedia, jenis, lokasi, kapasitas, fasilitas utama, deskripsi, **badge status verifikasi** (contoh), dan **rincian biaya terpisah** (harga sewa + biaya kebersihan + biaya layanan 10% + estimasi total dalam `.cost-list`). Banner data prototipe, pill jenis venue (Ballroom/Aula/Taman), toolbar pencarian lokal, dan empty state. Menggunakan ulang `katalog-filter.js` Tahap 5.1.
- **`resources/views/klien/katalog-venue-detail.blade.php`** (baru) — detail venue: jenis/penyedia/lokasi/kapasitas, deskripsi, **rincian biaya transparan**, fasilitas, **aturan penggunaan ruangan**, **kebijakan pembatalan** ("Informasi kebijakan pembatalan belum tersedia pada prototipe ini."), **informasi ketersediaan** (badge "Perlu konfirmasi" + peringatan; tanpa kalender/Instant Book), galeri placeholder, form "tambahkan ke acara" non-submit, dan kartu status penyedia.
- **`public/assets/css/katalog.css`** (diubah, diperluas) — menambah `.cost-list`/`.cost-list .tot` untuk rincian biaya; header diperbarui mencakup Venue. Selector Tahap 5.1 tidak diubah.
- **`resources/views/layouts/klien.blade.php`** (diubah, 1 klausa) — menambah `request()->is('klien/katalog-venue*')` pada kondisi `aria-current` menu "Vendor & venue" agar halaman detail venue baru ikut tersorot. Tidak ada perubahan struktur/aksesibilitas layout.

Catatan kejujuran implementasi:

- Semua nominal, nama venue, fasilitas, aturan, kebijakan, dan status verifikasi adalah **contoh statis/prototipe** dan ditandai eksplisit pada kedua halaman.
- Rincian biaya adalah **estimasi**, bukan tagihan final; total ditetapkan sistem. Komisi 10% ditampilkan sebagai tambahan ke tagihan Klien (bukan dipotong dari harga venue).
- Biaya yang belum diketahui ditampilkan sebagai "Belum ditentukan" (tidak dikarang).
- Kebijakan pembatalan ditulis "belum tersedia"; tidak ada aturan refund/tenggat/penalti yang dibuat.
- Ketersediaan ditandai "Perlu konfirmasi"; tanggal contoh ditandai simulasi; tidak ada kalender interaktif atau Instant Book.
- Tidak ada submit form, notifikasi sukses palsu, atau penyimpanan server.

Batasan dan hal yang belum diselesaikan:

- Katalog venue masih UI statis/prototipe: tidak ada controller/model/DB/API.
- `resources/views/klien/katalog-detail.blade.php` (halaman generik) **sengaja tidak diubah** pada tahap ini dan masih memuat konten contoh lama (rating "★ 4,8 dari 36 penilaian" dan kotak ketersediaan "Tersedia pada 12 Desember 2026") pada alur peralatan & rekomendasi. Ini **risiko/kebutuhan perbaikan lanjutan** yang harus ditangani hati-hati karena halaman dipakai bersama.
- Belum ada endpoint status verifikasi, biaya tambahan resmi, kebijakan pembatalan, atau ketersediaan tanggal.
- Route portal masih belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap **5.3–5.6 belum dikerjakan**. Jangan lanjut tanpa instruksi pengguna.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 214 assertions). `ProjectPortalPagesTest` otomatis menguji setiap view role termasuk `klien/katalog` dan `klien/katalog-venue-detail` (`assertOk` + tanpa tautan `.html`).
- `php artisan view:cache` → **Lulus**.
- `node --check public/assets/js/katalog-filter.js` → **Lulus**.
- `git diff --check` → **Lulus**.
- Render-check kernel: `/klien/katalog` (200; css, 4 kartu, empty state), `/klien/katalog-venue-detail` (200; css, rincian biaya, aturan ruangan, kebijakan, badge ketersediaan, sidebar aktif), dan tanpa regresi pada `/klien/katalog-peralatan`, `/klien/katalog-detail`, `/klien/katalog-vendor`, `/klien/katalog-vendor-detail` (semua 200; tanpa tautan `.html`).
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.2-katalog-venue.md`.

### 6.8 Hasil Tahap 5.3 — Profil dan Portofolio Penyedia (2026-11-15)

Lingkup yang disetujui pengguna: hanya **Tahap 5.3 (Profil dan Portofolio Penyedia)** sesuai instruksi resmi. Catatan penomoran: pada saat pekerjaan ini, roadmap masih memakai penomoran lama ("5.3 Katalog venue" / "5.4 Portofolio dan media"). Per 2026-11-15 penomoran Tahap 5 telah diselaraskan (lihat catatan di `docs/roadmap_pembaruan_frontend_pesta_kita.md`), sehingga subtahap ini bernomor final **5.3 Profil dan Portofolio Penyedia**. Isi dan hasil pekerjaan tidak diubah oleh penyelarasan ini.

Yang dikerjakan (frontend statis, tanpa backend):

- **`resources/views/klien/penyedia-vendor.blade.php`** (baru) — halaman profil penyedia Vendor untuk Klien: kategori/area/batas layanan, paket, kebijakan, status verifikasi "Terverifikasi (contoh)", **kontak pribadi disembunyikan**, galeri portofolio placeholder + empty state, remah & tombol kembali ke `/klien/katalog-vendor`, tautan ke `katalog-vendor-detail`. Banner `.data-note` data prototipe.
- **`resources/views/klien/penyedia-venue.blade.php`** (baru) — halaman profil Pemilik Venue: jenis/lokasi/kapasitas, fasilitas, aturan penggunaan ruangan, kebijakan venue ("belum tersedia"), ketersediaan badge "Perlu konfirmasi" + `.warn-box` (tanpa kalender/Instant Book), galeri placeholder + empty state, kembali ke `/klien/katalog`, tautan ke `katalog-venue-detail`.
- **`public/assets/css/profil-media.css`** (baru) — grid pratinjau media (`.media-list`/`.media-item`, `aspect-ratio:4/3`, `object-fit:cover`, 2 kolom di ≤480px). Hanya selector baru.
- **`public/assets/js/profil-media.js`** (baru) — pratinjau gambar **lokal** + validasi format/ukuran tanpa server; `URL.createObjectURL` + pembersihan `URL.revokeObjectURL` (saat ganti & `beforeunload`). Tidak ada klaim tersimpan.
- **`resources/views/vendor/profil.blade.php`** & **`resources/views/venue/profil.blade.php`** (diubah) — ditambah form UI pengelolaan foto (5.3.4): area pilih file (`.upload-box`), hint batas "JPG/PNG maks 5 MB" yang ditandai **konfigurasi contoh**, `.media-list` pratinjau, empty state, `field-error role="alert"`; form profil & kata sandi lama dipertahankan.
- **`resources/views/klien/katalog-vendor-detail.blade.php`** & **`katalog-venue-detail.blade.php`** (diubah, 5.3.2) — ditambah aksi "Lihat profil penyedia" + "Kembali ke katalog".
- **`resources/views/layouts/klien.blade.php`** (diubah, 1 klausa, 5.3.2) — `request()->is('klien/penyedia-*')` pada `aria-current` menu "Vendor & venue".
- **`resources/views/klien/katalog-detail.blade.php`** (diubah, 5.3.6) — dua penggantian setempat: rating contoh "★ 4,8 dari 36 penilaian" → "Rating belum tersedia pada prototipe"; kotak ketersediaan "Tersedia pada 12 Desember 2026" → kotak peringatan (tanggal hanya contoh, ketersediaan belum dikonfirmasi). Bukan penggantian global; struktur lain tidak diubah.

Catatan kejujuran implementasi:

- Semua data penyedia (nama usaha, kategori, area, paket, kebijakan, fasilitas, aturan) adalah **contoh statis/prototipe** dan ditandai eksplisit.
- Status verifikasi hanya label contoh; frontend tidak mengklaim verifikasi sistem.
- Kontak pribadi penyedia tidak ditampilkan (aturannya managed marketplace).
- Unggah foto: pratinjau lokal saja, **tidak ada POST/endpoint/penyimpanan**; tidak ada notifikasi sukses palsu. Batas 5 MB/JPG-PNG ditandai konfigurasi contoh yang perlu disepakati backend.
- Galeri portofolio memakai placeholder jujur karena tidak ada aset foto; tidak ada foto dikarang.

Batasan dan hal yang belum diselesaikan:

- Profil masih UI statis/prototipe: tidak ada controller/model/DB/API.
- Belum ada endpoint unggah media, daftar foto, status verifikasi, atau ketersediaan tanggal.
- `resources/views/venue/profil.blade.php` masih memuat data bergaya Vendor ("Dapur Nusantara") sebagai konten lama — **tidak diubah** (di luar lingkup 5.3); perlu ditinjau pada tahap relevan.
- Route portal masih belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap **5.4–5.6 belum dikerjakan**. Jangan lanjut tanpa instruksi pengguna.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 218 assertions). `ProjectPortalPagesTest` mencakup `klien/penyedia-vendor` & `klien/penyedia-venue`.
- `php artisan view:cache` → **Lulus**.
- `node --check` pada `profil-media.js`, `katalog-filter.js`, `aksi.js`, `app-ui.js` → **Lulus**.
- `git diff --check` → **Lulus**.
- Render-check kernel (`php artisan serve` + `Invoke-WebRequest`): 11 halaman 200 dengan penanda konten sesuai (`/klien/penyedia-vendor`, `/klien/penyedia-venue`, `/klien/katalog`, `/klien/katalog-vendor`, `/klien/katalog-vendor-detail`, `/klien/katalog-venue-detail`, `/klien/katalog-peralatan`, `/klien/katalog-detail`, `/klien/rekomendasi`, `/vendor/profil`, `/venue/profil`).
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.3-profil-portofolio.md`.

### 6.9 Hasil Tahap 5.4 — Request Quote / Custom Quotation (2026-11-15)

Lingkup yang disetujui pengguna: hanya **Tahap 5.4 (Request Quote / Custom Quotation)** sesuai instruksi resmi (Bagian B), termasuk penyelarasan penomoran Tahap 5 (Bagian A). Subtahap **5.5 dan 5.6 tidak dikerjakan**.

Alur frontend yang dibuat (managed marketplace): Klien mengajukan permintaan penawaran → Penyedia (Vendor/Venue) mengusulkan harga → Admin meninjau & memutuskan → Klien melihat hasil yang disahkan. Kedelapan halaman memakai auto-routing portal (tanpa perubahan route) dan berstatus **prototipe frontend** (tidak ada penyimpanan ke server).

Yang dikerjakan (frontend statis, tanpa backend):

- **`resources/views/klien/permintaan-penawaran.blade.php`** (baru) — daftar permintaan penawaran Klien + form pengajuan (pilih layanan/venue, acara, tanggal, perkiraan tamu, anggaran, kebutuhan) dengan kolom "Harga katalog (referensi)". Banner `.data-note` penanda prototipe. Form non-submit (`onsubmit="return false"`).
- **`resources/views/klien/permintaan-penawaran-detail.blade.php`** (baru) — kartu "Perbandingan harga" dengan **tiga baris terpisah**: harga katalog (referensi) `.b-mute`, harga diajukan penyedia `.b-warn`, harga disetujui Admin `.b-ok`; rincian tagihan (harga disetujui + komisi 10% + total contoh) + `.warn-box`; kartu "Arti status harga" (`.policy`) membedakan Katalog/Usulan/Menunggu/Disetujui/Belum tersedia.
- **`resources/views/vendor/permintaan-penawaran.blade.php`** & **`resources/views/venue/permintaan-penawaran.blade.php`** (baru) — daftar permintaan penawaran untuk penyedia + kolom harga katalog (referensi); `.alert` menegaskan **komisi 10% tidak dipotong dari harga penyedia**.
- **`resources/views/vendor/permintaan-penawaran-detail.blade.php`** & **`resources/views/venue/permintaan-penawaran-detail.blade.php`** (baru) — kartu permintaan Klien, kartu harga katalog penyedia (referensi), dan kartu **"Usulkan harga"** (field via `.field-control` + tombol `data-confirm` dengan `data-need`/`data-need-msg` validasi wajib isi). Tidak ada nominal final yang ditetapkan frontend.
- **`resources/views/admin/permintaan-penawaran.blade.php`** (baru) — antrean peninjauan Admin (`.workflow-card` + `.tabs`), tiap item memuat `.price-compare` (Harga katalog → Harga diajukan penyedia) dan tombol Setujui/Tolak (`data-confirm`+`data-ask` alasan wajib).
- **`resources/views/admin/permintaan-penawaran-detail.blade.php`** (baru) — perbandingan harga + perubahan nominal/persentase, harga final "Menunggu keputusan Admin" (`.warn-t`), rincian penawaran per komponen, dan panel "Tindakan Admin" (`#decision-quote`, `#reason-quote` dengan `data-need` alasan wajib). Catatan tegas: **halaman ini tidak menetapkan nominal final** (keputusan final ada di backend).
- **`resources/views/klien/katalog-vendor-detail.blade.php`** & **`katalog-venue-detail.blade.php`** (diubah) — ditambah aksi "Minta penawaran" + tautan "Ajukan permintaan penawaran" menuju `/klien/permintaan-penawaran`.
- **`resources/views/layouts/klien.blade.php`, `layouts/vendor.blade.php`, `layouts/venue.blade.php`, `layouts/admin.blade.php`** (diubah) — menambah menu "Permintaan penawaran"/"Peninjauan penawaran" (badge n) + klausa `aria-current` pada `request()->is('<role>/permintaan-penawaran*')`.

Catatan kejujuran implementasi:

- Semua data permintaan penawaran (nomor, layanan/venue, nominal, status) adalah **contoh statis/prototipe** dan ditandai eksplisit pada setiap halaman.
- **Tidak ada penyimpanan ke server**: pengajuan, usulan harga, dan keputusan Admin hanya memicu dialog pratinjau (`aksi.js`) dengan toast "Pratinjau frontend: tindakan ini tidak disimpan ke server." Tidak ada notifikasi sukses palsu.
- Tiga jenis nominal **tidak tertukar**: harga katalog ≠ harga usulan penyedia ≠ harga final (disetujui Admin). Frontend tidak pernah menetapkan nominal final; backend adalah sumber kebenaran.
- **Komisi 10% ditambahkan ke tagihan Klien**, bukan dipotong dari harga penyedia (ditegaskan pada halaman penyedia dan rincian Klien/Admin).

Batasan dan hal yang belum diselesaikan:

- Seluruh UI 5.4 statis/prototipe: tidak ada controller/model/DB/API/route/middleware baru.
- Belum ada endpoint penyimpanan permintaan, usulan harga, atau keputusan Admin.
- Perhitungan komisi, penomoran permintaan, dan harga final sepenuhnya menunggu backend.
- Route portal masih belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap **5.5–5.6 belum dikerjakan**. Jangan lanjut tanpa instruksi pengguna.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 234 assertions; naik dari 218 karena 8 view baru). `ProjectPortalPagesTest` mencakup kedelapan halaman permintaan penawaran (`assertOk` + larangan tautan `.html`).
- `php artisan view:cache` → **Lulus**.
- `node --check` pada `aksi.js` & `app-ui.js` → **Lulus**.
- `git diff --check` → **Lulus**.
- Render-check kernel (`php artisan serve` + `Invoke-WebRequest`): 8 halaman 200 dengan penanda konten sesuai (`/klien/permintaan-penawaran`, `/klien/permintaan-penawaran-detail`, `/vendor/permintaan-penawaran`, `/vendor/permintaan-penawaran-detail`, `/venue/permintaan-penawaran`, `/venue/permintaan-penawaran-detail`, `/admin/permintaan-penawaran`, `/admin/permintaan-penawaran-detail`).
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.4-request-quote.md`.

### 6.10 Hasil Tahap 5.5 — Kalender Ketersediaan (2026-11-15)

Lingkup yang disetujui pengguna: hanya **Tahap 5.5 (Kalender Ketersediaan)** sesuai instruksi resmi. Frontend untuk tiga portal (Klien, Vendor, Pemilik Venue); Admin tidak memerlukan fitur khusus kalender. Subtahap **5.6 tidak dikerjakan**.

Status ketersediaan yang dibedakan (warna + ikon + label teks, bukan warna saja):

- **Tersedia** — hijau, ikon ✓, badge `.b-ok`.
- **Tidak tersedia** — merah, ikon ✕, badge `.b-bad`.
- **Belum dikonfirmasi / Sedang ditinjau** — netral, ikon ?, badge `.b-warn`.

Yang dikerjakan (frontend statis, tanpa backend):

- **`public/assets/css/kalender.css`** (baru) — gaya kalender (`.kal-toolbar`, `.kal-nav`, `.kal-title`, `.kal-table`, `.kal-day`, `.kal-num`, `.kal-mark`, `.kal-ok/.kal-bad/.kal-warn`, `.kal-day.is-sel` ring dalam, `.kal-fill`, `.kal-legend`, `.kal-key`, `.kal-nodata`, `.kal-date`, `.kal-edit`, `.kal-list`). Memakai token `--pk-*`; responsif @760px & @420px (sel min-height 58→50→44px); `table-layout:fixed` mencegah overflow horizontal.
- **`public/assets/js/kalender.js`** (baru) — logika kalender Vanilla JS (IIFE). Membaca `[data-kalender]` + konfigurasi `#kal-config` (`<script type="application/json">`, bukan backend). Minggu dimulai Senin (`lead = (first.getDay() + 6) % 7`). Fungsi render grid, detail tanggal, daftar tanggal berstatus, empty state, picker/kategori. Navigasi bulan prev/next. Kontrol prototype (`[data-kal-set]`) hanya mengubah `dataset.days` di memori. Tombol tanggal punya `aria-label` lengkap + `aria-pressed`; seleksi memakai ring dalam agar tidak bertumpuk dengan focus outline.
- **`resources/views/klien/kalender-ketersediaan.blade.php`** (baru) — `editable:false`. Kategori (`#kal-kategori`: vendor/venue) + pemilih penyedia (`#kal-picker`); legend + `#kal-nodata`; detail tanggal; kartu Ajukan permintaan penawaran dengan `#kal-quote-link` (`data-base="{{ url('/klien/permintaan-penawaran') }}"`) yang meneruskan `?tanggal=`. Data contoh ditandai `.data-note`.
- **`resources/views/vendor/kalender-ketersediaan.blade.php`** (baru) — `editable:true`. Pemilih layanan; legend; kartu kontrol prototype (`[data-kal-editable]`) dengan tombol `[data-kal-set]` + `.warn-box` peringatan "Perubahan jadwal ini hanya berlaku pada tampilan prototype dan belum tersimpan ke server."; kartu "Daftar tanggal berstatus" (`#kal-list`); `#kal-nodata` bila kosong. Data contoh ditandai `.alert`.
- **`resources/views/venue/kalender-ketersediaan.blade.php`** (baru) — `editable:true`, **per ruangan** (ballroom, aula, taman, ruang VIP). Hint menegaskan tiap ruangan punya jadwal berbeda; tidak diasumsikan semua ruangan status sama. Kontrol prototype + daftar tanggal seperti Vendor.
- **`resources/views/layouts/klien.blade.php`**, **`layouts/vendor.blade.php`**, **`layouts/venue.blade.php`** (diubah, 1 item nav masing-masing) — menambah menu "Kalender ketersediaan" dengan klausa `aria-current` (`request()->is('<role>/kalender-ketersediaan*')`).
- **`resources/views/klien/permintaan-penawaran.blade.php`** (diubah, kaitan 5.5→5.4) — bila `request()->filled('tanggal')`, tampil `.alert` kontekstual "Tanggal pilihan dari kalender ketersediaan: …" yang menegaskan tanggal hanya usulan tampilan, belum tersimpan, dan belum dipesan; input `#tanggal` menampilkan tanggal tersebut.

Catatan kejujuran implementasi:

- **Tidak ada penyimpanan ke server**: perubahan status pada kontrol prototype hanya mengubah state di memori (hilang saat halaman dimuat ulang); tidak ada notifikasi sukses palsu.
- Semua penyedia, layanan, ruangan, dan tanggal berstatus adalah **contoh statis/prototipe** dan ditandai eksplisit (`.data-note` untuk Klien; `.alert` untuk Vendor/Venue).
- Status kalender menggunakan **label teks + ikon**, tidak mengandalkan warna saja (memenuhi aturan Tahap 1.4).
- Ketersediaan tidak diklaim pasti; teks peringatan menegaskan status perlu dikonfirmasi sistem sebelum pesanan disahkan. Instant Book tidak diterapkan.
- Kontak pribadi penyedia tetap tidak ditampilkan.

Batasan dan hal yang belum diselesaikan:

- Kalender masih UI statis/prototipe: tidak ada controller/model/DB/API; tidak ada perubahan `routes/web.php` (view baru otomatis di-route oleh `/{page}` + `view()->exists()`).
- Belum ada endpoint ketersediaan/booking, sehingga data tanggal berstatus adalah contoh yang dikonfigurasi di Blade.
- Route portal masih belum dilindungi middleware auth/role (pekerjaan backend).
- Subtahap **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5) belum dikerjakan**. Jangan lanjut tanpa instruksi pengguna.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 240 assertions; naik dari 234 karena 3 view baru). `ProjectPortalPagesTest` otomatis menguji setiap view role termasuk ketiga halaman kalender (`assertOk` + larangan tautan `.html`).
- `php artisan view:cache` → **Lulus**.
- `node --check` pada `kalender.js`, `katalog-filter.js`, `aksi.js`, `app-ui.js` → **Lulus**.
- `git diff --check` → **Lulus**.
- Render-check kernel (`php artisan serve` + `Invoke-WebRequest`): 200 pada `/klien/kalender-ketersediaan` (marker `kal-config` + `kal-quote-link`), `/vendor/kalender-ketersediaan` (`kal-config`), `/venue/kalender-ketersediaan` (`kal-config`), `/klien/permintaan-penawaran` (200 tanpa alert), dan `/klien/permintaan-penawaran?tanggal=…` (200 dengan alert "Tanggal pilihan dari kalender"). Tanpa regresi pada halaman Tahap 5.1–5.4 & dashboard tiap role.
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.5-kalender-ketersediaan.md`.

### 6.11 Hasil Tahap 5.6 — Kriteria Penerimaan dan Evaluasi Tahap 5 (2026-11-15)

Lingkup: **evaluasi menyeluruh Tahap 5.1–5.5** dan pemeriksaan penerimaan. **Bukan** redesign, bukan penambahan fitur baru, dan **tidak** memulai Tahap 6. Batasan backend/route/database/environment dipatuhi (tidak ada perubahan di area tersebut).

**Status penerimaan akhir Tahap 5: Diterima Bersyarat.** Fitur frontend utama Tahap 5.1–5.5 sesuai kebutuhan dan berjalan di sisi frontend; keterbatasan tersisa berasal dari backend yang belum tersedia (batas integrasi), bukan bukti fitur backend selesai.

Ringkasan hasil per subtahap (detail: `docs/reports/tahap-5.6-kriteria-penerimaan-dan-evaluasi.md`):

- **5.1 Katalog Vendor — Lulus Bersyarat.** Informasi/filter/detail konsisten; harga tetap vs penawaran jelas; kontak pribadi tidak ditampilkan; data statis tanpa backend.
- **5.2 Katalog Venue — Lulus Bersyarat.** Harga sewa, biaya kebersihan, komisi 10%, dan estimasi total dibedakan; biaya tak diketahui = "Belum ditentukan"; estimasi dinyatakan bukan tagihan final; ketersediaan "Perlu konfirmasi"; kebijakan pembatalan "belum tersedia".
- **5.3 Profil & Portofolio Penyedia — Lulus Bersyarat.** Profil publik sesuai role; kontak pribadi terlindungi; galeri placeholder + pratinjau lokal (tanpa unggah server); perbaikan 5.3.6 pada `katalog-detail` terverifikasi.
- **5.4 Request Quote — Lulus Bersyarat.** Delapan halaman (Klien/Vendor/Venue/Admin) konsisten; tiga nominal dibedakan; komisi 10% ditambahkan ke Klien; form non-submit.
- **5.5 Kalender Ketersediaan — Lulus Bersyarat.** Bulan/tahun, navigasi, awal minggu Senin, detail tanggal, indikator status, per layanan/ruangan, `?tanggal=` → Request Quote; perubahan hanya di memori.

**Temuan dan tindakan:**

- **Diperbaiki:** `venue/profil.blade.php` memuat data bergaya Vendor ("Dapur Nusantara"/"Katering"/`vendor@pestakita.test`) → diganti data Venue ("Graha Melati"/"Venue / Gedung pertemuan"/`venue@pestakita.test`).
- **Diperbaiki:** `admin/permintaan-penawaran.blade.php` hierarki heading tidak konsisten (`<h3>` item kedua) → `<h2>`.
- **Keterbatasan terdokumentasi:** kalender hanya membedakan 3 status — **"diblokir" dan "sudah dipesan (booked)" belum dibedakan** (butuh data transaksi backend). Tidak dibuat status "booked" palsu.
- **Out-of-scope:** `venue/pencairan-detail.blade.php` masih menampilkan "Dapur Nusantara" (domain Tahap 6); `klien/katalog-detail.blade.php` masih memakai label "harga akhir per acara" (halaman bersama yang sengaja tidak dirombak pada 5.2).
- **Terverifikasi bukan bug:** nomor telepon `0341-555-*` pada kartu "Butuh bantuan?" konsisten dengan pola halaman Tahap 4 (bantuan Pesta Kita), bukan kebocoran kontak.
- **Catatan path:** instruksi menyebut `docs/roadmap_pembaruan_frontend.md`, tetapi file roadmap aktual bernama `docs/roadmap_pembaruan_frontend_pesta_kita.md`.

Pengujian aktual pada 2026-11-15:

- `php artisan test --compact` → **Lulus** (4 tests, 240 assertions).
- `php artisan view:cache` → **Lulus** (Blade templates cached successfully).
- `node --check` pada `katalog-filter.js`, `kalender.js`, `aksi.js`, `app-ui.js` → **Lulus**.
- `git diff --check` → **Lulus** (tanpa whitespace error).
- Tidak dijalankan pada sesi ini: uji viewport langsung, audit screen reader/kontras menyeluruh, render-check HTTP manual. Rendering dicakup oleh `ProjectPortalPagesTest` (`assertOk` seluruh view per role) & `ProjectPortalAccessibilityTest`.
- Tidak ada screenshot; tidak ada perubahan backend/route/database/environment.

Rincian: `docs/reports/tahap-5.6-kriteria-penerimaan-dan-evaluasi.md`.

### 6.12 Hasil Tahap 6 — Transaksi, Tagihan, Pembayaran, Refund, dan Pencairan (2026-11-15)

Lingkup yang dikerjakan: pembaruan **frontend** Tahap 6 pada empat portal untuk delapan fokus — informasi tagihan & rincian biaya, kode unik transfer, unggah bukti pembayaran, status & riwayat pembayaran, invoice/nota, pembatalan & refund, pencairan Vendor/Venue, serta dashboard & pengelolaan keuangan Admin. Dimulai dengan audit read-only; seluruh fitur keuangan dinyatakan **UI prototipe frontend** karena backend transaksi/keuangan tidak ditemukan di repository.

Hasil audit (dukungan backend): seluruh halaman keuangan adalah **view Blade statis** yang dilayani oleh route generik `Route::get('/{page}', …)` + `view()->exists()` di `routes/web.php`. **Tidak ditemukan** controller, model, migration/tabel, endpoint, enum status pembayaran/refund/payout, maupun payment gateway untuk domain transaksi. Karena itu tidak ada nominal/status final yang boleh ditetapkan frontend.

Halaman/berkas yang diperbarui (perbaikan keamanan informasi keuangan):

- **`public/assets/js/aksi.js`** — menambah handler tombol salin (`[data-salin]` / `data-salin-target` / `data-salin-done`). Menyalin ke clipboard hanya di browser; hasil diumumkan lewat `#toast` (ber-`role="status"`) sehingga dapat dibaca screen reader. Tidak mengubah data server. (Tombol "Salin" sebelumnya tanpa handler.)
- **`public/assets/css/keselarasan.css`** — menambah definisi `.data-note` pada lapisan lintas panel (asal hanya di `katalog.css`), agar halaman keuangan Admin/Vendor/Venue dapat memakai penanda prototipe.
- **Klien:** `klien/tagihan`, `klien/tagihan-ditolak`, `klien/tagihan-lunas`, `klien/acara-batalkan`, `klien/acara-dibatalkan`, `klien/acara-terkonfirmasi` — penanda `.data-note`, label **"Kode unik transfer"**, tombol salin kode unik + rekening, tombol unduh PDF dinonaktifkan bila mekanisme tidak tersedia, dan **penghapusan perhitungan refund final lokal** (estimasi 70%/nominal berada di halaman) diganti pernyataan bahwa nominal refund ditetapkan sistem.
- **Vendor & Venue:** `vendor/pencairan`, `vendor/pencairan-detail`, `venue/pencairan`, `venue/pencairan-detail` — `venue/pencairan*` yang semula memuat data Vendor ("Dapur Nusantara"/"Katering prasmanan") diganti data Venue ("Graha Melati"); rincian dana diperbaiki agar **hak penyedia = harga penuh tanpa dipotong komisi** (komisi 10% "Ditagihkan ke Klien"); penanda `.data-note`.
- **Admin:** `admin/pembayaran`, `admin/pembayaran-detail`, `admin/tagihan-detail`, `admin/pembatalan`, `admin/pembatalan-detail`, `admin/pencairan`, `admin/pencairan-detail`, `admin/index`, `admin/mitra-detail` — penanda `.data-note`; label "Kode unik transfer"; **penghapusan blok "Perhitungan refund otomatis"** dan input `value="70%"` pada detail pembatalan; penyamaran nomor rekening menjadi `···· 7890` pada daftar/detail pencairan dan rekening mitra.

Perilaku frontend: tetap non-submit/preview. Aksi konfirmasi memakai dialog `aksi.js` + toast "Pratinjau frontend: tindakan ini tidak disimpan ke server."; form non-submit memicu toast "formulir ini tidak mengirim atau menyimpan data." Tidak ada notifikasi sukses transaksi palsu.

Aturan bisnis yang dipertahankan: managed marketplace; satu tagihan gabungan per acara; komisi **10% ditambahkan ke tagihan Klien** (bukan dipotong dari harga penyedia); pencairan setelah acara selesai diproses Admin; refund mengikuti kebijakan & keputusan proses; backend = sumber kebenaran nominal & status final.

Status integrasi backend: **belum tersedia** untuk seluruh domain transaksi. Semua halaman statis/prototipe.

Pengujian aktual 2026-11-15: `php artisan test --compact` → **Lulus** (4 tests, 240 assertions); `php artisan view:cache` → **Lulus**; `node --check public/assets/js/aksi.js` dan `node --check public/assets/js/app-ui.js` → **Lulus**; `git diff --check` → **Lulus**; `git status --short` diperiksa (memuat perubahan lokal Tahap 1–6 + penambahan `laravel/boost` di `composer.json` yang **bukan** dibuat pada Tahap 6 — lihat temuan).

Temuan & keterbatasan:

- **Temuan (Blocker, diperbaiki):** beberapa halaman menghitung **refund final secara lokal** (persentase/estimasi nominal + kode unik) pada `klien/acara-batalkan`, `klien/acara-dibatalkan`, `klien/acara-terkonfirmasi`, `admin/pembatalan`, dan `admin/pembatalan-detail`. Ini melanggar larangan instruksi. Blok perhitungan dihapus/diganti pernyataan bahwa sistem yang menetapkan nominal.
- **Temuan (Critical, diperbaiki):** rincian dana pencairan `vendor/pencairan-detail` & `venue/pencairan-detail` memotong komisi 10% dari harga penyedia ("Biaya layanan Pesta Kita −Rp 180.000"). Diperbaiki: hak penyedia = harga penuh; komisi ditagihkan ke Klien.
- **Temuan (Major, diperbaiki):** `venue/pencairan` & `venue/pencairan-detail` menampilkan identitas Vendor ("Dapur Nusantara", "Katering prasmanan"). Diperbaiki menjadi data Venue.
- **Temuan (Minor/Critical, diperbaiki):** tombol "Salin" tanpa handler JS → ditambahkan handler; nomor rekening penuh (`1234567890`) pada `admin/pencairan*` & `admin/mitra-detail` → disamarkan.
- **Catatan (bukan dibuat pada Tahap 6):** `composer.json`/`composer.lock` di working tree memuat `laravel/boost` (dev) dan ada `boost.json`/`.mcp.json`/`CLAUDE.md`. Ini **bukan bagian pekerjaan Tahap 6** dan bertentangan dengan batasan "dependensi baru"; tidak diubah oleh Tahap 6 dan perlu keputusan pengguna.
- Keterbatasan (out-of-scope): `venue/keberatan`, `venue/rundown`, `venue/pengajuan-harga` masih memuat nama item/layanan Vendor (domain Tahap 4/5, bukan domain keuangan Tahap 6) — dicatat, belum diubah.
- Keterbatasan backend: tidak ada enum status, endpoint unggah bukti, nomor invoice/kode unik/referensi transfer otomatis, kalkulasi refund/payout, atau payment gateway.
- Tidak ada screenshot; tidak ada perubahan backend/route/database/migration/controller/model/middleware/`.env`/payment gateway/scheduler.

Rincian: `docs/reports/tahap-6-transaksi-tagihan-pembayaran-refund-pencairan.md`.

### 6.13 Hasil BE-1 — Autentikasi, Otorisasi, Role, dan Wiring Frontend Terbatas (2026-11-16)

Lingkup yang disetujui pengguna: **BE-1 Batch 1** (Gate 1 kode disetujui; Gate 2 pembuatan database disetujui dan dilaksanakan). Keputusan disetujui: **T1** (redirect login ditentukan role database; tab role form hanya visual), **T2** (akun vendor/venue baru langsung `aktif`, verifikasi bisnis `menunggu`, `verified_at=null`; status akun ≠ verifikasi bisnis), **B18** (demo bar hanya environment `local`, bukan bypass auth). B4/B15/B6 tanpa dampak skema BE-1. Laravel Boost dipertahankan; tidak ada perubahan dependensi.

Yang dikerjakan (backend nyata + wiring frontend terbatas):

- **Skema & enum**: migration menambah `users.role` (indexed, NOT NULL), `users.phone` (nullable), `users.status` (default `aktif`); tabel `client_profiles`, `vendor_profiles`, `venue_profiles` (FK unique cascade); enum `Role`, `UserStatus`, `VerificationStatus`; model profil + relasi User; factory states per role.
- **Auth**: `POST /login` (rate limit 5/menit email+IP, pesan generik, session regenerate, redirect role DB), `POST /register` (hanya klien/vendor/venue; admin ditolak server; transaction user+profil; auto-login; redirect klien→`/klien`, provider→`/<portal>/verifikasi`), `POST /logout` (invalidate + regenerate token).
- **Otorisasi**: middleware `EnsureRole` (alias `role`) melindungi seluruh halaman portal termasuk catch-all `/{page}`; guest→`/login`, role salah→403, akun nonaktif di tengah sesi→logout paksa. Route POST eksplisit didaftarkan sebelum catch-all; halaman login portal menjadi route guest eksplisit sebelum grup terproteksi (satu deviasi yang diperlukan agar guest dapat mencapai login portal).
- **Profil**: `POST /profil` (name/phone; provider tambah business_name/category/address/pic_name; email read-only; kepemilikan dari authenticated user) dan `POST /profil/sandi` (wajib sandi saat ini benar, min 8, confirmed).
- **Wiring frontend terbatas (10 file)**: `auth/login`, `auth/register`, `admin/login`, `klien/login`, `vendor/login`, `admin/profil`, `klien/profil`, `vendor/profil`, `venue/profil`, `partials/demo-bar`. Simulasi redirect JS dihapus; error inline memakai pola `.field-error` yang ada; form logout di 4 halaman profil; bagian bank & foto tetap prototipe (B12/B14 terbuka). Layout/CSS/`aksi.js`/sidebar/halaman lain tidak disentuh.
- **Seeder**: `LocalDemoSeeder` (guard local, idempoten) — 4 akun demo `admin/klien/vendor/venue@pestakita.test` (sandi `password`, hanya lokal).
- **Gate 2 dilaksanakan**: database `pesta_kita` dibuat (utf8mb4/utf8mb4_unicode_ci, tanpa perubahan `.env`); 7 migration berjalan (3 baseline + 4 BE-1); seed + verifikasi data (4 user aktif, profil provider `menunggu`/`verified_at=null`, index `users_role_index`); seed ulang tanpa duplikat.

Batasan dan hal yang belum diselesaikan:

- Tautan "Daftar" pada login portal masih menunjuk halaman prototipe terproteksi (`/klien/register`, `/vendor/registrasi-penyedia`); guest diarahkan ke `/login`. Perlu keputusan wiring lanjutan.
- Manajemen pengguna/verifikasi mitra oleh Admin belum ada (tahap berikutnya); reset password & verifikasi email di luar lingkup BE-1.
- Seluruh halaman di luar wiring tetap prototipe frontend; hanya auth/profil/sandi yang benar-benar terhubung backend.

Pengujian aktual pada 2026-11-16:

- `php artisan test --compact` → **Lulus** (58 tests, 616 assertions; suite baru `tests/Feature/Auth/*` mencakup matriks role×portal, guest, nonaktif, register/login/logout/profil, rate limit, rollback, demo bar 3 environment). Test portal lama ditulis ulang memakai `actingAs` (B18).
- `php artisan route:list` → 23 route (19 aplikasi), urutan POST sebelum catch-all terverifikasi.
- `php artisan migrate:status` → 7 migration `[1] Ran` di `pesta_kita`.
- `php artisan view:cache` → Lulus (lalu `view:clear`).
- Pint (`php vendor/bin/pint app database routes tests`) → 29 isu gaya diperbaiki (terutama line ending); tes ulang tetap lulus.
- `git diff --check` → Lulus. Working tree: 110 modified / 22 deleted / 53 untracked; seluruh perubahan baseline dipertahankan; HEAD tetap `50bb69f` (tanpa commit).
- Tidak ada screenshot; tidak ada perubahan dependensi/`.env`.

Rincian: `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md`.

### 6.14 Hasil BE-2 — Skema Domain Non-Finansial (Gate 3) (2026-11-16)

Lingkup yang disetujui pengguna: **BE-2 Batch 1** (Gate 3 disetujui). Hanya skema domain non-finansial: 16 tabel, model + relasi, enum status, factory, perluasan seeder demo lokal, dan integrity tests. Tanpa controller/endpoint/workflow/UI. Keputusan terkunci diterapkan: **B4** (cleaning_fee integer rupiah nullable, terpisah dari rental_price), **B15** (satu tabel catalog_items dengan kind layanan/peralatan, tanpa tabel equipment), **B5** (deposit_amount nullable tanpa logika), **B6** (tanpa events.venue_id; venue via order_items→venue_room, boleh banyak venue per acara), **B7** (objection_deadline hanya kolom nullable), **B11** (availability hanya 3 status manual, tanpa booked), **T2** (status akun ≠ verifikasi bisnis). Laravel Boost dipertahankan; tanpa perubahan dependensi/.env.

Yang dikerjakan:

- **Migration (16, additive, FK-safe, reversible)**: `provider_bank_accounts`, `company_bank_accounts`, `catalog_items`, `venues`, `venue_rooms`, `venue_facilities`, `media`, `price_change_requests`, `availability_days`, `events`, `quote_requests`, `quote_proposals`, `quote_decisions`, `order_items`, `order_objections`, `audit_logs` (timestamp `2026_11_16_100001`–`100016`). Uang `unsignedBigInteger`; softDeletes hanya `catalog_items`/`venues`; cascade hanya `venue_rooms`/`venue_facilities`/`quote_proposals`; unique availability(subject,date), room/facility(venue,name), quote_decisions(quote_request_id), users.email. Seluruh `down()` = `dropIfExists`, terverifikasi reversible.
- **Model + relasi (16 baru)**: relasi typed + docblock; morph memakai alias pendek via `Relation::enforceMorphMap` di `AppServiceProvider`. `User` ditambah inverse events/quoteRequests/quoteDecisions/reviewedPriceChanges/orderObjections/auditLogs; `VendorProfile`/`VenueProfile` ditambah catalogItems/venues/bankAccounts/media. Tanpa `events.venue_id`.
- **Enum (10 baru)**: CatalogKind, PricingMode, CatalogItemStatus, AvailabilityStatus, MediaVisibility, PriceChangeStatus, EventStatus, QuoteRequestStatus, QuoteDecisionResult, OrderItemStatus. Nilai berdasar label Blade + aturan bisnis; `order_objections.status`/`admin_decision` dan `venues.kind` tetap string (belum himpunan tertutup).
- **Factory (16 + 3 profil)**: states sesuai C.5 (provider menunggu/terverifikasi; catalog layanan/peralatan × tetap/perlu_penawaran; venue dengan/tanpa cleaning fee & deposit; event draft/diajukan; quote dengan/tanpa proposal/decision). Factory profil memakai state role langsung agar tidak membuat profil ganda.
- **Seeder**: `LocalDemoSeeder` diperluas (tetap guard local, idempoten firstOrCreate) — 2 item katalog Dapur Nusantara (1 layanan + 1 peralatan), venue Graha Melati + 2 ruangan + 4 fasilitas + 3 availability, 1 acara draft Klien, 1 quote request + 1 proposal. Data BE-1 tidak digandakan (user tetap 4).
- **Integrity tests (3 file, 38 test)**: `tests/Feature/Database/{SchemaIntegrityTest,DomainRelationsTest,LocalDemoSeederTest}.php` mencakup 14 kategori §8 (FK orphan, UQ availability/email/room/facility/decision, cascade, null B4/B5, kind B15, integer rupiah, soft delete, morph resolve, relasi quote/event/order, seeder local-only + idempoten). SQLite memory, FK enforcement aktif.

Batasan dan hal yang belum diselesaikan:

- Belum ada controller/endpoint CRUD, workflow verifikasi Admin, alur quote/order, invoice/payment/refund/payout, rundown/review/notification (BE-3+).
- Seluruh halaman katalog/venue/acara tetap prototipe frontend; belum terhubung ke skema baru.
- Keputusan terbuka (B6 jumlah venue, B7 logika deadline, B11 booked, B12/B14) tidak dikodekan.

Pengujian aktual pada 2026-11-16:

- `php artisan test --compact` → **Lulus** (96 tests, 704 assertions; 58 BE-1 + 38 BE-2).
- `php artisan migrate` (additive, MySQL lokal `pesta_kita`) → 16 tabel BE-2 dibuat; `migrate:status` → 23 Ran (7 BE-1 batch [1] utuh + 16 BE-2 batch [2]).
- `php artisan db:seed` ×2 → idempoten di MySQL (jumlah record identik; user tetap 4).
- Reversibility: migrate + `migrate:rollback --step=16` pada SQLite sementara → 16 tabel BE-2 ter-drop urut FK-safe, 7 tabel BE-1 bertahan; file sementara dihapus, koneksi kembali ke MySQL.
- Pint (file PHP BE-2) → 64 isu gaya diperbaiki (line ending/EOF/brace/import); tes ulang tetap lulus.
- `git diff --check` → Lulus. HEAD tetap `50bb69f` (tanpa commit); perubahan baseline dipertahankan.
- Tidak ada screenshot; tidak ada perubahan dependensi/`.env`/Blade/CSS/JS.

Rincian: `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md`.

### 6.15 Hasil BE-3 — Media, Katalog, Venue, Verifikasi Mitra, Visibilitas Klien (Gate 4B) (2026-11-16)

Catatan batasan di bagian ini mencerminkan status saat BE-3 ditutup pada 2026-11-16; hasil tahap berikutnya ada di 6.16.

Lingkup yang disetujui pengguna: **BE-3 Gate 4B** (Gate 4A + rekomendasi §18 butir 1–10 disetujui). Lingkup inti A–G: media privat + unggah/hapus/stream berotorisasi, CRUD katalog vendor (layanan/peralatan), CRUD venue + ruangan/fasilitas, workflow verifikasi penyedia–Admin, endpoint visibilitas Klien, dan wiring Blade terbatas (20 view, tanpa redesign). Ditunda: kalender ketersediaan & pengajuan harga (BE-3b), tambah item ke acara draft (BE-4/BE-5). Keputusan diterapkan: B12/B14 (lihat `BACKEND_BUSINESS_RULES.md` bagian E/F), tanpa persetujuan Admin per item, venue mengikuti verifikasi profil + penonaktifan individual oleh Admin, `cleaning_fee` hanya Admin, bukti rekening bukan syarat verifikasi, satu dokumen aktif per jenis (ganti setelah unggah baru sukses), disk `private` `serve=false`.

Yang dikerjakan:

- **Migration (2, additive, reversible, batch [3])**: `2026_11_16_200001` media + kind/doc_type/disk/mime_type/size/original_name + index (owner_type, owner_id, kind); `2026_11_16_200002` vendor_profiles/venue_profiles + verification_note/reviewed_by (FK users nullOnDelete)/verification_submitted_at. `migrate:status` MySQL → 25 Ran; BE-1/BE-2 utuh.
- **Enum (3 baru)**: VerificationStatus, MediaKind, VerificationDocType (ktp/nib/npwp/bukti_venue).
- **Backend**: 8 controller baru (Media, Verification, Vendor\CatalogItem, Venue\Venue, Venue\VenueRoom, Admin\ProviderVerification, Admin\Venue, Klien\Catalog), 12 Form Request, 5 Policy terdaftar; disk `private` + `local` sama-sama `serve=false`; scope `publikUntukKlien` CatalogItem/Venue; approve mempublikasikan foto dan menyelaraskan venue (kecuali nonaktif individual), transaksi terkunci, audit log; tolak wajib alasan; status akun tidak tersentuh (T2).
- **Wiring Blade (20 view)**: vendor 6 (layanan, peralatan, item-form, peralatan-form, verifikasi, profil), venue 4 (data, form, verifikasi, profil), admin 3 (admin-penyedia, mitra-detail, admin-venue-form), klien 7 (katalog-vendor, katalog-peralatan, katalog, katalog-vendor-detail, katalog-venue-detail, penyedia-vendor, penyedia-venue). Tanpa redesign/CSS baru; form nyata menggantikan pratinjau `aksi.js` (atribut data-form/data-confirm dihapus pada form wired); null guard untuk render catch-all tanpa data; estimasi biaya Klien berlabel estimasi (komisi 10%); kontak pribadi tidak dirender; tombol "Tambahkan ke acara" diganti catatan penundaan BE-4/BE-5.
- **Tes (124 baru/diperbarui)**: Be3SchemaAdditionsTest, Be3PolicyTest, MediaManagementTest, CatalogCrudTest, VenueCrudTest, VerificationWorkflowTest, CatalogVisibilityTest + trait MakesUploadedFiles (biner base64, tanpa GD); ProjectPortalPagesTest/AccessibilityTest tetap hijau dengan view wired.

Batasan dan hal yang belum diselesaikan:

- Kalender ketersediaan, pengajuan harga (BE-3b); tambah item ke acara draft, pesanan, dan seluruh keuangan (BE-4+); stok peralatan dan UI rekening bank belum ada.
- Halaman di luar 20 view wiring tetap prototipe frontend; `klien/permintaan-penawaran`, kalender, rundown, notifikasi, dll. belum terhubung backend.
- Keputusan terbuka bagian B (selain B4/B12/B14/B15/B18) tidak dikodekan.

Pengujian aktual pada 2026-11-16:

- `php artisan test` → **Lulus** (220 tests, 1139 assertions; 96 baseline + 124 BE-3), dijalankan ulang setelah Pint tetap lulus.
- Checkpoint per grup wiring: vendor 61 lulus (439 assertions), venue 39 (358), admin 39 (358), klien 16 (268).
- `php artisan migrate` (MySQL lokal) → 2 migration BE-3 batch [3]; `migrate:status` → 25 Ran. Reversibility dibuktikan ulang pada SQLite sementara (rollback + migrate ulang 200001/200002); file sementara dihapus.
- Keamanan: kedua disk `serve=false`; tanpa `public/storage`; akses dokumen privat lintas peran ditolak (policy tests); kontak privat tidak bocor di detail Klien (assertDontSee).
- `php artisan view:cache` → seluruh Blade terkompilasi (lalu view:clear); `route:list` → 62 route; Pint → 38 isu gaya file BE-3 diperbaiki, `--test` lulus 142 file; `git diff --check` → Lulus. Tanpa commit; HEAD tetap.
- Perbaikan selama wiring: bug Blade `@endif@if(...)` bersebelahan tidak terkompilasi (dipisah spasi) pada katalog-vendor-detail/penyedia-venue; `Str::limit(null)` dicegah dengan `?? ''`.

Rincian: `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md`.

### 6.16 Hasil BE-4 — Wiring Request Quote (2026-11-17)

**Status: selesai — terverifikasi.** Alur quote Klien → Vendor/Venue → Admin dihubungkan ke backend dan delapan view quote menggunakan data server. Pengajuan quote menyimpan snapshot harga katalog; `event_id` hanya opsional untuk acara draft milik Klien dan tidak membuat acara. Proposal penyedia dibatasi pada subjek miliknya; nama Klien dan acara tidak ditampilkan di portal penyedia. Admin memutuskan proposal terakhir yang tersimpan, keputusan dicatat pada quote dan audit log dalam transaksi. Penolakan membutuhkan alasan; tiap quote hanya dapat diputuskan sekali dan tidak mendukung usulan ulang. Notifikasi dan alur acara/pesanan/keuangan belum termasuk.

Tes BE-4 dan regresi portal terarah: **40 tests / 454 assertions lulus**; suite penuh **289 tests / 1536 assertions lulus** pada 2026-11-17. `view:cache` berhasil lalu cache dibersihkan; `route:list` menampilkan 13 route quote. Pint dan `git diff --check` lulus. Rincian perubahan dan batasan ada di `docs/reports/BE-4_Implementation_Report_Pesta_Kita.md`.

## 7. Panduan Tahap 4 — Penyempurnaan Portal per Role

> **Status: selesai bersyarat (2026-11-15).** Lihat 6.5 untuk hasil aktual dan keterbatasan. Panduan di bawah dipertahankan sebagai lingkup dan batasan tahap.

Tahap 4 adalah tahap berikutnya menurut status historis. Sebelum mulai, baca kriteria penerimaan dan lingkup Tahap 4 dari roadmap aktual.

Fokus yang direncanakan:

- penyempurnaan navigasi;
- dashboard;
- halaman representatif Admin;
- halaman representatif Klien;
- halaman representatif Vendor;
- halaman representatif Pemilik Venue.

Ketentuan:

- lakukan audit terarah terhadap halaman/komponen yang akan disentuh;
- gunakan design tokens dan pola komponen yang sudah tersedia;
- pertahankan aksesibilitas Tahap 1 dan responsivitas Tahap 3;
- jangan mengerjakan Tahap 5–8 kecuali pengguna meminta;
- jangan membuat data atau aksi backend palsu;
- jika lingkup atau acceptance criteria roadmap tidak jelas, berhenti dan minta klarifikasi, bukan menebak.

## 8. Protokol Wajib Setiap Memulai Chat/Sesi TRAE

Lakukan langkah berikut secara berurutan:

1. Baca `docs/AI_AGENT_CONTEXT.md` terlebih dahulu.
2. Periksa apakah roadmap dan dokumen relevan tersedia.
3. Jalankan `git status --short`.
4. Periksa diff yang relevan sebelum mengedit; jangan menghapus atau menimpa perubahan lokal.
5. Verifikasi struktur, teknologi, file, route, selector, dan implementasi aktual yang terkait dengan tugas.
6. Baca hanya bagian roadmap/dokumen yang diperlukan. Jangan membaca seluruh repository atau seluruh dokumen tanpa alasan.
7. Pisahkan:
    - fakta yang sudah diverifikasi;
    - informasi historis dari dokumentasi;
    - hal yang belum diketahui;
    - asumsi yang tidak boleh dipakai sebagai dasar implementasi.
8. Nyatakan lingkup dan risiko singkat sebelum perubahan besar.
9. Kerjakan hanya tugas/tahap yang diminta.
10. Uji perubahan secara terarah.
11. Perbarui dokumen konteks sesuai aturan pada Bagian 9.
12. Laporkan hasil dan berhenti. Jangan otomatis melanjutkan tahap berikutnya.

## 9. Pemeliharaan `AI_AGENT_CONTEXT.md` — WAJIB

### 9.1 Kapan diperbarui?

Perbarui dokumen ini apabila:

- satu tahap roadmap selesai atau berubah status;
- pekerjaan penting mengubah kondisi proyek;
- struktur direktori, teknologi, atau arsitektur berubah;
- design system, komponen, atau pola implementasi berubah;
- ditemukan masalah, batasan, keputusan teknis, atau ketergantungan baru;
- status fitur, integrasi, atau pengujian berubah.

Perubahan kecil yang tidak memengaruhi konteks proyek tidak perlu dicatat.

### 9.2 Informasi yang wajib diperbarui

1. **Status roadmap:** selesai, selesai bersyarat, berjalan, belum dikerjakan, atau terblokir.
2. **Pekerjaan:** fitur/halaman/komponen yang diubah dan file utama terdampak.
3. **Kondisi implementasi:** fitur nyata, simulasi lokal, UI statis, belum tersedia, atau belum diverifikasi.
4. **Keputusan teknis:** keputusan baru, pola yang perlu dipertahankan, dan alasan.
5. **Pengujian:** perintah yang benar-benar dijalankan, hasil aktual, kegagalan, blocker, dan tes yang belum dilakukan.
6. **Masalah tersisa:** bug, risiko, ketergantungan, dan pekerjaan yang belum selesai.
7. **Titik awal berikutnya:** tahap selanjutnya, file/dokumen relevan, dan pemeriksaan awal yang dibutuhkan.

### 9.3 Aturan akurasi

- Perbarui berdasarkan repository aktif dan hasil verifikasi, bukan ingatan percakapan.
- Jangan menghapus catatan historis yang masih berguna; ubah statusnya dan pertahankan alasan keputusan.
- Bedakan jelas antara selesai, selesai bersyarat, belum selesai, terblokir, dan belum diverifikasi.
- Jangan mencatat fitur sebagai berfungsi jika baru UI, data statis, atau simulasi.
- Jangan mengarang hasil tes, API, route, database, atau status.
- Jangan mengubah aturan bisnis melalui pembaruan dokumentasi.
- Jaga dokumen utama tetap ringkas; simpan rincian panjang tiap tahap dalam `docs/reports/`.

### 9.4 Prosedur wajib sebelum mengakhiri tugas

1. Tinjau perubahan kode dan implementasi aktual.
2. Jalankan pengujian relevan dan catat hasil yang benar-benar terjadi.
3. Perbarui `docs/AI_AGENT_CONTEXT.md` jika pekerjaan memengaruhi konteks proyek.
4. Perbarui status roadmap bila ada perubahan status.
5. Simpan laporan tahap terperinci di `docs/reports/` bila hasilnya cukup besar atau menjadi catatan audit.
6. Pastikan dokumentasi tidak bertentangan dengan repository.
7. Jalankan `git diff --check` bila memungkinkan dan tinjau diff dokumentasi.
8. Sertakan file dokumentasi dalam daftar file yang diubah dan jelaskan pembaruannya pada laporan akhir.

Jika pembaruan tidak dapat dilakukan karena blocker, jelaskan alasannya secara eksplisit. Jangan mengklaim dokumen telah diperbarui.

### 9.5 Riwayat pembaruan

Tambahkan baris terbaru di bawah ini setiap kali konteks utama diperbarui secara bermakna.

| Tanggal    | Tahap/Pekerjaan                                                             | Status                                                 | Ringkasan                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| ---------- | --------------------------------------------------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2026-11-15 | Penyusunan instruksi konteks TRAE                                           | Dokumen diperbarui                                     | Menambahkan aturan pemeliharaan konteks dan protokol lintas chat; status implementasi tetap perlu diverifikasi di repository                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| 2026-11-15 | Tahap 4 — Penyempurnaan portal per role                                     | Selesai bersyarat                                      | Navigasi diseragamkan (`.role-chip` berbasis token), aksi utama dashboard, perbaikan halaman inti Admin/Klien/Vendor/Venue; tes `php artisan test --compact` 4 passed (210 assertions), `view:cache`, `node --check`, `git diff --check` lulus; rincian pada 6.5 dan `docs/reports/tahap-4-penyempurnaan-portal-per-role.md`                                                                                                                                                                                                                                                                                                                                                                                                                           |
| 2026-11-15 | Tahap 5.1 — Katalog vendor                                                  | Selesai bersyarat                                      | Halaman daftar & detail katalog vendor Klien (badge verifikasi berlabel contoh, harga tetap vs "Perlu penawaran", paket, kebijakan, galeri placeholder), CSS `katalog.css`, JS filter lokal `katalog-filter.js`; tes `php artisan test --compact` 4 passed (212 assertions), `view:cache`, `node --check`, `git diff --check` lulus; rincian pada 6.6 dan `docs/reports/tahap-5.1-katalog-vendor.md`                                                                                                                                                                                                                                                                                                                                                   |
| 2026-11-15 | Tahap 5.2 — Katalog venue                                                   | Selesai bersyarat                                      | Daftar venue dirombak (rincian biaya terpisah via `.cost-list`, badge verifikasi, pill jenis, filter lokal) + halaman **detail venue terpisah** (`katalog-venue-detail`: rincian biaya, aturan ruangan, kebijakan pembatalan "belum tersedia", ketersediaan "Perlu konfirmasi"); `katalog-detail` generik sengaja tidak diubah; klausa nav `klien/katalog-venue*` ditambahkan; tes `php artisan test --compact` 4 passed (214 assertions), `view:cache`, `node --check`, `git diff --check`, render-check lulus; rincian pada 6.7 dan `docs/reports/tahap-5.2-katalog-venue.md`                                                                                                                                                                        |
| 2026-11-15 | Tahap 5.3 — Profil dan portofolio penyedia                                  | Selesai bersyarat                                      | Halaman profil penyedia Klien baru (`klien/penyedia-vendor`, `klien/penyedia-venue`) dengan data prototipe & kontak pribadi disembunyikan; navigasi dua arah katalog↔profil; galeri portofolio responsif + empty state jujur; form UI kelola foto (pratinjau lokal `URL.createObjectURL`, tanpa unggah server) di `vendor/profil` & `venue/profil`; batas format/ukuran ditandai konfigurasi contoh; perbaikan setempat konten contoh menyesatkan pada `katalog-detail` (5.3.6); file baru `profil-media.css`/`profil-media.js`; tes `php artisan test --compact` 4 passed (218 assertions), `view:cache`, `node --check`, `git diff --check`, render-check 11 halaman lulus; rincian pada 6.8 dan `docs/reports/tahap-5.3-profil-portofolio.md`       |
| 2026-11-15 | Tahap 5.4 — Request Quote / Custom Quotation                                | Selesai bersyarat                                      | 8 view Blade baru (Klien ×2, Vendor ×2, Venue ×2, Admin ×2) untuk alur permintaan penawaran managed marketplace; harga katalog ≠ harga usulan penyedia ≠ harga final dibedakan tegas; komisi 10% ditambahkan ke tagihan Klien (bukan dipotong dari penyedia); form non-submit + penanda prototipe eksplisit (tanpa notifikasi sukses palsu); 6 file diubah (2 detail katalog Klien + 4 layout sidebar); tanpa perubahan route/controller/model/DB/API/middleware; tes `php artisan test --compact` 4 passed (234 assertions), `view:cache`, `node --check`, `git diff --check`, render-check 8 halaman lulus; rincian pada 6.9 dan `docs/reports/tahap-5.4-request-quote.md`                                                                           |
| 2026-11-15 | Tahap 5.5 — Kalender Ketersediaan                                           | Selesai bersyarat                                      | Kalender ketersediaan frontend untuk Klien/Vendor/Venue; 3 status (Tersedia/Tidak tersedia/Belum dikonfirmasi) dibedakan via warna + ikon + label teks; Klien pilih kategori+penyedia & tanggal diteruskan ke Request Quote via `?tanggal=`; Vendor/Venue kontrol prototype hanya ubah state memori (peringatan eksplisit, tanpa simpan server); Venue per ruangan; file baru `kalender.css`/`kalender.js` + 3 view + 3 layout diubah + 1 halaman permintaan-penawaran diubah; tanpa perubahan route/controller/model/DB/API/middleware; tes `php artisan test --compact` 4 passed (240 assertions), `view:cache`, `node --check`, `git diff --check`, render-check lulus; rincian pada 6.10 dan `docs/reports/tahap-5.5-kalender-ketersediaan.md`     |
| 2026-11-15 | Tahap 5.6 — Kriteria Penerimaan dan Evaluasi Tahap 5                        | Selesai — Diterima Bersyarat                           | Evaluasi menyeluruh 5.1–5.5 (tanpa redesign/fitur baru, tanpa memulai Tahap 6); status akhir **Diterima Bersyarat**; 2 perbaikan aman: data role pada `venue/profil` (Vendor→Venue) dan hierarki heading `admin/permintaan-penawaran` (`h3`→`h2`); keterbatasan utama: kalender belum membedakan "diblokir" vs "booked" (butuh backend), seluruh data statis tanpa backend; tes `php artisan test --compact` 4 passed (240 assertions), `view:cache`, `node --check` (4 file), `git diff --check` lulus; rincian pada 6.11 dan `docs/reports/tahap-5.6-kriteria-penerimaan-dan-evaluasi.md`                                                                                                                                                            |
| 2026-11-15 | Tahap 6 — Transaksi, tagihan, pembayaran, refund, pencairan                 | Selesai bersyarat (frontend)                           | UI keuangan empat portal ditandai prototipe; perhitungan refund lokal dan potongan komisi pada pencairan diperbaiki di tampilan. Backend transaksi belum ada. Rincian pada 6.12.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| 2026-11-15 | BE-0 — Audit dan rancangan backend                                          | Selesai sebagai dokumen. Implementasi belum disetujui. | Pemeriksaan ulang: Laravel 13.40.0, PHP 8.4.4, hanya tabel bawaan, database `pesta_kita` belum ada, portal GET tanpa auth. Rancangan ada di lima dokumen `docs/BACKEND_*.md`. Tidak ada migration atau endpoint baru.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| 2026-11-16 | Keputusan Batch 1 (B4, B15, B18, Laravel Boost, wiring frontend terbatas)   | Keputusan disetujui; implementasi kode belum           | Audit BE-0 read-only ulang mengonfirmasi kondisi backend; baseline `php artisan test --compact` 4 lulus (240 assertions). Pengguna menyetujui B4/B15/B18, mempertahankan Boost, dan wiring frontend terbatas (login/registrasi/profil/sandi). Rencana teknis BE-1 dan rancangan skema BE-2 disusun sebagai persiapan; tidak ada perubahan kode/migration/database/frontend. Rincian: `BACKEND_BUSINESS_RULES.md` bagian E dan addendum 2026-11-16 `BACKEND_AGENT_CONTEXT.md`.                                                                                                                                                                                                                                                                          |
| 2026-11-16 | BE-1 — Autentikasi, otorisasi, role, wiring frontend terbatas + Gate 2      | Selesai — terverifikasi                                | Implementasi penuh BE-1: enum + skema users/profil, login/register/logout (rate limit, pesan generik, T1/T2), middleware `EnsureRole` seluruh portal, profil/sandi, demo bar local-only (B18), wiring 10 file, seeder demo lokal idempoten. Gate 2 dilaksanakan: `pesta_kita` dibuat (utf8mb4_unicode_ci), 7 migration Ran, seed terverifikasi. Tes 58 lulus (616 assertions); route:list, migrate:status, view:cache, Pint, git diff --check lulus; tanpa perubahan dependensi/.env/commit. Rincian pada 6.13 dan `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md`.                                                                                                                                                                            |
| 2026-11-16 | BE-2 — Skema domain non-finansial (Gate 3)                                  | Selesai — terverifikasi                                | 16 migration additive FK-safe reversible + 16 model/relasi + 10 enum + 19 factory + perluasan LocalDemoSeeder (idempoten, local-only) + 38 integrity tests (14 kategori §8). Keputusan B4/B15/B5/B6/B7/B11/T2 diterapkan; tanpa events.venue_id; morph enforceMorphMap. Tes 96 lulus (704 assertions); migrate MySQL 23 Ran (BE-1 batch [1] utuh, BE-2 batch [2]); db:seed ×2 idempoten; rollback SQLite sementara membuktikan down() FK-safe; Pint 64 isu; git diff --check lulus; tanpa dependensi/.env/commit/Blade. Rincian pada 6.14 dan `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md`.                                                                                                                                                 |
| 2026-11-16 | BE-3 — Media, katalog, venue, verifikasi mitra, visibilitas Klien (Gate 4B) | Selesai — terverifikasi                                | 2 migration additive batch [3] (media kind/metadata; kolom reviewer/alasan/waktu pengajuan profil) + 3 enum + 8 controller + 12 Form Request + 5 Policy + disk private serve=false + scope publikUntukKlien + wiring 20 view Blade tanpa redesign (vendor 6, venue 4, admin 3, klien 7). Keputusan Gate 4B butir 1–10 diterapkan; B12/B14 diperbarui di BACKEND_BUSINESS_RULES (B/E/F). Tes 220 lulus (1139 assertions); migrate MySQL 25 Ran (BE-1/BE-2 utuh); reversibility SQLite sementara; view:cache, route:list 62, Pint 142 file, git diff --check lulus; tanpa dependensi/.env/commit. Sisa: BE-3b kalender & pengajuan harga, BE-4/BE-5 tambah item ke acara. Rincian pada 6.15 dan `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md`. |
| 2026-11-17 | BE-4 — Wiring Request Quote | Selesai — terverifikasi | Alur Klien→Vendor/Venue→Admin dihubungkan; quote menyimpan snapshot harga katalog, proposal, keputusan final, dan audit. Event draft opsional; tidak membuat acara. Tes terarah 40/454 dan suite penuh 289/1536 lulus; `view:cache`, `route:list` (13 route quote), Pint, dan pemeriksaan diff tercatat di laporan. Rincian di 6.16 dan `docs/reports/BE-4_Implementation_Report_Pesta_Kita.md`. |

## 10. Efisiensi Token dan Pencegahan Halusinasi

- Jangan membaca seluruh repository tanpa kebutuhan.
- Jangan mengulang audit yang sudah tersedia jika tidak relevan; verifikasi hanya bagian yang terkait dengan tugas.
- Gunakan pencarian terarah dan tampilkan output command secukupnya.
- Jangan mengulang isi dokumen konteks ke dalam chat kecuali dibutuhkan.
- Jangan membuat abstraksi atau komponen baru hanya demi preferensi pribadi.
- Jangan memperluas lingkup pekerjaan tanpa persetujuan.
- Jika bukti tidak cukup, katakan belum diketahui dan periksa sumber yang tepat.
- Jangan membuat screenshot.
- Jangan mengklaim keberhasilan sebelum command/test selesai dan hasilnya terlihat.

## 11. Format Laporan Akhir Wajib

Gunakan format ringkas berikut:

### Status

Selesai / Selesai bersyarat / Sebagian selesai / Terblokir.

### Lingkup

Ringkasan tugas yang dikerjakan dan yang sengaja tidak dikerjakan.

### File yang diubah

Daftar file dan tujuan perubahannya. Sertakan `docs/AI_AGENT_CONTEXT.md` jika diperbarui.

### Hasil verifikasi

| Pemeriksaan       | Hasil aktual                           |
| ----------------- | -------------------------------------- |
| Nama command/test | Lulus/Gagal/Terblokir/Tidak dijalankan |

Jangan menulis “lulus” jika tidak benar-benar dijalankan.

### Hal yang belum selesai

Daftar blocker, bug, risiko, atau bagian yang belum diverifikasi.

### Pembaruan konteks

Nyatakan bagian `AI_AGENT_CONTEXT.md` dan roadmap yang diperbarui, atau alasan belum dapat memperbaruinya.

### Langkah berikutnya

Sebutkan titik awal yang disarankan tanpa otomatis mengerjakan tahap berikutnya.

---

## 12. Template Prompt untuk Chat TRAE Baru

Salin prompt berikut dan isi bagian tugasnya:

```text
Baca `docs/AI_AGENT_CONTEXT.md` terlebih dahulu dan ikuti seluruh batasan di dalamnya.

Tugas saat ini: [jelaskan tugas spesifik].
Tahap roadmap: [nomor dan nama tahap].
Target halaman/komponen: [sebutkan jika diketahui].

Sebelum coding:
1. Periksa git status dan diff; jangan menghapus/menimpa perubahan lokal.
2. Verifikasi struktur dan implementasi aktual yang relevan.
3. Baca hanya bagian roadmap/dokumen yang diperlukan.
4. Nyatakan fakta terverifikasi, hal yang belum diketahui, dan risiko.

Laksanakan hanya lingkup tugas ini. Pertahankan teknologi, aturan bisnis, aksesibilitas, dan responsivitas yang sudah ada. Jangan mengarang API/data/status, jangan memasang dependency atau mengubah backend/database/route/environment tanpa izin. Jangan membuat screenshot.

Setelah selesai:
1. Jalankan tes relevan dan laporkan hasil aktual.
2. Perbarui `docs/AI_AGENT_CONTEXT.md` berdasarkan repository dan hasil verifikasi.
3. Perbarui status roadmap bila berubah.
4. Simpan laporan tahap di `docs/reports/` jika diperlukan.
5. Tinjau diff dan `git diff --check`.
6. Laporkan file yang diubah, hasil tes, blocker, risiko, dan pembaruan konteks sesuai format pada dokumen.

Jangan lanjut ke tahap berikutnya tanpa instruksi.
```

## 13. Prompt Siap Pakai untuk Memulai Tahap 4

```text
Baca `docs/AI_AGENT_CONTEXT.md` dan roadmap pembaruan frontend Pesta Kita.

Mulai Tahap 4 — Penyempurnaan Portal per Role. Fokus pada navigasi, dashboard, dan halaman representatif Admin, Klien, Vendor, serta Pemilik Venue sesuai ruang lingkup dan kriteria penerimaan di roadmap.

Mulai dengan audit terarah terhadap halaman dan komponen yang akan disentuh. Periksa git status/diff dan pertahankan seluruh perubahan lokal. Gunakan design tokens dan pola komponen yang sudah tersedia. Pertahankan aksesibilitas dan responsivitas hasil Tahap 1–3.

Jangan membuat fitur/backend/data palsu, jangan mengubah aturan bisnis, dan jangan mengerjakan Tahap 5–8. Jangan membuat screenshot. Kerjakan bertahap dan uji setiap bagian.

Setelah pekerjaan selesai, WAJIB perbarui `docs/AI_AGENT_CONTEXT.md` berdasarkan kondisi repository dan hasil pengujian aktual. Perbarui status roadmap, catat pekerjaan, file yang berubah, keputusan teknis, hasil tes, blocker, risiko, dan titik awal berikutnya. Simpan laporan terperinci di `docs/reports/` bila diperlukan.

Jika roadmap tidak tersedia atau lingkupnya ambigu, jangan menebak—laporkan bagian yang dibutuhkan. Jangan lanjut ke tahap berikutnya tanpa instruksi.
```

## 14. Dokumentasi backend

Audit BE-0 (2026-11-15) memeriksa repository dan tidak mengubah kode aplikasi. Frontend Tahap 4, 5.1–5.6, dan 6 tetap acuan kebutuhan tampilan. **Per 2026-11-17, BE-1 (auth/role), BE-2 (skema domain), BE-3 (media/katalog/verifikasi), BE-3b (kalender/pengajuan harga), dan BE-4 (alur quote) telah diimplementasikan dan terverifikasi sesuai laporan masing-masing.** BE-5 (acara/pesanan) dan BE-6–BE-7 (keuangan, rundown, ulasan) belum dilaksanakan. Lihat 6.13–6.16.

| Kebutuhan                                           | Dokumen                                                 |
| --------------------------------------------------- | ------------------------------------------------------- |
| Kondisi server, auth, dan database yang terbukti    | `docs/BACKEND_AGENT_CONTEXT.md`                         |
| Aturan terkunci dan keputusan yang belum dijawab    | `docs/BACKEND_BUSINESS_RULES.md`                        |
| Skema domain (BE-2 sudah dimigrasi; keuangan belum) | `docs/BACKEND_DATABASE_DESIGN.md`                       |
| Data per halaman dan route usulan                   | `docs/BACKEND_API_CONTRACT.md`                          |
| Urutan BE-1 sampai BE-8                             | `docs/BACKEND_IMPLEMENTATION_PLAN.md`                   |
| Hasil implementasi BE-1 + Gate 2 (2026-11-16)       | `docs/reports/BE-1_Implementation_Report_Pesta_Kita.md` |
| Hasil implementasi BE-2 + Gate 3 (2026-11-16)       | `docs/reports/BE-2_Implementation_Report_Pesta_Kita.md` |
| Persiapan BE-3 + Gate 4A (2026-11-16)               | `docs/reports/BE-3_Preparation_Report_Pesta_Kita.md`    |
| Hasil implementasi BE-3 + Gate 4B (2026-11-16)      | `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md` |
| Hasil implementasi BE-3b (2026-11-16)               | `docs/reports/BE-3b_Wiring_Report_Pesta_Kita.md`        |
| Hasil implementasi BE-4 (2026-11-17)                | `docs/reports/BE-4_Implementation_Report_Pesta_Kita.md` |

BE-1, BE-2, BE-3, BE-3b, dan BE-4 sudah dilaksanakan; jangan mengulang implementasinya. Tahap berikutnya adalah BE-5, yang tetap menunggu instruksi eksplisit pengguna dan konfirmasi keputusan B7. Jangan memperlakukan route usulan BE-5+ sebagai endpoint yang sudah terpasang.
