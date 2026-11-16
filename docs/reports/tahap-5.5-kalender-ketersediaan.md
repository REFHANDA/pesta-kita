# Laporan Tahap 5.5 — Kalender Ketersediaan (Availability Calendar)

Tanggal: 2026-11-15

Tahap roadmap: **5 — Katalog, Profil Penyedia, dan Ketersediaan** (`docs/roadmap_pembaruan_frontend_pesta_kita.md`, bagian "TAHAP 5", subtahap "5.5 Kalender ketersediaan"). Instruksi resmi tahap ini adalah dokumen `INSTRUKSI EKSEKUSI TRAE AI AGENT — Tahap 5.5 Kalender Ketersediaan (Availability Calendar)`.

Catatan penomoran: penomoran Tahap 5 telah diselaraskan ke standar final (5.1 Katalog Vendor, 5.2 Katalog Venue, 5.3 Profil dan Portofolio Penyedia, 5.4 Request Quote, 5.5 Kalender Ketersediaan, 5.6 Kriteria Penerimaan dan Evaluasi). Lihat catatan penyelarasan di `docs/roadmap_pembaruan_frontend_pesta_kita.md`.

Status: **Selesai bersyarat** (frontend selesai; backend belum tersedia).

---

## 1. Lingkup

### Dikerjakan
Antarmuka **Kalender Ketersediaan** untuk tiga portal: **Klien**, **Vendor**, dan **Pemilik Venue** (Admin tidak memerlukan fitur khusus).

Tiga status ketersediaan yang dibedakan melalui **warna + ikon + label teks** (bukan warna saja):
- **Tersedia** — hijau, ikon ✓, badge `.b-ok`.
- **Tidak tersedia** — merah, ikon ✕, badge `.b-bad`.
- **Belum dikonfirmasi / Sedang ditinjau** — netral, ikon ?, badge `.b-warn`.

Fitur per portal:
- **Klien:** pemilihan kategori (Vendor/Venue) + penyedia, navigasi bulan, kalender bulanan, legenda, detail tanggal terpilih, empty state, dan kaitan tanggal terpilih ke alur Request Quote (5.4) melalui parameter `?tanggal=` — tanpa membuat permintaan nyata.
- **Vendor:** kalender bulanan, legenda, daftar tanggal berstatus, detail tanggal, dan kontrol prototype (mengubah status di memori) dengan peringatan eksplisit bahwa perubahan hanya berlaku pada tampilan prototype.
- **Pemilik Venue:** seperti Vendor, tetapi **per ruangan** (ballroom, aula, taman, ruang VIP). Tidak diasumsikan satu venue = satu jadwal, dan tidak diasumsikan semua ruangan berstatus sama.

### Sengaja tidak dikerjakan
- Subtahap **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5)**.
- Backend, database/migration, route, controller, model, API, autentikasi, middleware, `.env`.
- Penyimpanan jadwal/ketersediaan ke server; endpoint baru.
- Notifikasi keberhasilan palsu.
- Perubahan pada `routes/web.php`.
- Screenshot (sesuai kebijakan: tidak membuat screenshot).

---

## 2. Keputusan penting

### 2.1 Auto-routing portal (tanpa perubahan route)
`routes/web.php` memakai `Route::get('/{page}', ...)` per prefix (`klien`, `vendor`, `venue`, `admin`) dengan `view()->exists()`, sehingga tiga view Blade baru otomatis dapat diakses **tanpa perubahan route** (sesuai batasan "jangan mengubah route").

### 2.2 Konfigurasi data via JSON inline (bukan backend)
Data contoh kalender ditempatkan sebagai JSON di `<script type="application/json" id="kal-config">` di dalam Blade — bukan request server. `kalender.js` membacanya untuk merender grid. Ini menjaga sifat prototipe tanpa membuat kesan terhubung database.

### 2.3 State perubahan hanya di memori
Kontrol prototype pada Vendor/Venue mengubah `dataset.days` di memori JavaScript saja. Perubahan hilang saat halaman dimuat ulang; tidak ada penyimpanan maupun notifikasi sukses.

### 2.4 Status dibedakan tanpa mengandalkan warna
Setiap sel tanggal memiliki ikon penanda (✓ / ✕ / ?) dan `aria-label` yang menyebut status lengkap; legenda dan badge menyertakan label teks. Ini memenuhi aturan Tahap 1.4.

### 2.5 Aksesibilitas seleksi
Tombol tanggal memakai `aria-pressed` untuk status terpilih; penanda seleksi memakai `box-shadow` ring dalam (`.kal-day.is-sel`) agar tidak bertumpuk dengan focus outline bawaan.

---

## 3. File yang dibuat dan diubah

### Dibuat
| File | Tujuan |
|---|---|
| `public/assets/css/kalender.css` | Gaya kalender (`.kal-toolbar`, `.kal-nav`, `.kal-title`, `.kal-table`, `.kal-day`, `.kal-num`, `.kal-mark`, `.kal-ok/.kal-bad/.kal-warn`, `.kal-day.is-sel`, `.kal-fill`, `.kal-legend`, `.kal-key`, `.kal-nodata`, `.kal-date`, `.kal-edit`, `.kal-list`). Token `--pk-*`; responsif @760px & @420px; `table-layout:fixed` mencegah overflow. |
| `public/assets/js/kalender.js` | Logika kalender Vanilla JS (IIFE): render grid (minggu mulai Senin), navigasi bulan, detail tanggal, daftar tanggal berstatus, empty state, picker/kategori, kontrol prototype memori. |
| `resources/views/klien/kalender-ketersediaan.blade.php` | Kalender Klien (`editable:false`): kategori + pemilih penyedia, legend, detail tanggal, kartu Ajukan permintaan penawaran (`#kal-quote-link` meneruskan `?tanggal=`), `.data-note` penanda contoh. |
| `resources/views/vendor/kalender-ketersediaan.blade.php` | Kalender Vendor (`editable:true`): pemilih layanan, kartu kontrol prototype (`[data-kal-editable]`, `[data-kal-set]`, `.warn-box`), kartu "Daftar tanggal berstatus" (`#kal-list`), `#kal-nodata`. |
| `resources/views/venue/kalender-ketersediaan.blade.php` | Kalender Venue (`editable:true`) **per ruangan**; hint menegaskan tiap ruangan punya jadwal berbeda; kontrol prototype + daftar tanggal. |

### Diubah
| File | Tujuan |
|---|---|
| `resources/views/layouts/klien.blade.php` | Menu "Kalender ketersediaan" + klausa `aria-current` `klien/kalender-ketersediaan*`. |
| `resources/views/layouts/vendor.blade.php` | Menu "Kalender Ketersediaan" + klausa `aria-current` `vendor/kalender-ketersediaan*`. |
| `resources/views/layouts/venue.blade.php` | Menu "Kalender Ketersediaan" + klausa `aria-current` `venue/kalender-ketersediaan*`. |
| `resources/views/klien/permintaan-penawaran.blade.php` | Kaitan 5.5→5.4: bila `request()->filled('tanggal')`, tampil `.alert` "Tanggal pilihan dari kalender ketersediaan: …" (tanggal hanya usulan tampilan, belum tersimpan, belum dipesan) dan input `#tanggal` menampilkan tanggal tersebut. |

### Dokumentasi
| File | Tujuan |
|---|---|
| `docs/AI_AGENT_CONTEXT.md` | Tabel status roadmap Tahap 5, bagian **6.10**, dan baris riwayat 9.5. |
| `docs/roadmap_pembaruan_frontend_pesta_kita.md` | Status subtahap 5.5 → Selesai bersyarat + catatan status pada bagian 5.5. |
| `docs/reports/tahap-5.5-kalender-ketersediaan.md` | Laporan ini. |

Tidak ada perubahan pada `routes/web.php`, controller, model, migration, `.env`, atau aset inti.

---

## 4. Alur Klien

- **`/klien/kalender-ketersediaan`**: pilih kategori (Vendor/Venue) → daftar penyedia diperbarui; pilih penyedia → kalender bulanan dirender. Tiga status dibedakan via kelas `.kal-ok/.kal-bad/.kal-warn` + ikon + `aria-label`. Legend di atas grid; `#kal-nodata` bila penyedia belum punya data (mis. contoh tanpa jadwal). Memilih tanggal mengisi panel "Detail tanggal".
- Kartu "Ajukan permintaan penawaran": `#kal-quote-link` (`data-base="{{ url('/klien/permintaan-penawaran') }}"`) diubah href-nya menjadi `?tanggal=<date>` saat tanggal dipilih — tanpa mengirim permintaan nyata.
- Halaman `.data-note` menegaskan seluruh data penyedia/jadwal adalah contoh prototipe.

## 5. Alur Vendor

- **`/vendor/kalender-ketersediaan`**: pilih layanan → kalender dirender. Legend + daftar tanggal berstatus (`#kal-list`). Memilih tanggal menampilkan detail.
- Kartu kontrol prototype (`[data-kal-editable]`): tombol `[data-kal-set]` mengubah status tanggal terpilih di memori. `.warn-box`: **"Perubahan jadwal ini hanya berlaku pada tampilan prototype dan belum tersimpan ke server."**
- `.alert` menandai seluruh data sebagai contoh; bila layanan belum punya jadwal, `#kal-nodata` tampil.

## 6. Alur Pemilik Venue

- **`/venue/kalender-ketersediaan`**: pemilih **ruangan** (ballroom/aula/taman/ruang VIP), masing-masing punya dataset `days` sendiri. Hint menegaskan tiap ruangan memiliki jadwal berbeda.
- Kontrol prototype + daftar tanggal + warning sejenis Vendor. Tidak ada asumsi semua ruangan berstatus sama.

---

## 7. Hasil pengujian aktual

| Pemeriksaan | Hasil aktual |
|---|---|
| `php artisan test --compact` | **Lulus** — 4 tests, 240 assertions (naik dari 234 karena 3 view baru). `ProjectPortalPagesTest` otomatis menguji setiap view role termasuk ketiga halaman kalender (`assertOk` + larangan tautan `.html`). |
| `php artisan view:cache` | **Lulus** — Blade templates cached successfully. |
| `node --check` (`kalender.js`, `katalog-filter.js`, `aksi.js`, `app-ui.js`) | **Lulus** (tanpa error). |
| `git diff --check` | **Lulus** (exit code 0, tanpa whitespace error). |
| Render-check kernel (`php artisan serve` + `Invoke-WebRequest`) | **Lulus** — 200 pada `/klien/kalender-ketersediaan` (marker `kal-config` + `kal-quote-link`), `/vendor/kalender-ketersediaan`, `/venue/kalender-ketersediaan` (marker `kal-config`), `/klien/permintaan-penawaran` (200 tanpa alert), dan `/klien/permintaan-penawaran?tanggal=...` (200 dengan alert "Tanggal pilihan dari kalender"). |

Tidak ada screenshot.

---

## 8. Pemeriksaan regresi

- **Halaman 5.1–5.4 & dashboard tiap role:** semua 200; tidak ada perubahan struktur di luar penambahan menu nav dan alert kontekstual pada `klien/permintaan-penawaran`.
- **Navigasi sidebar (Klien/Vendor/Venue):** `ProjectPortalAccessibilityTest` lulus; menu baru memakai klausa `aria-current` yang konsisten.
- **Tautan `.html`:** tidak ada (dilarang oleh `ProjectPortalPagesTest`).
- **Aset inti:** `design-tokens.css`, `admin.css`, `klien.css`, `penyedia.css`, `katalog.css`, `app-ui.js`, `aksi.js` tidak diubah.

---

## 9. Kebutuhan backend yang belum tersedia

1. **Endpoint ketersediaan/booking** per penyedia/layanan/ruangan — saat ini data contoh di Blade.
2. **Sumber tanggal berstatus** (tersedia/tidak/belum dikonfirmasi) dari sistem.
3. **Penyimpanan perubahan jadwal penyedia** (Vendor/Venue) — belum ada.
4. **Waktu pembaruan data ketersediaan** (last updated) bila dibutuhkan.
5. **Perlindungan route** (auth/role middleware) — route portal masih belum dilindungi.
6. **Instant Book** — sengaja tidak diterapkan sampai data kalender terbukti akurat.

---

## 10. Risiko / pekerjaan yang belum selesai

- Seluruh UI 5.5 masih statis/prototipe; perubahan jadwal hanya di memori dan hilang saat reload.
- Data tanggal berstatus adalah contoh yang dikonfigurasi di Blade, bukan hasil query.
- Belum ada integrasi ke alur pemesanan nyata; kaitan ke Request Quote hanya pengisian parameter tampilan.
- Subtahap **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5)** belum dikerjakan.

---

## 11. Titik awal berikutnya

Tahap **5.6 (Kriteria Penerimaan dan Evaluasi Tahap 5)** menurut roadmap, **hanya setelah persetujuan pengguna**. **Berhenti di sini** — jangan melanjutkan tanpa instruksi.
