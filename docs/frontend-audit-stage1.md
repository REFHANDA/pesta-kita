# Laporan Eksekusi — Tahap 1: Perbaikan Blocker UI/UX dan Aksesibilitas

**Tanggal pelaksanaan:** 13 November 2026 (WIB)  
**Cakupan:** portal Admin, Klien, Vendor, dan Pemilik Venue; kontrol drawer, dialog, accessible name, target sentuh, dan navigasi keyboard.  
**Acuan:** [`frontend-audit-baseline.md`](./frontend-audit-baseline.md) dan instruksi Tahap 1. Dokumen panduan dan roadmap yang disebutkan tidak ditemukan di repository saat pemeriksaan.

## 1. Status

**SELESAI — Tahap 1 selesai. Tidak melanjutkan ke Tahap 2.**

## 2. Ringkasan

- Drawer seluruh role memakai tombol semantik bernama, status `aria-expanded`, kontrol tutup, fokus masuk, focus trap, Escape, dan pengembalian fokus. Konten latar dibuat inert saat drawer terbuka.
- Dialog konfirmasi bersama dan dialog keputusan Klien mengumumkan judul/deskripsi, menerima fokus awal, ditutup dengan Escape, dan memulihkan fokus. Menu aksi dibuka kembali saat fokus dikembalikan ke pemicu di dalam menu.
- Form yang diperiksa diberi nama aksesibel; audit browser pada **90 halaman role yang dapat dijangkau dari navigasi** tidak menemukan tombol, tautan, atau kontrol formulir tanpa accessible name.
- Target kontrol sentuh yang diperiksa pada viewport 375 px dinaikkan menjadi 44 px. Pemeriksaan menemukan target menu aksi Admin berukuran 36 px pada awalnya; override responsif spesifik ditambahkan dan pemeriksaan ulang tidak menemukan target terpilih di bawah 44 px.
- Hierarki dan konten utama pada screenshot 375 px untuk keempat role tetap utuh dibanding baseline; tidak ditemukan overflow horizontal. Perbandingan dilakukan secara visual, bukan pixel-diff.
- Tidak ada perubahan aturan bisnis, data, database, konfigurasi environment, maupun dependency.

## 3. File yang diubah

- Shell portal dan drawer: [`layouts/admin.blade.php`](../resources/views/layouts/admin.blade.php), [`layouts/klien.blade.php`](../resources/views/layouts/klien.blade.php), [`layouts/vendor.blade.php`](../resources/views/layouts/vendor.blade.php), [`layouts/venue.blade.php`](../resources/views/layouts/venue.blade.php), dan [`demo-bar.blade.php`](../resources/views/partials/demo-bar.blade.php).
- Perilaku bersama, drawer, dan styling: [`app-ui.js`](../public/assets/js/app-ui.js), [`aksi.js`](../public/assets/js/aksi.js), [`admin.css`](../public/assets/css/admin.css), dan [`keselarasan.css`](../public/assets/css/keselarasan.css).
- Label formulir Admin: [`acara.blade.php`](../resources/views/admin/acara.blade.php), [`admin-penyedia.blade.php`](../resources/views/admin/admin-penyedia.blade.php), [`aktivitas.blade.php`](../resources/views/admin/aktivitas.blade.php), [`evaluasi.blade.php`](../resources/views/admin/evaluasi.blade.php), [`katalog.blade.php`](../resources/views/admin/katalog.blade.php), [`pembayaran.blade.php`](../resources/views/admin/pembayaran.blade.php), dan [`rundown.blade.php`](../resources/views/admin/rundown.blade.php).
- Form vendor/venue dan dialog Klien: [`pengajuan-harga.blade.php`](../resources/views/vendor/pengajuan-harga.blade.php), [`peralatan.blade.php`](../resources/views/vendor/peralatan.blade.php), [`peralatan-form.blade.php`](../resources/views/vendor/peralatan-form.blade.php), [`rundown.blade.php`](../resources/views/vendor/rundown.blade.php), [`pengajuan-harga.blade.php`](../resources/views/venue/pengajuan-harga.blade.php), [`rundown.blade.php`](../resources/views/venue/rundown.blade.php), dan [`acara-diajukan.blade.php`](../resources/views/klien/acara-diajukan.blade.php).
- Regresi drawer: [`ProjectPortalAccessibilityTest.php`](../tests/Feature/ProjectPortalAccessibilityTest.php).

## 4. Fitur yang berhasil diterapkan

### Drawer dan navigasi keyboard

- Mengganti kontrol checkbox/label drawer dengan tombol `Buka menu navigasi` yang memiliki `aria-controls` dan `aria-expanded`.
- Menambahkan tombol `Tutup menu navigasi`, overlay dekoratif, dan semantik modal saat drawer terbuka.
- Fokus dipindahkan ke navigasi, Tab/Shift+Tab dibatasi di drawer, Escape/tombol tutup/overlay menutup drawer, dan fokus kembali ke pemicu.
- Drawer tertutup disembunyikan dari navigasi aksesibel pada breakpoint yang sama dengan layout drawer, termasuk lebar 901–1024 px.

### Dialog, nama aksesibel, dan target sentuh

- Dialog bersama menautkan judul dan deskripsi, memilih fokus awal, menangani Escape eksplisit, serta memulihkan fokus pemicu.
- Dialog Klien yang memang aktif memulihkan fokus saat Escape dan setelah pilihan dikonfirmasi. Dialog markup pada halaman pengajuan pembatalan yang tidak terhubung tidak diaktifkan agar tidak menambah workflow.
- Menautkan label ke kontrol formulir dan memberi nama pada kontrol pencarian/filter yang sebelumnya tidak memiliki label.
- Menetapkan target 44 px untuk kontrol sentuh mobile yang diperiksa, termasuk tombol ringkas, tab, pagination, dan menu aksi tabel.
- Mempertahankan indikator `:focus-visible` yang ada; verifikasi keyboard browser menampilkan outline saat fokus keyboard.

## 5. Ketergantungan backend

Tidak ada perubahan backend. Sesuai baseline, data portal dan aksi bisnis tetap berupa demo statis/client-side; Tahap 1 tidak mengubahnya menjadi transaksi nyata atau menambah endpoint. Kekurangan autentikasi/otorisasi dan persistence pada baseline tetap berada di luar cakupan ini.

## 6. Pengujian

| Pemeriksaan | Hasil | Catatan |
|---|---|---|
| `php artisan test --compact` | **LULUS** — 4 tes, 210 assertion. | Seluruh regresi Laravel yang tersedia. |
| `php artisan view:cache` | **LULUS**. | Template Blade berhasil dikompilasi. |
| `node --check` untuk `aksi.js` dan `app-ui.js` | **LULUS**. | Sintaks JavaScript. |
| `git diff --check` | **LULUS**. | Spasi akhir diperbaiki pada dua layout yang disentuh. |
| Browser — drawer | **LULUS**. | Empat role pada 375 px; Admin juga pada 1000 px. Diuji fokus masuk, inert latar, Tab/Shift+Tab melingkar, Escape, tombol tutup, overlay, dan pengembalian fokus. |
| Browser — dialog | **LULUS**. | Dialog konfirmasi bersama dan dialog Klien diuji untuk fokus awal, Escape, konfirmasi, dan pemulihan fokus. |
| Audit accessible name | **LULUS pada cakupan crawl**. | 90 halaman yang dijangkau dari navigasi role; 0 kontrol tanpa nama aksesibel dan 0 kegagalan HTTP. |
| Target sentuh mobile | **LULUS pada kontrol/halaman sampel**. | Pemeriksaan ulang pada dashboard empat role, Admin pengguna, Vendor peralatan/pengajuan harga, dan rundown Venue tidak menemukan target terpilih di bawah 44 px. |
| Responsif dashboard | **LULUS**. | `/admin`, `/klien`, `/vendor`, `/venue` pada 1440, 768, dan 375 px: tidak ada horizontal overflow. |
| Screenshot baseline | **DIBANDINGKAN**. | Pemeriksaan visual dashboard empat role pada 375 px; file baseline tetap tidak diubah. |

Tidak dilakukan audit screen reader penuh atau audit konformansi WCAG; hasil di atas tidak menyatakan bahwa WCAG terpenuhi. Build Vite tidak dijalankan karena asset portal menggunakan CSS/JS statis secara langsung dan dependency frontend lokal tidak tersedia pada baseline.

## 7. Risiko atau masalah tersisa

- Dialog pada halaman Klien `acara-batalkan` masih berupa markup yang tidak terhubung ke aksi. Dialog tersebut sengaja tidak diaktifkan karena tindakan itu akan mengubah alur yang tersedia.
- Belum dilakukan pengujian dengan screen reader maupun pemeriksaan kontras menyeluruh pada seluruh kombinasi warna dan status interaksi. Tidak ada token warna yang diubah pada tahap ini.
- Temuan baseline tentang akses portal tanpa autentikasi/otorisasi, data statis, dan ketiadaan API domain tetap berlaku.
- Dokumen panduan dan roadmap yang diminta tidak tersedia di repository pada saat pelaksanaan; laporan ini mengikuti struktur laporan baseline dan batasan eksplisit instruksi Tahap 1.

## 8. Status tahap berikutnya

Tahap 1 berhenti di sini sesuai instruksi. **Menunggu arahan pengguna sebelum memulai Tahap 2.**
