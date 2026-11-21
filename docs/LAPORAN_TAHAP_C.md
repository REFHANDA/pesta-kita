# Laporan Tahap C — Rundown Acara dan Evaluasi

Tanggal: 21 November 2026
Branch: `sinkronisasi-rancangan`
Basis: ZIP Tahap B (`pesta-kita-tahap-b.zip`), PHP 8.4.23, Laravel 13, SQLite untuk tes.

## Ringkasan

| Sub-tahap | Isi | Commit |
|---|---|---|
| C0 | Pemeriksaan kondisi awal | — |
| C1 | `RundownService`, `RundownItemPolicy`, matriks akses | `C1: RundownService dan policy + tes matriks akses` |
| C2 | Rundown di detail acara Klien | `C2: rundown di detail acara klien` |
| C3 | Rundown Vendor, Venue (hanya lihat), Admin | `C3: rundown vendor, venue (hanya lihat), dan Admin` |
| C4 | Evaluasi final satu kali | `C4: evaluasi acara oleh klien` |
| C5 | `RatingSummary`, kartu dan daftar rating | `C5: hasil evaluasi, rating, dan kartu dashboard` |
| C6 | Notifikasi rundown dan evaluasi | `C6: notifikasi rundown dan evaluasi` |
| C7 | Tes lintas fitur dan dokumen | `C7: tes lintas fitur, dokumen, dan verifikasi Tahap C` |

Hasil tes akhir seluruh proyek: **510 tes lulus, 3.285 assertion** (Tahap B: 445 tes / 2.903 assertion).

## C0 — Kondisi awal yang ditemukan

- Tabel `rundown_items`, `evaluations`, `evaluation_items` **sudah ada** pada migrasi Tahap A; tidak ada migrasi baru di Tahap C.
- `/klien/rundown`, `/klien/evaluasi`, `/klien/rekomendasi`, `/vendor/rundown`, `/venue/rundown` masih `Route::view(...)` dengan teks "Belum ada data.".
- Belum ada `RundownService`, `RundownItemPolicy`, `EvaluationService`, `RatingSummary`, maupun notifikasi rundown/evaluasi.
- `RundownItem` belum dipakai di halaman mana pun; belum ada tautan menu ke halaman tersebut.

## C1 — Aturan dan matriks akses

Seluruh aturan berada di `app/Services/RundownService.php`; `RundownItemPolicy` hanya mendelegasikan. Matriks dicetak oleh tes dan hasil nyata sama dengan tabel instruksi:

```
Aktor                     diajukan | terkonfirmasi  | selesai        | dievaluasi     | dibatalkan
Klien pemilik             -        | lihat / tulis  | lihat          | lihat          | -
Klien lain                -        | -              | -              | -              | -
Vendor pemilik pesanan    -        | lihat / tulis  | lihat          | lihat          | -
Vendor lain               -        | -              | -              | -              | -
Venue pemilik pesanan     -        | lihat          | lihat          | lihat          | -
Admin                     lihat    | lihat          | lihat          | lihat          | lihat
```

Keputusan yang diambil:
- **Admin** melihat semua acara non-draft (termasuk `dibatalkan`), konsisten dengan kolom "semua" pada tabel instruksi. Admin tidak memiliki route tulis rundown.
- **Vendor** wajib menyertakan `order_item_id` miliknya yang `terkonfirmasi`; item tanpa tautan ditolak 422.
- Item vendor lain / item klien tidak dapat diubah vendor (403).
- Batas 100 item, format `HH:MM`, `end_time > start_time` divalidasi di FormRequest **dan** di service (pertahanan berlapis).

Tes: `tests/Feature/Rundown/RundownRulesTest.php` (11 tes) termasuk cetakan matriks, batas 100, kepemilikan, dan audit tanpa data uang.

## C2 — Klien

- Route eksplisit: `klien.acara.rundown.index|store|update|destroy`.
- Bagian "Rundown" di dalam `resources/views/klien/acara-detail.blade.php`; tanpa menu baru.
- `Route::view('/rundown')` dan `klien/rundown.blade.php` dihapus; `/klien/rundown` → 404.
- Klien lain: detail 404, aksi tulis 403.
- Item buatan vendor ditandai "Dibuat oleh vendor"; nama usaha vendor tampil, kontak tidak.

Tes: `tests/Feature/Rundown/KlienRundownTest.php` (7 tes).

## C3 — Vendor, Venue, Admin

- Vendor: `vendor.rundown`, `vendor.rundown.show`, `store`, `update`, `destroy`. Daftar hanya acara dengan pesanan `terkonfirmasi`/`selesai` miliknya.
- Venue: `venue.rundown`, `venue.rundown.show` saja. Tes memastikan **tidak ada** route POST/PUT/PATCH/DELETE di `venue/rundown`.
- Admin: tab "Rundown" pada detail acara, hanya lihat.
- Semua halaman penyedia memakai "Acara #ID"; tidak memuat nama/kontak klien.

Tes: `tests/Feature/Rundown/ProviderRundownTest.php` (7 tes).

## C4 — Evaluasi

- `EvaluationService` menghitung subjek di server: satu venue, satu per vendor (bukan per layanan), satu Admin; hanya pesanan berstatus `selesai`.
- Satu evaluasi per acara, final. Kiriman kedua → **409**. Subjek kurang/asing → 422 tanpa menyimpan apa pun.
- Status `selesai` → `dievaluasi`, `AuditLog` `event_evaluated`, tidak menyentuh `invoices`, `payments`, `disbursements`, `cancellations` (dibuktikan snapshot tes).
- Form bintang 1–5 berupa radio aksesibel (`label` + teks tersembunyi "bintang n dari 5").

Tes: `tests/Feature/Rundown/KlienEvaluationTest.php` (7 tes).

## C5 — Rating

- `RatingSummary`: rata-rata satu desimal dari server, `null` bila belum ada; agregasi batch untuk banyak penyedia.
- Admin: kartu "Rating Admin" (rata-rata + jumlah) dan tab "Evaluasi" pada daftar acara (`?tab=evaluasi`), detail acara menampilkan seluruh item evaluasi, komentar, `system_feedback`, dan nama klien.
- Vendor/Venue: kartu rating + daftar penilaian terbaru; **tanpa** nama klien dan **tanpa** `system_feedback`; hanya data miliknya.
- Empty state eksplisit ("Belum ada penilaian untuk Admin.", "Belum ada penilaian").

Tes: `tests/Feature/Rundown/RatingSummaryTest.php` (6 tes, termasuk pembulatan 4,3 dari 5/4/4).

## C6 — Notifikasi

- `RundownUpdatedNotification`: klien → penyedia dengan pesanan terkonfirmasi/selesai; vendor → klien.
- Digabung: selama notifikasi sejenis untuk acara dan penerima sama masih **belum dibaca**, perubahan berikutnya tidak membuat baris baru. Setelah dibaca, perubahan baru membuat notifikasi baru.
- `EvaluationSubmittedNotification`: ke Admin dan tiap penyedia yang dinilai; payload penyedia tanpa nama klien dan tanpa isi komentar.
- Tautan notifikasi memakai `url` eksplisit (`#rundown`, `vendor.rundown.show`, `venue.rundown.show`, `admin.acara.detail#evaluasi`).
- Judul baru di `NotificationController::TITLES`: "Rundown diperbarui", "Evaluasi acara".

Tes: `tests/Feature/Rundown/RundownNotificationTest.php` (4 tes).

## C7 — Lintas fitur dan dokumen

- `tests/Feature/Rundown/TahapCFlowTest.php`: alur penuh rundown → Admin menandai selesai (rundown terkunci) → evaluasi → rating tampil; snapshot uang identik sebelum/sesudah; acara dibatalkan tidak punya rundown/evaluasi.
- Dokumen diperbarui: bagian **G** pada `docs/BACKEND_BUSINESS_RULES.md` (15 aturan), bagian 4 pada `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`, status modul pada `README.md`.
- Instruksi Tahap C dan D disalin ke `docs/`.

## Penyimpangan dan catatan jujur

1. **Verifikasi browser manual tidak dilakukan.** Lingkungan tidak memiliki browser interaktif yang terpasang untuk sesi ini; verifikasi dilakukan lewat 44 tes HTTP fitur C dan pemeriksaan suite penuh. Ini bukan pengganti uji visual.
2. Halaman `/klien/evaluasi` dan `/klien/rekomendasi` sudah 404 lebih awal (dihapus bersama blok route Klien pada C2), bukan pada C4/D1. Tidak ada perilaku yang hilang karena halaman itu memang halaman kosong.
3. Kolom Admin pada `dibatalkan` diisi `lihat` sesuai bunyi tabel instruksi; bila produk menghendaki Admin juga tidak melihat rundown acara batal, ubah `RundownService::canView`.
4. Rancangan hanya menyebut rundown disusun klien; izin tulis vendor untuk item sendiri adalah tafsiran dari tabel C1 ("Vendor pemilik pesanan: tulis"), dan sudah dicatat pada dokumen perubahan.
