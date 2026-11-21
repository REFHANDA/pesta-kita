# Instruksi Antigravity IDE — Sinkronisasi Project Pesta Kita dengan Dokumen Rancangan EMS

> Tempel seluruh isi file ini sebagai prompt awal (atau simpan di root project, misalnya `docs/INSTRUKSI_SINKRONISASI.md`, lalu minta agent membacanya). Kerjakan **per fase**. Setelah tiap fase, berhenti dan laporkan sebelum lanjut.

---

## 0. Peran dan konteks

Kamu adalah agent pengembang Laravel untuk project **Pesta Kita**, Event Management System (EMS) berbasis web untuk acara ulang tahun privat (Laravel + Blade + MySQL, PHP 8.4, PHPUnit). Dokumen acuan utama adalah **Dokumen Perancangan EMS Kelompok 3** (rancangan resmi). Tugasmu: membuat kode project **sesuai rancangan itu**, dengan cara:

1. **Menghapus** bagian yang menyimpang dari rancangan.
2. **Menambahkan** bagian rancangan yang belum ada.
3. **Menyinkronkan** dokumen internal project dengan rancangan.

### Aturan kerja wajib

- Buat branch baru: `git checkout -b sinkronisasi-rancangan`. Jangan bekerja langsung di branch utama.
- Jalankan `php artisan test --compact` **sebelum** mulai (catat baseline) dan **setelah** tiap fase. Semua tes harus lulus sebelum fase dianggap selesai.
- Ikuti `CLAUDE.md` / `AGENTS.md` yang sudah ada: konvensi sibling file, `php artisan make:...` untuk file baru, return type eksplisit, curly braces, enum TitleCase, jalankan `vendor/bin/pint --dirty` sebelum menyelesaikan fase.
- **Konvensi penamaan project (pertahankan):** nama tabel/kolom/class dalam bahasa Inggris (`events`, `order_items`, `audit_logs`); **nilai status/enum dalam bahasa Indonesia** (`menunggu_review`, `terkonfirmasi`). Teks UI bahasa Indonesia.
- **Rupiah = bilangan bulat** (`unsignedBigInteger`). Dilarang memakai float/decimal untuk uang.
- Setiap perubahan status finansial wajib dalam `DB::transaction` dengan `lockForUpdate()` pada baris terkait, dan menolak aksi bila status saat ini tidak mengizinkan.
- Setiap keputusan penting Admin wajib menulis `AuditLog` (aktor, aksi, subjek, status lama, status baru, alasan).
- **Jangan** menambah dependency Composer/NPM tanpa bertanya (lihat catatan PDF di Fase 5). **Jangan** mengubah desain visual/CSS kecuali perlu untuk menghubungkan data.
- **Jangan** menjalankan perintah destruktif pada database nyata. Penghapusan tabel hanya lewat migration baru (jangan edit atau hapus migration lama yang sudah ada).
- Jangan commit `.env`, `database.sqlite`, atau `vendor/`.
- Bila ada hal yang ambigu atau berpotensi mengubah aturan bisnis, **berhenti dan tanya**, jangan menebak.

---

## 1. Aturan bisnis yang menjadi sumber kebenaran (dari rancangan)

Gunakan ini sebagai spesifikasi. Jika kode atau dokumen project bertentangan, **rancangan ini yang menang**.

### 1.1 Pelaku
Klien, Vendor, Pemilik Venue (`venue`), Admin. Admin tidak bisa mendaftar publik. Vendor dan Venue disebut "penyedia jasa". Klien dan penyedia **tidak berhubungan langsung**: nama usaha penyedia tampil ke klien, **kontak tidak**. Penyedia melihat acara sebagai **"Acara #ID"** (tanggal, jumlah tamu, tema, rundown), **tanpa nama klien**.

### 1.2 Status (nilai enum)

| Objek | Urutan status |
|---|---|
| Acara (`events`) | `draft → diajukan → menunggu_pembayaran → terkonfirmasi → selesai → dievaluasi`; cabang `dibatalkan` |
| Pesanan (`order_items`) | `menunggu_review → disetujui → terkonfirmasi → selesai`; cabang `ditolak`, `dialihkan`, `dibatalkan` |
| Tagihan (`invoices`) | `belum_dibayar → menunggu_validasi → lunas`; cabang `kedaluwarsa`, `dibatalkan` |
| Pembayaran (`payments`) | `menunggu_validasi → valid` atau `ditolak` (klien unggah ulang) |
| Keberatan (`order_objections`) | `diajukan → ditinjau → diterima / ditolak` |
| Pengajuan harga | `menunggu → disetujui / ditolak` (nilai project saat ini sudah dipakai, boleh dipertahankan) |
| Pembatalan (`cancellations`) | `diajukan → ditinjau → disetujui → refund_diproses → refund_selesai`; atau `ditolak` |
| Pencairan (`disbursements`) | `belum_cair → cair` |

### 1.3 Komponen tagihan
- **Harga penyedia**: harga aktif yang disetujui, **dikunci** saat pesanan disetujui (tidak berubah walau penyedia mengajukan harga baru).
- **Komisi platform** = 10% × harga penyedia, dibulatkan ke rupiah (half-up), disimpan **per pesanan**, tampil sebagai baris terpisah. Komisi **ditambahkan** ke tagihan klien, **tidak dipotong** dari penyedia.
- **Biaya kebersihan**: nominal tetap per venue (`venues.cleaning_fee`, hanya Admin yang mengubah), **satu baris per acara** untuk venue terpilih; kosong/null = tidak dikenakan.
- **Kode unik**: 3 digit acak (001–999), tidak boleh sama dengan kode tagihan lain yang masih **belum dibayar / menunggu validasi**. Bukan pendapatan, **tidak** masuk dasar komisi. Dapat dinonaktifkan lewat pengaturan.
- **Total tagihan (grand total)** = Σ harga penyedia + Σ komisi + biaya kebersihan.
- **Total transfer** = grand total + kode unik.
- **Hak penyedia** = harga penyedia penuh, dicairkan setelah acara ditandai selesai.
- **Pendapatan perusahaan** = Σ komisi + biaya kebersihan.

Contoh uji wajib: venue 5.000.000, catering 3.000.000, dekorasi 2.000.000, kebersihan 500.000 → komisi 500.000 / 300.000 / 200.000, grand total **11.500.000**, kode unik 342 → transfer **11.500.342**, pencairan 5.000.000 / 3.000.000 / 2.000.000, pendapatan perusahaan **1.500.000**.

### 1.4 Alur persetujuan dan penagihan
1. Klien mengajukan acara. Sistem memeriksa ketersediaan tanggal dan stok, **menahan** tanggal, menyimpan pesanan `menunggu_review`.
2. **Hanya Admin** yang menyetujui/menolak/mengalihkan pesanan (penyedia hanya memantau, tanpa tombol terima/tolak). SLA review bawaan 24 jam + notifikasi pengingat.
3. Setelah `disetujui`, penyedia punya **masa keberatan 24 jam**. Keberatan: wajib kategori (`alat_rusak`, `jadwal_bentrok`, `kapasitas`, `lainnya`) + keterangan; **tidak langsung mengubah status pesanan**; berlaku setelah Admin memutuskan dengan alasan tertulis (batas 24 jam).
4. Jika pesanan `ditolak`/`dialihkan`: klien dinotifikasi, boleh memilih layanan lain atau lanjut tanpa item itu; tagihan belum terbit sampai selesai.
5. **Tagihan terbit otomatis** bila: semua pesanan sudah diputuskan, masa keberatan selesai, dan tidak ada keberatan terbuka. Isi hanya pesanan `disetujui`. Batas bayar **48 jam**.
6. Tagihan belum dibayar boleh dibuat ulang sebagai versi baru (versi lama `dibatalkan`). Setelah `lunas`, isi terkunci.
7. Keberatan setelah lunas = **keberatan darurat**, wajib bukti, ditindaklanjuti Admin (alihkan manual ke penyedia lain; klien boleh minta refund penuh bila pengganti tidak sepadan).

### 1.5 Pembayaran
- 100% dalam satu transfer per tagihan, ke salah satu `company_bank_accounts` aktif (rekening tujuan dicatat di pembayaran).
- Admin hanya boleh `valid` jika jumlah = total transfer (total + kode unik); selain itu `ditolak` dengan catatan.
- Tagihan tidak dibayar 48 jam → `kedaluwarsa` (job terjadwal **tiap jam**), pesanan dibatalkan, tanggal venue dan stok dilepas.
- Setelah `valid`: tagihan `lunas`, pesanan `terkonfirmasi`, acara `terkonfirmasi`, nota lunas terbit, tombol batal klien terkunci.
- Tagihan dan nota lunas bisa diunduh **PDF** bernomor unik `PK/YYYY/MM/NNNN` (contoh `PK/2026/10/0001`), hanya oleh pemilik acara dan Admin.

### 1.6 Pembatalan dan refund
- **Sebelum** pembayaran divalidasi: batal otomatis tanpa biaya; tagihan `dibatalkan`; tanggal/stok dilepas; penyedia diberi tahu.
- **Sesudah**: tombol batal terkunci; klien mengajukan pembatalan (untuk **seluruh acara**, bukan per item) dengan alasan; Admin meninjau.
- Persen refund otomatis dari sisa hari menuju acara, **disimpan di pengaturan (dapat diubah Admin)**:

| Sisa waktu | Refund dari grand total |
|---|---|
| ≥ 30 hari | 90% |
| 14–29 hari | 70% |
| 7–13 hari | 50% |
| 3–6 hari | 25% |
| < 3 hari | 0% |

- Dengan `p` = persen refund: **refund klien = grand total × p + kode unik (dikembalikan penuh)**; potongan = grand total × (1 − p), dibagi: **kompensasi tiap penyedia = harga penyedia pesanan itu × (1 − p)**; **pendapatan perusahaan = (Σ komisi + kebersihan) × (1 − p)**.
- Pembulatan: hitung dengan integer; selisih pembulatan masuk ke pendapatan perusahaan agar jumlah komponen selalu = grand total.
- Kesalahan vendor / force majeure: Admin menetapkan **p = 100%**, kompensasi penyedia 0, **alasan wajib** + audit log (Admin boleh menetapkan kompensasi manual).
- **Snapshot kebijakan**: simpan persen, tabel yang dipakai, dan hasil hitung pada record pembatalan (frontend tidak menghitung refund final).

### 1.7 Ketersediaan
- Stok peralatan tersedia pada tanggal X = stok total − Σ jumlah sewa pada tanggal X dari pesanan berstatus **menahan** (`disetujui`, `terkonfirmasi`; dan pesanan `menunggu_review` ikut menahan tanggal sejak diajukan). Pesanan kedaluwarsa/dibatalkan/ditolak/dialihkan tidak dihitung.
- Venue: satu tanggal, satu venue, satu pesanan penahan.
- Cek ketersediaan + simpan pesanan dalam **satu transaksi** dengan `lockForUpdate` agar dua klien tak mengambil slot terakhir bersamaan.

### 1.8 Pencairan
- Dibuat per pesanan `terkonfirmasi` saat Admin menandai acara `selesai` (**hanya setelah tanggal acara lewat**). Admin mencatat transfer (tanggal + bukti). Memakai rekening penyedia yang sudah diverifikasi. Satu pencairan hak per pesanan (unique constraint). Kompensasi pembatalan masuk daftar pencairan juga.

### 1.9 Rundown
- Setiap item mencatat pembuatnya. Klien mengelola semua item; vendor hanya item miliknya dan hanya untuk acara yang pesanannya `terkonfirmasi`; venue dan Admin hanya melihat. Bentrok waktu = peringatan, tidak memblokir. Dikunci setelah acara selesai.

### 1.10 Evaluasi
- Muncul setelah acara selesai, berurutan: **venue → tiap vendor → Admin → feedback sistem**. Klien lanjut ke tahap berikut setelah semua vendor dinilai (komentar boleh kosong). Penyedia melihat **rata-rata** rating tanpa identitas klien. Rating Admin dan feedback sistem **internal**.

### 1.11 Rekomendasi paket
- Input: budget, jumlah tamu, tanggal, tema. Saring: aktif, tersedia pada tanggal acara, kapasitas cukup. Harga pembanding = **harga akhir** (harga penyedia + komisi + kebersihan). Alokasi bawaan (dapat diatur Admin): Venue 35%, Catering 30%, Dekorasi 15%, Hiburan 10%, Peralatan 5%, Cadangan 5%. Keluaran 3 paket: **Hemat, Seimbang, Premium**.

### 1.12 Parameter pengaturan (tabel `settings`, bisa diubah Admin tanpa ubah kode)
`commission_percent=10`, `payment_deadline_hours=48`, `objection_window_hours=24`, `admin_review_sla_hours=24`, `refund_tiers` (JSON), `budget_allocation` (JSON), `unique_code_enabled=true`.

---

## 2. Fase pekerjaan

### FASE 1 — Hapus alur "Request Quote" (menyimpang dari rancangan)

Rancangan **tidak memiliki** negosiasi penawaran. Penyedia hanya punya harga aktif yang disetujui Admin.

Tugas:
1. Audit semua referensi: `grep -rniE "quote|penawaran|perlu_penawaran"` di `app/`, `routes/`, `resources/views/`, `database/`, `tests/`, `docs/`, `public/assets/`.
2. Hapus: model `QuoteRequest`, `QuoteProposal`, `QuoteDecision`; enum `QuoteRequestStatus`, `QuoteDecisionResult`; controller `Klien|Vendor|Venue\QuoteRequestController`; rute `permintaan-penawaran*` di `routes/web.php`; view `*/permintaan-penawaran*.blade.php` (semua portal, termasuk Admin); factory; seeder terkait; tes terkait (`EventQuoteCatalogWiringTest` — pertahankan bagian yang menguji acara/katalog, hapus bagian quote); menu "Permintaan penawaran/Peninjauan penawaran" di semua `layouts/*.blade.php`; tombol "Minta penawaran" di `klien/katalog-*detail` dan `kalender-ketersediaan` (ganti dengan aksi **"Tambah ke acara"**, diaktifkan di Fase 3).
2. Migration baru (jangan edit yang lama): `drop` tabel `quote_decisions`, `quote_proposals`, `quote_requests`; di `order_items` drop kolom `quote_request_id` dan `proposed_price_snapshot`.
3. Hapus `PricingMode::PerluPenawaran` (sisakan `Tetap`) dan semua cabang UI "harga setelah penawaran". Semua item katalog wajib memiliki harga tetap (validasi di `Store/UpdateCatalogItemRequest`, `StoreVenueRequest`).
4. Perbaiki `docs/` (lihat Fase 13) agar tidak lagi menyebut alur quote.

Kriteria selesai: tidak ada `grep` hit "QuoteRequest|permintaan-penawaran" yang tersisa; `php artisan route:list` bersih; `php artisan migrate:fresh --seed` sukses; tes lulus.

### FASE 2 — Penyelarasan skema database

Buat migration **additive** baru (jangan ubah migration lama):

- `events`: tambah `budget` (unsignedBigInteger, nullable → wajib di form); ubah nilai status `dinilai` menjadi `dievaluasi` (rename case enum `Dinilai`→`Dievaluasi`, migrasi data lama bila ada).
- `order_items`: tambah `usage_date` (date, tgl pakai), `quantity` (unsignedInteger default 1, untuk peralatan), `provider_price` (harga penyedia terkunci), `commission` (unsignedBigInteger), `admin_note` (text null), `reviewed_by`, `reviewed_at`, `review_due_at` (SLA), `approved_at`. Pertahankan `catalog_price_snapshot`. `final_price` → hapus atau jadikan alias `provider_price` (pilih satu, jangan duplikat; migrasikan data). Ganti nilai `OrderItemStatus` menjadi: `MenungguReview`, `Disetujui`, `Terkonfirmasi`, `Selesai`, `Ditolak`, `Dialihkan`, `Dibatalkan`. Hapus `MasaKeberatan` dan `Mengikat` (masa keberatan dihitung dari `approved_at + objection_window_hours`, bukan status). Tambah `replaced_by_order_item_id` (nullable) untuk pengalihan.
- **Unique constraint venue**: tambah kolom generated/pendukung agar satu venue+tanggal hanya boleh punya satu pesanan penahan (mis. kolom `hold_key` nullable bernilai `venue:{id}:{tanggal}` saat status menahan, `NULL` saat dilepas, dengan unique index). Tanpa ini, aturan "satu tanggal satu venue" hanya bergantung pada kode.
- `order_objections`: tambah `category`, `evidence_media_id` (nullable, wajib bila keberatan darurat), `is_emergency` (bool), `decision_reason` (text), `reviewed_by`; status jadi `diajukan/ditinjau/diterima/ditolak`; ganti `admin_decision` bila perlu agar konsisten.
- `company_bank_accounts`: tambah `sort_order`.
- Tabel baru: `settings` (key unik, value JSON), `invoices`, `invoice_items`, `payments`, `cancellations`, `disbursements`, `rundown_items`, `evaluations`, `evaluation_items`, `notifications` (boleh memakai tabel notifikasi bawaan Laravel `php artisan notifications:table` bila sesuai konvensi, tanpa dependency baru).

Rancangan kolom tabel baru:

- `invoices`: `event_id`, `invoice_number` (unique, `PK/YYYY/MM/NNNN`), `version`, `subtotal_provider`, `total_commission`, `cleaning_fee`, `grand_total`, `unique_code`, `transfer_total`, `due_at`, `status`, `issued_at`, `paid_at`. Satu tagihan **aktif** per acara.
- `invoice_items`: `invoice_id`, `order_item_id` (nullable untuk baris kebersihan), `type` (`harga_penyedia`, `komisi`, `kebersihan`), `description`, `amount` (snapshot).
- `payments`: `invoice_id`, `company_bank_account_id`, `amount`, `sender_name`, `transfer_date`, `proof_media_id`, `status`, `admin_note`, `confirmed_by`, `confirmed_at`.
- `cancellations`: `event_id`, `invoice_id`, `reason`, `cause` (`klien`/`vendor`/`force_majeure`), `refund_percent`, `refund_amount`, `unique_code_refund`, `provider_compensation_total`, `company_income`, `policy_snapshot` (JSON), `status`, `refund_proof_media_id`, `reviewed_by`, `decision_reason`.
- `disbursements`: `order_item_id` (unique per jenis), `provider_type`, `provider_id`, `type` (`hak_pesanan`/`kompensasi`), `amount`, `status`, `paid_at`, `proof_media_id`, `recorded_by`.
- `rundown_items`: `event_id`, `created_by`, `order_item_id` (nullable), `start_time`, `end_time` (nullable), `activity`, soft lock via status acara.
- `evaluations`: `event_id` (unique), `system_feedback`; `evaluation_items`: `evaluation_id`, `target_type` (`venue`/`vendor`/`admin`), `target_id`, `rating` (1–5), `comment`.

Tambahkan Model, Enum, Factory, dan relasi. Perbarui `SchemaIntegrityTest` / `DomainRelationsTest`.

Kriteria selesai: `migrate:fresh --seed` sukses; tes skema/relasi baru lulus; tidak ada float untuk uang.

### FASE 3 — Penyusunan acara, budget, dan pemesanan (booking)

1. `Klien\EventController`: create/update/delete acara draft dengan `name`, `event_date`, `guest_count`, `theme`, **`budget`**.
2. Aksi "Tambah ke acara" dari katalog/kalender: membuat `order_items` berstatus draft di acara (tanpa menahan tanggal sampai acara diajukan — jika memilih menahan sejak ditambah, dokumentasikan dan jelaskan). Validasi: penyedia terverifikasi, item aktif, tanggal tidak `tidak_tersedia`.
3. Aksi **Ajukan acara** (`diajukan`): dalam satu `DB::transaction` + `lockForUpdate`, cek ketersediaan (aturan §1.7), isi `usage_date`, `provider_price` = harga aktif saat itu (snapshot katalog), set status `menunggu_review`, set `review_due_at = now + SLA`, isi `hold_key` untuk venue.
4. Layanan `AvailabilityService` (satu kelas, mudah dites) untuk stok per tanggal dan venue per tanggal; pakai juga di kalender.
5. Notifikasi ke Admin saat ada pesanan baru.

Tes: dua klien berebut slot terakhir (hanya satu berhasil); stok peralatan dihitung benar; pesanan ditolak/dibatalkan melepas slot.

### FASE 4 — Review Admin, masa keberatan, penerbitan tagihan

1. `Admin\OrderReviewController`: daftar `menunggu_review`; aksi `setujui` / `tolak` (alasan wajib) / `alihkan` (pilih penyedia pengganti, buat pesanan baru status `dialihkan` rujukannya). Saat setujui: kunci `provider_price`, hitung `commission` (10% dari pengaturan), set `approved_at`. Tulis audit log.
2. Penyedia: halaman pesanan **read-only** (hapus tombol terima/tolak jika ada). Tampilkan "Acara #ID" tanpa nama klien.
3. Keberatan: form penyedia (kategori + keterangan), hanya selama `now < approved_at + objection_window`, hanya penyedia terkait. Admin memutuskan (`diterima`/`ditolak`) dengan alasan tertulis. Keberatan tidak mengubah status pesanan sampai Admin memutuskan.
4. `InvoiceIssuer` (service) + command terjadwal `invoices:issue` (tiap 10–15 menit): terbitkan tagihan bila semua pesanan acara sudah diputuskan, masa keberatan selesai, tidak ada keberatan terbuka. Isi hanya pesanan `disetujui`; tambahkan satu baris kebersihan bila ada venue terpilih dan `cleaning_fee` > 0; buat **kode unik** (unik di antara tagihan terbuka); hitung total; set `due_at = now + payment_deadline_hours`; acara → `menunggu_pembayaran`. Dalam transaksi.
5. Admin dapat "Buat ulang tagihan" (versi baru) hanya jika tagihan lama `belum_dibayar`; versi lama → `dibatalkan`.
6. Command terjadwal `orders:remind-sla` untuk notifikasi pengingat SLA review Admin.

### FASE 5 — Tagihan, pembayaran, validasi, dan nota PDF

1. Klien: halaman tagihan (rincian, rekening aktif urut `sort_order`, kode unik, batas bayar), pilih rekening, unggah bukti (JPG/PNG/PDF, maks 5 MB, disk `private`, via `Media`), status `menunggu_validasi`.
2. Admin: validasi pembayaran. Server yang membandingkan `amount == transfer_total`; tidak sama → `ditolak` + catatan (klien unggah ulang). Sama → `valid`: tagihan `lunas`, pesanan `terkonfirmasi`, acara `terkonfirmasi`, terbit nota lunas, notifikasi ke klien dan penyedia terkait. Audit log.
3. **PDF tagihan dan nota lunas**: unduhan hanya pemilik acara dan Admin (Policy). **Butuh library PDF** (mis. `barryvdh/laravel-dompdf`). Karena aturan project melarang menambah dependency tanpa persetujuan, **berhenti dan minta izin pengguna**; bila tidak disetujui, sediakan view cetak (print-friendly HTML) sebagai pengganti sementara dan catat di laporan.
4. Command terjadwal `invoices:expire` **tiap jam**: tagihan `belum_dibayar` lewat `due_at` → `kedaluwarsa`, pesanan terkait `dibatalkan`, kosongkan `hold_key`, acara kembali/`dibatalkan` sesuai kebijakan, notifikasi.
5. Daftarkan scheduler di `routes/console.php` (`Schedule::command(...)->hourly()`).

### FASE 6 — Pembatalan dan refund

1. Klien: tombol batal.
   - Tagihan belum `lunas`: batal otomatis (tagihan `dibatalkan`, lepas slot, notifikasi).
   - Sudah `lunas`: tombol terkunci; tampilkan form **pengajuan pembatalan** (alasan wajib).
2. `RefundCalculator` (service murni, mudah dites): terima grand total, sisa hari, tabel dari `settings`, cause, daftar pesanan → kembalikan persen, refund, kode unik dikembalikan, kompensasi per pesanan, pendapatan perusahaan (aturan §1.6; semua integer, selisih pembulatan ke pendapatan perusahaan).
3. Admin: review pengajuan (setujui/tolak + alasan). Untuk `vendor`/`force_majeure` Admin set `p = 100%` dengan alasan wajib + audit log; opsi kompensasi manual. Simpan `policy_snapshot`.
4. Admin mencatat transfer refund (unggah bukti) → `refund_selesai`; acara `dibatalkan`; slot dilepas; kompensasi penyedia masuk `disbursements` tipe `kompensasi`.
5. **Hapus** semua perhitungan/estimasi refund di Blade/JS; semua angka dari server.

Tes wajib: setiap batas tabel (30, 29, 14, 13, 7, 6, 3, 2 hari), force majeure, jumlah komponen selalu = grand total.

### FASE 7 — Acara selesai dan pencairan

1. Admin "Tandai selesai": hanya bila `event_date < today`; acara → `selesai`; pesanan terkonfirmasi → `selesai`; buat `disbursements` `hak_pesanan` per pesanan = `provider_price` (unique per pesanan; percobaan kedua ditolak constraint). Buka form evaluasi untuk klien.
2. Admin catat pencairan (tanggal + bukti) → `cair`; pakai rekening penyedia terverifikasi (`provider_bank_accounts`); tampilkan rekening hanya sebagian (`···· 7890`) di UI.
3. Vendor/Venue: halaman pencairan read-only milik sendiri (rincian tanpa potongan komisi). Pastikan data vendor tidak muncul di portal venue dan sebaliknya.

### FASE 8 — Rundown

Terapkan aturan §1.9 lewat `RundownItemPolicy` + controller untuk Klien (semua item), Vendor (item sendiri, hanya acara dengan pesanannya `terkonfirmasi`), Venue dan Admin (read-only). Peringatan bentrok waktu (tidak memblokir). Kunci semua perubahan setelah acara `selesai`.

### FASE 9 — Evaluasi dan rating

Form berjenjang venue → tiap vendor → Admin → feedback sistem (state per langkah di server, bukan hanya JS). Acara → `dievaluasi` setelah semua tahap. Penyedia melihat **rata-rata** rating tanpa identitas klien; rating Admin dan feedback sistem hanya terlihat Admin. Rekap di `admin/evaluasi`.

### FASE 10 — Rekomendasi paket

`RecommendationService`: saring data (aktif, tersedia tanggal acara, kapasitas ≥ jumlah tamu) → alokasi per kategori dari `settings.budget_allocation` → bandingkan dengan **harga akhir** → hasilkan 3 paket (Hemat, Seimbang, Premium; pilih item per kategori mendekati 70%/100%/130% alokasi, atau aturan lain yang jelas dan terdokumentasi). Klien dapat menerapkan paket ke acara (membuat `order_items` draft). Rating rata-rata dipakai sebagai pemeringkat pada tahap lanjutan (siapkan, jangan wajib).

### FASE 11 — Layout ruangan venue (minimal)

Rancangan menyebut "layout ruangan". **Tanyakan pengguna** apakah MVP cukup: klien memilih `venue_rooms` + mengunggah/menyimpan catatan layout (media), atau perlu editor visual. Jangan membangun editor visual tanpa persetujuan. Layout dapat dilihat venue (read-only).

### FASE 12 — Pengaturan Admin, notifikasi, dan log aktivitas

1. `settings` + halaman `admin/pengaturan` yang benar-benar menyimpan parameter §1.12 (validasi, audit log). Seed nilai bawaan.
2. Notifikasi dalam aplikasi untuk perubahan status pesanan, tagihan, pembayaran, dan pengingat SLA; halaman `*/notifikasi` memakai data nyata.
3. Pastikan **semua** keputusan Admin menulis `audit_logs` (persetujuan, perubahan harga, validasi pembayaran, refund, pencairan, keberatan, set p refund).
4. Admin: halaman pantau acara/pesanan/rating memakai data nyata.

### FASE 13 — Sinkronisasi dokumen, kebersihan, dan keamanan

1. Perbarui `docs/BACKEND_BUSINESS_RULES.md`: pindahkan B1, B2, B3, B5–B10, B13, B16–B17 dari "terbuka" menjadi "diputuskan sesuai rancangan" (isi dari §1); hapus semua rujukan Request Quote; hapus status `masa_keberatan`/`mengikat`.
2. Perbarui `README.md`: tabel status per modul (**Selesai / Sebagian / Prototipe**), cara menjalankan, akun demo hanya untuk lokal.
3. Buat `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md` berisi hal yang **sengaja berbeda** dari PDF dan perlu diperbarui di dokumen rancangan: peralatan digabung ke `catalog_items` dengan `kind`; dokumen verifikasi penyedia (KTP/NIB/NPWP/bukti venue); `vendor_profiles`/`venue_profiles`/`provider_bank_accounts` menggantikan class `Vendor`; nama tabel bahasa Inggris.
4. Hapus banner/teks "prototipe" (`.data-note`) pada halaman yang sudah terhubung backend; pertahankan hanya pada halaman yang benar-benar masih statis.
5. Pindahkan atau hapus `_updates/` dan `_prototypes/` dari repo (arsipkan di luar repo atau branch lain); pastikan tidak ada view yang bergantung padanya.
6. Hapus `.phpunit.result.cache`, `.screenshots/`, `database/database.sqlite` dari repo bila terlacak; tambahkan ke `.gitignore`.
7. Pastikan `.env` tidak terlacak. `.env.example` memakai `APP_DEBUG=false` sebagai contoh produksi dan DB tanpa password nyata. Password seeder `password` hanya aktif di environment `local`/`demo` (guard `app()->environment(...)`).
8. Hapus route catch-all `/{page}` secara bertahap untuk halaman yang sudah punya controller; halaman yang tersisa harus tetap dilindungi `auth` + `role`.

---

## 3. Verifikasi akhir (wajib dijalankan)

1. `php artisan migrate:fresh --seed` sukses.
2. `php artisan test --compact` lulus, dengan tes baru minimal untuk: kalkulasi tagihan (contoh §1.3), kode unik, validasi jumlah pembayaran, kedaluwarsa tagihan, `RefundCalculator` (semua batas), pencairan unik per pesanan, race booking, otorisasi (penyedia tidak melihat nama klien/kontak; klien tidak melihat kontak penyedia; PDF hanya pemilik/Admin), aturan rundown.
3. `vendor/bin/pint --dirty` bersih.
4. `php artisan route:list --except-vendor` tidak memuat rute quote.
5. Skenario uji manual end-to-end (seed demo): registrasi → verifikasi penyedia → klien susun acara + budget → ajukan → Admin setujui → masa keberatan → tagihan terbit → unggah bukti → Admin validasi → nota PDF → rundown → tandai selesai → pencairan → evaluasi; plus jalur batal (sebelum dan sesudah bayar) dan jalur tagihan kedaluwarsa.

## 4. Format laporan tiap fase

Berikan laporan ringkas: (a) file yang ditambah/diubah/dihapus, (b) migration baru, (c) tes yang ditambah dan hasilnya, (d) asumsi yang kamu ambil, (e) pertanyaan yang butuh keputusan pengguna. **Berhenti** setelah laporan dan tunggu konfirmasi sebelum fase berikutnya.
