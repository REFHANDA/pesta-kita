# Instruksi Antigravity IDE — TAHAP C: Rundown Acara dan Evaluasi

> Tempel seluruh isi file ini sebagai prompt (atau simpan di `docs/` lalu minta agent membacanya). Tahap C menggabungkan **Fase 8** (rundown) dan **Fase 9** (evaluasi dan rating). Keduanya bergantung pada status acara yang sama: rundown aktif saat `terkonfirmasi`, terkunci saat `selesai`, evaluasi dibuka setelah `selesai`. Tahap A dan B sudah selesai dan tidak boleh dibongkar.
>
> **Tahap C tidak menyentuh uang.** Tidak ada perubahan pada tagihan, pembayaran, refund, kompensasi, atau pencairan. Ada tes yang membuktikannya (C7).

---

## 0. Tujuan, batas, dan aturan kerja

```
terkonfirmasi ── klien/vendor menyusun rundown ── (venue, Admin: hanya lihat)
      │
      └─ Admin tandai selesai (Tahap B) ── rundown terkunci ── klien mengisi evaluasi ── acara `dievaluasi`
```

### Yang TIDAK BOLEH diubah
- `InvoiceIssuer`, `Admin\PaymentController`, `CancellationService`, `RefundCalculator`, `DisbursementService`, dan `EventCompletionService` (kecuali menambah pemanggilan notifikasi bila perlu; tidak ada perubahan efek).
- Tabel `rundown_items`, `evaluations`, `evaluation_items` **dipakai apa adanya**. Tahap C **tidak menambah migration** kecuali ketidakcocokan nyata ditemukan di C0 (lapor dulu, jangan ubah diam-diam).
- Menu dan struktur navigasi dari Tahap A (hanya mengisi halaman yang masih empty state).

### Aturan kerja
- Ikuti `CLAUDE.md`/`AGENTS.md`. Lanjutkan branch `sinkronisasi-rancangan`.
- Satu **commit per sub-langkah** (C1…C7). Jalankan `php artisan test --compact` dan `vendor/bin/pint --dirty` setelah tiap sub-langkah; lanjut hanya jika semua lulus.
- Semua aksi tulis: route POST/PUT/DELETE eksplisit, FormRequest berpesan Bahasa Indonesia, policy, dan `DB::transaction` + `lockForUpdate` pada acara bila mengubah status. Perubahan penting menulis **AuditLog** (pola yang ada).
- Frontend tidak menghitung rata-rata rating; server yang menghitung.
- Tidak ada dependency baru. Tidak ada data contoh di UI; pakai empty state jujur.
- **Privasi:** penyedia hanya melihat "Acara #ID" (ikuti pola yang sudah dipakai di halaman pesanan penyedia). Tidak ada nama atau kontak klien di halaman rundown dan evaluasi penyedia, maupun di payload notifikasinya.
- Berhenti dan bertanya hanya jika ada ketidakpastian yang mengubah aturan bisnis. Selain itu lanjutkan, laporkan sekali di akhir.

### Kondisi kode saat ini (hasil pemeriksaan, jangan dibangun ulang)
- Tabel dan model sudah ada: `RundownItem` (`event_id`, `created_by`, `order_item_id` nullable, `start_time` string `HH:MM`, `end_time` nullable, `activity`, `description`), `Evaluation` (`event_id` **unik**, `system_feedback`), `EvaluationItem` (`target_type` ∈ venue/vendor/admin, `target_id` nullable, `rating` 1–5, `comment`). Relasi `Event::rundownItems` sudah dipakai oleh halaman detail pesanan vendor dan venue.
- `EventStatus` sudah punya `selesai` dan `dievaluasi`. Tombol "Tandai selesai" dan efeknya sudah ada (B6).
- Halaman `klien/rundown`, `klien/evaluasi`, `vendor/rundown`, `venue/rundown` masih `Route::view` empty state. Dashboard Admin dan penyedia masih berisi kartu rating kosong ("Belum ada data").
- Penyedia pada `order_items` ditentukan lewat `provider_type` (`vendor_profile`/`venue_profile`, morph map terkunci) dan `provider_id`. **Pakai pola yang sama** untuk `target_id` evaluasi.
- Klien tidak punya menu Rundown/Evaluasi (menu Klien hanya 5 item). Rundown dan evaluasi klien hidup **di dalam detail acara**.

---

## C0 — Pemeriksaan awal (wajib, jangan lewati)

Sebelum mengubah apa pun, laporkan (cukup di laporan akhir):
- `grep -rniE "rundown|evaluation|evaluasi|rating" app routes resources tests` — apa yang sudah ada.
- Bagaimana halaman detail pesanan vendor/venue memakai `event.rundownItems` hari ini.
- Skema aktual tiga tabel di atas dan apakah cukup untuk aturan C1–C5 (terutama `target_id` dan unik `event_id`). Bila tidak cukup, **berhenti dan laporkan** sebelum membuat migration.
- Bagaimana tab Acara di detail Klien dan tab/seksi di detail Admin saat ini disusun (tempat rundown dan evaluasi akan ditambahkan).

---

## C1 — Aturan rundown (service + policy, tanpa UI) + PEMERIKSAAN WAJIB

Buat `app/Services/RundownService.php` dan `app/Policies/RundownItemPolicy.php`. Semua aturan ada di sini, bukan di controller.

### Aturan akses
| Peran | Lihat | Tambah/ubah/hapus |
|---|---|---|
| Klien pemilik acara | seluruh rundown acaranya | **semua** item acaranya |
| Vendor | seluruh rundown acara yang punya pesanan miliknya berstatus `terkonfirmasi`/`selesai` | **hanya** item buatannya sendiri yang tertaut ke `order_item_id` miliknya |
| Venue | seluruh rundown acara yang punya pesanan venue miliknya berstatus `terkonfirmasi`/`selesai` | **tidak ada** (hanya lihat) |
| Admin | semua | **tidak ada** (hanya lihat) |

### Aturan status acara
- Tulis (tambah/ubah/hapus) **hanya saat acara `terkonfirmasi`**. Saat `selesai`/`dievaluasi`: terkunci permanen (baca saja). Saat `dibatalkan` atau status sebelum terkonfirmasi: tidak ada rundown (halaman menampilkan empty state, route tulis ditolak 403/422 dengan pesan jelas).
- Setelah acara `selesai`, tidak ada jalan mengubah rundown, termasuk oleh Admin.

### Validasi
- `start_time`: format `HH:MM` 24 jam (regex `^([01]\d|2[0-3]):[0-5]\d$`). `end_time`: opsional, format sama, **harus setelah** `start_time`.
- `activity`: wajib, maks 150 karakter. `description`: opsional, maks 1000.
- `order_item_id` (vendor): harus pesanan milik vendor itu pada acara yang sama dan berstatus `terkonfirmasi`. Klien boleh mengaitkan item ke pesanan mana pun di acaranya atau mengosongkannya.
- Maksimal **100 item** per acara.
- Urutan tampil: `start_time` naik, lalu `id`. Item yang tumpang tindih **diperbolehkan** (acara nyata sering paralel).

### Tes untuk C1
Matriks peran × status acara × aksi (lihat pemeriksaan), validasi format jam, `end_time` ≤ `start_time` ditolak, batas 100 item, vendor tidak bisa mengubah item klien atau item vendor lain, vendor tidak bisa mengaitkan item ke pesanan vendor lain, klien lain 403, venue dan Admin tidak punya aksi tulis.

### ⛔ PEMERIKSAAN (berhenti di sini bila meleset)
Setelah tes C1 hijau, **cetak ke terminal** matriks hasil nyata dari policy/service: baris = Klien pemilik, Klien lain, Vendor pemilik pesanan, Vendor lain, Venue pemilik pesanan, Admin; kolom = status acara (`diajukan`, `terkonfirmasi`, `selesai`, `dievaluasi`, `dibatalkan`); isi = `lihat / tulis`. **Cocokkan dengan tabel aturan di atas.** Jika ada sel yang berbeda, berhenti dan laporkan; jangan lanjut ke C2. Jika semua cocok, lanjut tanpa menunggu persetujuan.

Commit: `C1: RundownService dan policy + tes matriks akses`.

---

## C2 — Rundown di portal Klien

- Tambahkan bagian/tab **"Rundown"** di detail acara Klien (`klien/acara/{event}`), tidak membuat menu baru. Route eksplisit bernama di bawah `klien.acara.rundown.*` (daftar, simpan, ubah, hapus). **Hapus** `Route::view('/rundown')` Klien dan view lamanya; URL lama mengembalikan 404.
- Tampilan: daftar berurutan (jam mulai–selesai, kegiatan, keterangan, penyedia terkait bila ada), form tambah di atas daftar, tombol Ubah/Hapus per item (hapus dengan konfirmasi). Tampilkan penanda "dibuat oleh vendor" pada item vendor.
- Saat acara bukan `terkonfirmasi`: form dan tombol tulis **tidak tampil**; muncul keterangan status ("Rundown dikunci karena acara sudah selesai" / "Rundown tersedia setelah pembayaran terkonfirmasi"). Empty state bila kosong.
- Tidak ada fitur seret-urut atau impor; cukup urut otomatis berdasarkan jam.

Tes: klien menambah, mengubah, dan menghapus item; klien lain 403; terkunci saat `selesai`; tidak tampil saat `dibatalkan`; validasi form; URL lama 404.

Commit: `C2: rundown di detail acara klien`.

---

## C3 — Rundown di portal Vendor, Venue, dan Admin

- **Vendor → Rundown:** ganti `Route::view`. Daftar acara (hanya "Acara #ID", tanggal, jumlah tamu) tempat vendor punya pesanan `terkonfirmasi`/`selesai`; membuka satu acara menampilkan rundown lengkapnya. Vendor dapat tambah/ubah/hapus **hanya item miliknya** (aturan C1), selama acara `terkonfirmasi`.
- **Venue → Rundown:** sama, tetapi **tanpa form dan tanpa route tulis** (tes Tahap A nomor 8 tetap lulus dan diperluas).
- **Admin:** tab **"Rundown"** di detail Acara (hanya lihat, boleh menampilkan nama klien seperti halaman Admin lain). Sesuai Tahap A, daftar Acara Admin memuat tab Rundown dan Evaluasi; Evaluasi dibuat di C5.
- Halaman detail pesanan vendor/venue yang sudah memuat `event.rundownItems` dibiarkan; pastikan tidak memuat nama klien dan memakai urutan yang sama.
- Tidak ada nama atau kontak klien di seluruh halaman penyedia.

Tes: isolasi (vendor A tidak melihat acara tanpa pesanan miliknya; tidak melihat acara yang pesanannya `dibatalkan`/`dialihkan`); venue tidak punya route tulis; vendor tidak dapat mengubah item klien; halaman penyedia tidak memuat nama klien; Admin tidak punya aksi tulis.

Commit: `C3: rundown vendor, venue (hanya lihat), dan Admin`.

---

## C4 — Evaluasi acara oleh Klien

### Aturan
- Hanya klien pemilik, acara berstatus `selesai`, belum ada `evaluations` untuk acara itu. **Satu evaluasi per acara, final** (tidak dapat diubah atau dihapus; unik `event_id` menjadi pagar terakhir).
- **Subjek yang dinilai** (dihitung server dari pesanan acara berstatus `selesai`, bukan dari input klien):
  - satu nilai untuk **venue** (bila ada pesanan venue),
  - satu nilai untuk **tiap vendor** (satu per penyedia, bukan per layanan; `target_id = provider_id`),
  - satu nilai untuk **Admin**.
  Pesanan ditolak/dialihkan/dibatalkan tidak dinilai.
- Setiap subjek: `rating` integer 1–5 **wajib**; `comment` opsional maks 1000. `system_feedback` (masukan untuk sistem) opsional maks 2000.
- Input yang memuat subjek di luar daftar server, atau melewatkan subjek wajib, ditolak (422) tanpa menyimpan apa pun.

### Efek (satu transaksi, `lockForUpdate` pada acara)
1. Buat `evaluations` dan seluruh `evaluation_items`.
2. Acara `selesai` → `dievaluasi`.
3. AuditLog (aktor, acara, status lama/baru).
4. Notifikasi di C6. **Tidak ada perubahan pada pencairan atau data uang apa pun.**

### UI
- Bagian **"Evaluasi"** di detail acara Klien, muncul saat `selesai` (form) dan `dievaluasi` (ringkasan hasil yang sudah dikirim, hanya baca). Form: bintang 1–5 per subjek (input radio yang aksesibel, bukan hanya gambar), komentar, masukan sistem. Hapus `Route::view('/evaluasi')` Klien dan view lamanya; URL lama 404.
- Penyedia ditampilkan dengan **nama usaha** (boleh, sama seperti katalog), tanpa kontak.

Tes: hanya pemilik; hanya status `selesai`; dua kali kirim tidak menggandakan (kedua → 409); subjek lengkap wajib; rating di luar 1–5 ditolak; subjek asing ditolak; transaksi gagal di tengah tidak menyisakan data setengah jadi (simulasikan kegagalan setelah pembuatan item); status acara berubah ke `dievaluasi`; pencairan acara tidak berubah.

Commit: `C4: evaluasi acara oleh klien`.

---

## C5 — Hasil evaluasi dan rating (Admin, Vendor, Venue, dashboard)

- Buat `app/Services/RatingSummary.php`: rata-rata dan jumlah penilaian per subjek (`venue`/`vendor` per `provider_id`, dan `admin`). Rata-rata dihitung **di server**, satu angka desimal (dibulatkan standar), `null` bila belum ada penilaian. Dipakai ulang di Tahap D.
- **Admin:**
  - Tab **"Evaluasi"** di Acara: daftar acara `selesai` (belum dievaluasi) dan `dievaluasi`, dengan tautan ke detail evaluasi lengkap (rating per subjek, komentar, `system_feedback`, nama klien boleh tampil).
  - Kartu **"Rating Admin"** di dashboard: rata-rata dan jumlah dari data nyata; empty state bila belum ada.
- **Vendor/Venue:**
  - Kartu rating di dashboard (rata-rata dan jumlah milik sendiri) dan daftar penilaian di halaman profil atau di bagian khusus pada halaman yang sudah ada: "Acara #ID", bintang, komentar. **Tanpa nama klien.** Hanya milik sendiri.
  - Penyedia tidak melihat rating atau komentar penyedia lain, dan tidak melihat `system_feedback`.
- Klien tidak melihat evaluasi klien lain.
- Hapus sisa banner/kartu "Rating … tersedia setelah backend" bila masih ada.

Tes: rata-rata dan pembulatan benar (data sederhana yang dapat dihitung tangan); isolasi antar penyedia; payload dan halaman penyedia tidak memuat nama klien dan tidak memuat `system_feedback`; kartu Admin/penyedia menampilkan empty state bila kosong.

Commit: `C5: hasil evaluasi, rating, dan kartu dashboard`.

---

## C6 — Notifikasi

Gunakan sistem notifikasi yang ada (lonceng, buka = baca, `url` di payload). Tambahkan:
- **Rundown diubah oleh klien** → ke penyedia yang pesanannya `terkonfirmasi` pada acara itu ("Rundown Acara #ID diperbarui"). **Digabung:** bila sudah ada notifikasi jenis ini untuk penyedia dan acara yang sama yang **belum dibaca**, jangan buat yang baru (mencegah spam saat klien mengedit banyak item).
- **Rundown diubah oleh vendor** → ke klien pemilik acara (dengan aturan penggabungan yang sama; tanpa nama kontak vendor, nama usaha boleh).
- **Evaluasi dikirim** → ke Admin dan ke tiap penyedia yang dinilai ("Acara #ID telah dinilai"). Payload penyedia **tanpa nama klien dan tanpa isi komentar**; komentar dibaca di halaman, bukan di notifikasi.
- Perluas tes privasi payload dari Tahap A dan B.

Commit: `C6: notifikasi rundown dan evaluasi`.

---

## C7 — Tes lintas fitur, dokumen, dan verifikasi

### Tes lintas fitur (selain tes per sub-langkah)
1. **Alur penuh:** acara lunas dan `terkonfirmasi` → klien menyusun rundown → vendor menambah item miliknya → venue dan Admin melihat → Admin tandai selesai (B6) → rundown terkunci → klien mengisi evaluasi → acara `dievaluasi` → rating tampil di Admin dan penyedia.
2. **Uang tidak tersentuh:** ambil snapshot semua baris `disbursements`, `invoices`, `payments`, dan `cancellations` sebelum rundown/evaluasi; setelah seluruh alur C selesai, snapshot **identik**.
3. **Acara dibatalkan:** tidak ada rundown, tidak ada evaluasi, semua route tulis ditolak.
4. **Idempotensi:** evaluasi dikirim dua kali tidak menggandakan baris maupun mengubah rating.
5. **Hak akses:** peran yang salah mendapat 403 di semua route baru; tidak ada route baru tanpa `auth` + `role`; URL lama `klien/rundown` dan `klien/evaluasi` → 404.
6. **Bersih dari sisa prototipe:** tes Tahap A (tanpa `BE-`, `prototipe`, `belum terhubung`, nama contoh) tetap lulus untuk semua halaman yang disentuh.
7. **Seeder demo:** `LocalDemoSeeder` tetap berjalan. Tambahkan data demo rundown hanya jika jelas fiktif dan tidak memengaruhi tes lain.

### Dokumen
- `docs/BACKEND_BUSINESS_RULES.md`: tambahkan aturan baru dengan nomor berurutan setelah aturan terakhir Tahap B (rundown: siapa menulis, kapan terkunci, batas 100 item, tumpang tindih boleh; evaluasi: satu per acara, final, subjek dihitung server, rating tidak memengaruhi uang, rata-rata satu desimal).
- `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`: catat bahwa vendor boleh mengisi item rundown miliknya (jika berbeda dari rancangan), bahwa venue dan Admin hanya melihat, penggabungan notifikasi rundown, dan evaluasi satu kali final.
- `README.md`: perbarui status modul (rundown dan evaluasi selesai).

### Verifikasi manual di browser (sertakan hasil di laporan)
1. **Klien:** pada acara `terkonfirmasi`, tambah, ubah, hapus item rundown; coba jam tidak valid dan `end_time` sebelum `start_time` (harus ditolak dengan pesan).
2. **Vendor:** tambah item tertaut pesanannya; coba ubah item klien (tidak ada tombol / ditolak).
3. **Venue dan Admin:** rundown tampil, tidak ada form atau tombol tulis.
4. **Admin:** tandai selesai (ubah `event_date` data uji ke kemarin, bukan kode) → rundown terkunci di semua portal.
5. **Klien:** isi evaluasi (uji kirim tanpa salah satu rating) → berhasil setelah lengkap → status `dievaluasi`; tombol kirim tidak ada lagi.
6. **Admin, Vendor, Venue:** rating dan komentar tampil dengan angka yang sama dengan input; penyedia tidak melihat nama klien dan tidak melihat milik penyedia lain; notifikasi di lonceng sesuai dan tidak berlipat ganda.

---

## Format laporan akhir Tahap C

1. Hasil pemeriksaan C0 (ringkas) dan **matriks akses C1** (tabel yang dicetak).
2. Daftar commit C1…C7 dan file yang berubah per commit.
3. Hasil `php artisan test --compact` (jumlah tes) dan `vendor/bin/pint --dirty`.
4. Hasil verifikasi manual di browser.
5. Asumsi yang diambil dan pertanyaan yang tersisa.

### Asumsi bawaan (laporkan bila agent mengubahnya)
- Klien dapat mengubah semua item rundown acaranya; vendor hanya item miliknya yang tertaut ke pesanannya; venue dan Admin hanya melihat.
- Rundown hanya dapat ditulis saat acara `terkonfirmasi`; terkunci permanen setelah `selesai`.
- Item rundown yang tumpang tindih diperbolehkan; maksimal 100 item per acara.
- Evaluasi: satu per acara, final, tanpa batas waktu pengisian; satu nilai per penyedia (bukan per layanan); penyedia yang pesanannya tidak `selesai` tidak dinilai.
- Rating tidak memengaruhi pencairan atau status penyedia; rating hanya informasi (dan dipakai rekomendasi di Tahap D).
- Penyedia membaca komentar tentang dirinya tanpa nama klien; `system_feedback` hanya terbaca Admin.
- Tidak ada penghapusan atau moderasi komentar oleh Admin (di luar lingkup).

**Jangan mulai Tahap D** (rekomendasi paket, layout ruangan, pembersihan akhir) sebelum saya mengonfirmasi laporan ini.
