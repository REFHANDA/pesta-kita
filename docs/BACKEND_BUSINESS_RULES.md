# Aturan Bisnis Backend — Pesta Kita

Sumber: `docs/AI_AGENT_CONTEXT.md` bagian 4, roadmap Tahap 6, dan teks yang sudah tampil di Blade. Aturan bagian A terkunci. Bagian B adalah keputusan yang belum boleh ditebak.

## A. Aturan terkunci

1. Pesta Kita adalah managed marketplace. Admin mengendalikan persetujuan pesanan pada MVP.
2. Klien membayar satu tagihan gabungan untuk satu acara.
3. Pembayaran MVP adalah transfer manual ke rekening perusahaan. Tidak ada payment gateway sampai pengguna menyetujui.
4. Komisi 10% ditambahkan pada tagihan Klien. Komisi tidak dipotong dari hak Vendor atau Pemilik Venue.
5. Harga penyedia adalah harga aktif yang disetujui Admin, dikunci saat pesanan disetujui. Tidak ada alur request quote/usulan penawaran harga.
6. Pencairan hak penyedia dilakukan Admin setelah acara selesai.
7. Kontak pribadi penyedia (telepon, email usaha, penanggung jawab, rekening utuh) tidak ditampilkan ke Klien.
8. Ulasan hanya setelah acara selesai dan transaksi memenuhi syarat. Penyedia melihat agregat, tanpa identitas Klien. Penilaian untuk Pesta Kita dan umpan balik aplikasi bersifat internal.
9. Backend adalah sumber kebenaran nominal, kode unik transfer, refund, payout, dan status.
10. Refund mengikuti kebijakan transaksi yang berlaku. Kebijakan yang berlaku pada saat transaksi harus disimpan sebagai snapshot. Frontend tidak menghitung refund final.
11. Perubahan harga katalog tidak mengubah pesanan yang sudah disetujui. Selama pengajuan harga berstatus menunggu, harga katalog lama tetap berlaku.
12. Nama Klien tidak ditampilkan pada daftar pesanan penyedia. Komunikasi lewat Pesta Kita.
13. Akun Admin tidak dibuat dari formulir daftar publik. UI daftar hanya menawarkan Klien, Vendor, dan Pemilik Venue.
14. Satu akun pada UI daftar memilih tepat satu peran.

## B. Keputusan Bisnis (Disinkronkan dengan Dokumen Rancangan EMS)

Seluruh keputusan di bawah ini telah diputuskan sesuai Dokumen Perancangan EMS Kelompok 3 (rancangan resmi):

| No  | Topik                  | Status Keputusan | Ringkasan Ketetapan Rancangan |
| --- | ---------------------- | ---------------- | ----------------------------- |
| B1  | Kebijakan refund       | **DIPUTUSKAN**   | Persentase refund dari grand total berdasarkan sisa hari: ≥30 hari (90%), 14–29 hari (70%), 7–13 hari (50%), 3–6 hari (25%), <3 hari (0%). Refund klien = grand total × p + kode unik dikembalikan penuh. Kompensasi penyedia = harga penyedia × (1 − p). Pendapatan perusahaan = (komisi + kebersihan) × (1 − p). Force majeure/kesalahan vendor: p = 100%, kompensasi 0. Snapshot disimpan di record pembatalan. |
| B2  | Kode unik              | **DIPUTUSKAN**   | 3 digit acak (001–999), unik di antara tagihan belum dibayar / menunggu validasi. Bukan pendapatan dan tidak masuk dasar komisi. Total transfer = grand total + kode unik. Dapat dinonaktifkan via settings. |
| B3  | Dasar komisi           | **DIPUTUSKAN**   | Komisi platform 10% dihitung dari total baris pesanan (`lineTotal` = `provider_price` × `quantity`, di mana `provider_price` adalah harga satuan), dibulatkan ke rupiah terdekat (half-up: `round((lineTotal * commissionPercent) / 100, 0, PHP_ROUND_HALF_UP)`), ditambahkan ke tagihan klien (tidak dipotong dari hak penyedia). Biaya kebersihan dan kode unik tidak dikenakan komisi. |
| B4  | Biaya kebersihan       | **DIPUTUSKAN**   | Nominal tetap per venue (`venues.cleaning_fee`), hanya diubah Admin. Satu baris per acara untuk venue terpilih; jika null/0 = tidak dikenakan. |
| B5  | Deposit venue          | **DIPUTUSKAN**   | Tidak ada deposit; hanya harga sewa venue + biaya kebersihan opsional. |
| B6  | Jumlah venue per acara | **DIPUTUSKAN**   | Maksimal satu venue per acara; satu venue satu tanggal satu pesanan penahan (`hold_key`). |
| B7  | Masa keberatan         | **DIPUTUSKAN**   | Masa keberatan penyedia adalah 24 jam setelah pesanan disetujui Admin. Keberatan wajib kategori + alasan dan diputuskan Admin. Masa keberatan bukan status pesanan terpisah, melainkan window waktu (`approved_at + objection_window_hours`). |
| B8  | Versi tagihan          | **DIPUTUSKAN**   | Tagihan belum dibayar dapat dibuat ulang sebagai versi baru (versi lama dibatalkan). Setelah lunas, isi tagihan terkunci permanen. |
| B9  | Batas bayar            | **DIPUTUSKAN**   | Batas bayar adalah 48 jam sejak tagihan terbit otomatis (`settings.payment_deadline_hours`). |
| B10 | Status kedaluwarsa     | **DIPUTUSKAN**   | Tagihan belum dibayar lewat 48 jam menjadi `kedaluwarsa` (job tiap jam), pesanan dibatalkan, slot tanggal venue dan stok dilepas. |
| B11 | Kalender dipesan       | **DIPUTUSKAN**   | Status menahan slot/tanggal berlaku untuk pesanan berstatus `menunggu_review`, `disetujui`, dan `terkonfirmasi`. |
| B12 | Unggah media           | **DIPUTUSKAN**   | Foto: JPG/PNG/WebP maks 5 MB. Dokumen: PDF/JPG/PNG maks 5 MB. Bukti transfer: JPG/PNG/PDF maks 5 MB di disk `private`. |
| B13 | Bukti bayar            | **DIPUTUSKAN**   | Format JPG/PNG/PDF maks 5 MB disk `private` via `Media`. Verifikasi Admin mencocokkan jumlah transfer persis. |
| B14 | Verifikasi mitra       | **DIPUTUSKAN**   | Vendor wajib KTP; Venue wajib KTP + bukti venue; katalog tayang hanya setelah terverifikasi. |
| B15 | Peralatan              | **DIPUTUSKAN**   | Satu entitas `catalog_items` dengan kolom `kind` (`layanan`/`peralatan`). |
| B16 | Denah / Layout         | **DIPUTUSKAN**   | Minimal catatan/unggah media layout ruangan venue (tanpa editor visual interaktif). |
| B17 | Rekomendasi paket      | **DIPUTUSKAN**   | Alokasi budget bawaan (Venue 35%, Catering 30%, Dekorasi 15%, Hiburan 10%, Peralatan 5%, Cadangan 5%), keluaran 3 paket: Hemat, Seimbang, Premium. |
| B18 | Lingkungan demo        | **DIPUTUSKAN**   | Demo bar hanya aktif pada environment `local`/`demo`. |
| B19 | Kontak Klien           | **DIPUTUSKAN**   | Klien hanya berhubungan dengan Admin (tombol WhatsApp “Tanya Admin” dari `settings.admin_whatsapp`). Kontak vendor/venue tidak pernah ditampilkan ke Klien; notifikasi penyedia hanya memuat *Acara #ID*, tanpa nama/kontak klien. |
| B20 | Ketersediaan           | **DIPUTUSKAN**   | Tidak ada kalender ketersediaan manual di UI. Ketersediaan dicek otomatis di server (pesanan penahan B11, stok/kuota peralatan, satu venue per tanggal) saat item ditambahkan ke acara dan saat acara diajukan. |
| B21 | Hapus data katalog     | **DIPUTUSKAN**   | Penyedia tidak dapat menghapus item katalog atau venue; gunakan *Nonaktifkan* (item) atau penonaktifan venue oleh Admin. Akun penyedia hanya dibuat lewat pendaftaran mandiri. |
| B22 | Pengaturan sistem      | **DIPUTUSKAN**   | Parameter B1, B2, B3, B7, B9, SLA Admin, B17, dan nomor WhatsApp Admin diubah Admin di `/admin/pengaturan` dengan validasi server; setiap perubahan dicatat `setting_updated`. Perubahan komisi tidak memengaruhi pesanan yang komisinya sudah dikunci. |
| B23 | Hari refund            | **DIPUTUSKAN**   | Sisa hari = selisih tanggal kalender (zona Asia/Jakarta, tanpa jam) antara `event_date` dan **tanggal pengajuan** klien, bukan tanggal persetujuan Admin. Tier dibaca dinamis dari `settings.refund_tiers` dan disimpan di `policy_snapshot`. |
| B24 | Pembulatan refund      | **DIPUTUSKAN**   | Bilangan bulat rupiah. Refund = `intdiv(G × p, 100)` (ke bawah) + kode unik penuh; kompensasi per pesanan = `P − intdiv(P × p, 100)` (ke atas); pendapatan perusahaan = `G − refund − Σ kompensasi` (menyerap sisa). Invariant: refund tanpa kode unik + kompensasi + pendapatan = `G`. Penyebab vendor/force majeure → 100%, kompensasi dan pendapatan 0. |
| B25 | Satu pengajuan aktif   | **DIPUTUSKAN**   | Satu pembatalan aktif (`diajukan`/`ditinjau`/`disetujui`/`refund_diproses`) per acara; pengajuan `ditolak` boleh diajukan ulang. Hanya acara `terkonfirmasi` dengan tagihan `lunas` sampai `event_date` (hari-H = 0%). Klien tidak dapat menarik pengajuan. Acara baru `dibatalkan` saat refund selesai. |
| B26 | Pembatalan otomatis    | **DIPUTUSKAN**   | Acara `diajukan`/`menunggu_pembayaran` dibatalkan langsung oleh klien tanpa baris `cancellations`; pesanan dibatalkan, slot dilepas, tagihan belum dibayar dibatalkan. Diblokir saat bukti bayar `menunggu_validasi`. |
| B27 | Tandai selesai         | **DIPUTUSKAN**   | Admin menandai selesai hanya acara `terkonfirmasi`, setelah `event_date` terlewati, tanpa pembatalan aktif. Pencairan `hak_pesanan` = `lineTotal()` per pesanan; komisi dan biaya kebersihan tidak dicairkan. |
| B28 | Pencairan              | **DIPUTUSKAN**   | Satu pencairan per (`order_item_id`, `type`). Transfer dicatat Admin hanya bila penyedia punya rekening terverifikasi; tanggal ≤ hari ini, bukti privat `BuktiPencairan`. Baris `cair` tidak dapat diubah, dihapus, atau dicatat ulang. Kompensasi manual untuk kasus vendor tidak dibangun. |

## C. Alur Status Sistem

### Acara (`events`)

`draft` → `diajukan` → `menunggu_pembayaran` → `terkonfirmasi` → `selesai` → `dievaluasi`
Cabang: `dibatalkan` (sebelum lunas otomatis, setelah lunas via pengajuan pembatalan).

### Item pesanan (`order_items`)

`menunggu_review` → `disetujui` → `terkonfirmasi` → `selesai`
Cabang: `ditolak`, `dialihkan`, `dibatalkan`. (Catatan: `masa_keberatan` bukan status, melainkan window waktu 24 jam setelah `disetujui`).

### Tagihan (`invoices`)

`belum_dibayar` → `menunggu_validasi` → `lunas`
Cabang: `kedaluwarsa`, `dibatalkan`.

### Pembayaran (`payments`)

`menunggu_validasi` → `valid` atau `ditolak` (klien unggah ulang).

### Keberatan (`order_objections`)

`diajukan` → `ditinjau` → `diterima` atau `ditolak`.

### Pembatalan (`cancellations`)

`diajukan` → `ditinjau` → `disetujui` → `refund_diproses` → `refund_selesai` (atau `ditolak`).

### Pencairan (`disbursements`)

`belum_cair` → `cair`.

### Pengajuan harga katalog (`price_change_requests`)

`menunggu` → `disetujui` atau `ditolak`. Harga aktif berubah hanya saat disetujui.

### Verifikasi penyedia

`menunggu` → `terverifikasi` atau `ditolak`, plus `nonaktif`.

## D. Konsistensi uang

- Simpan rupiah sebagai bilangan bulat. Jangan memakai float.
- Konvensi harga dan kuantitas pesanan: `order_items.provider_price` adalah harga satuan (unit price). Total baris hak penyedia adalah `lineTotal() = provider_price × quantity` (kuantitas default minimal 1). Komisi platform dihitung dari total baris menggunakan pembulatan half-up (`round((lineTotal * commissionPercent) / 100, 0, PHP_ROUND_HALF_UP)`). Pada tagihan (`invoice_items`), baris bertipe `harga_penyedia` mencatat nominal `lineTotal()`, dan untuk item peralatan dengan kuantitas > 1 dicantumkan format deskripsi "Nama item x{qty}".
- Setiap tagihan menyimpan snapshot baris: harga final penyedia, komisi, kebersihan, biaya lain, kode unik, total.
- Perubahan status finansial terjadi di dalam transaksi database, dengan kunci baris tagihan, dan penolakan jika status saat ini tidak mengizinkan aksi.
- Satu tagihan aktif per acara. Versi lama tetap tersimpan dan tidak menerima pembayaran baru.
- Satu pembayaran “menunggu verifikasi” per tagihan. Unggah ulang hanya sah jika status sebelumnya ditolak atau kedaluwarsa.
- Satu pencairan hak per item pesanan. Percobaan kedua ditolak oleh unique constraint.
- Bukti transfer disimpan di disk privat. Response unduhan memeriksa peran. Klien melihat bukti miliknya. Penyedia tidak melihat bukti bayar Klien. Admin melihat bukti yang diperlukan untuk verifikasi dan pencairan.
- Setiap aksi setuju, tolak, cair, dan refund menulis audit log: aktor, waktu, subjek, status lama, status baru, dan alasan.
- Pendapatan Perusahaan = Σ (komisi + kebersihan) tagihan lunas milik acara yang tidak dibatalkan + Σ `company_income` pembatalan `refund_selesai`.
- Bukti refund (`BuktiRefund`) hanya untuk Admin dan klien pemilik acara; bukti pencairan (`BuktiPencairan`) hanya untuk Admin dan penyedia penerima.

## E. Catatan keputusan yang disetujui (2026-11-16)

Keputusan berikut disetujui pengguna pada 2026-11-16. Keputusan lain pada bagian B tetap terbuka dan tidak boleh diisi sendiri.

| Keputusan              | Isi yang disetujui                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| B4 — Biaya kebersihan  | Opsional; dicatat terpisah dari harga sewa; nilainya boleh kosong atau tidak dikenakan. Detail snapshot transaksi mengikuti implementasi transaksi BE-6.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| B15 — Peralatan        | Layanan dan peralatan menggunakan satu struktur katalog; jenis dibedakan melalui atribut `kind`/`type`. Tidak membuat entitas atau tabel peralatan terpisah tanpa kebutuhan teknis yang terbukti.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| B18 — Lingkungan demo  | Demo bar hanya aktif pada environment local/demo dan tidak boleh menjadi bypass autentikasi di production. Tes portal diperbarui setelah authentication dan role diterapkan; pengujian portal menggunakan akun dan role yang sesuai.                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| B12 — Unggah media     | Foto (profil, item katalog, venue): JPG/JPEG/PNG/WebP, maksimal 5 MB per file, maksimal 10 foto per subjek; validasi MIME sisi server. Dokumen verifikasi: PDF/JPG/JPEG/PNG, maksimal 5 MB. Seluruh media disimpan di disk `private` dengan `serve=false`; satu-satunya jalur baca adalah `GET /media/{media}` berotorisasi. Foto berubah menjadi publik saat verifikasi penyedia disetujui Admin; dokumen verifikasi tetap privat selamanya.                                                                                                                                                                                                                                                      |
| B14 — Verifikasi mitra | Dokumen wajib: Vendor = KTP; Pemilik Venue = KTP + bukti venue. NIB dan NPWP opsional; bukti rekening **bukan** persyaratan verifikasi. Visibilitas katalog item dan venue bergantung pada status verifikasi profil penyedia (tanpa persetujuan Admin per item). Venue otomatis mengikuti status verifikasi profil saat disetujui, tetapi Admin dapat menonaktifkan venue secara individual. Satu dokumen aktif per jenis dokumen; unggah ulang menggantikan dokumen lama hanya setelah unggahan baru berhasil. Keputusan Admin mencatat reviewer, alasan (wajib saat menolak), dan waktu pengajuan. Penolakan tidak menonaktifkan akun penyedia; penyedia dapat memperbaiki dan mengajukan ulang. |
| Laravel Boost          | Dependency dan konfigurasi Laravel Boost yang tersedia dipertahankan. Jangan menghapus, memasang ulang, atau memperbarui dependency tanpa persetujuan.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| Wiring frontend        | Disetujui dengan lingkup terbatas: form login, form registrasi, form profil pengguna, dan form perubahan password dalam lingkup autentikasi. Integrasi hanya sebatas menghubungkan frontend dengan backend; tanpa redesign, tanpa perubahan layout, CSS, visual, atau perilaku yang tidak diperlukan.                                                                                                                                                                                                                                                                                                                                                                                              |

Persetujuan ini adalah keputusan bisnis dan lingkup, **bukan** persetujuan implementasi kode. Pembuatan migration, database, model, controller, middleware, route, dan perubahan frontend tetap menunggu persetujuan eksplisit per tahap.

## F. Persetujuan implementasi BE-3 (Gate 4B, 2026-11-16)

Pengguna menyetujui Gate 4A dan seluruh rekomendasi §18 butir 1–10 laporan persiapan BE-3, lalu memberikan persetujuan eksplisit untuk implementasi Gate 4B dengan ketentuan:

1. Lingkup inti A–G; kalender ketersediaan dan pengajuan harga ditunda ke BE-3b; tambah item ke acara draft masuk BE-4/BE-5.
2. Tanpa persetujuan Admin per item katalog; visibilitas mengikuti status verifikasi penyedia.
3. Venue mengikuti verifikasi profil penyedia saat disetujui Admin; Admin dapat menonaktifkan venue individual.
4. Kolom reviewer, alasan verifikasi, dan waktu pengajuan ditambahkan melalui migration additive.
5. Perubahan frontend hanya wiring dan penyesuaian yang diperlukan; tanpa redesign.
6. Disk `private` dengan `serve=false`.
7. Bukti rekening bukan persyaratan verifikasi B14.
8. `cleaning_fee` hanya dapat diubah Admin.
9. Dokumentasi aturan bisnis B12 dan B14 diperbarui (bagian B dan E di atas).
10. Satu dokumen aktif per jenis dokumen verifikasi; unggah ulang menggantikan dokumen lama setelah unggahan baru berhasil.

Hasil implementasi, daftar perubahan, dan sisa pekerjaan dicatat dalam `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md`. Keputusan bagian B yang belum disebut di atas tetap terbuka.

## G. Aturan rundown dan evaluasi (Tahap C, 2026-11-21)

1. Rundown hanya ada pada acara `terkonfirmasi`, `selesai`, dan `dievaluasi`. Pada `draft`, `diajukan`, `menunggu_pembayaran`, dan `dibatalkan` tidak ada rundown.
2. Klien pemilik dapat melihat seluruh rundown acaranya dan menulis semua item **hanya saat acara `terkonfirmasi`**.
3. Vendor dapat melihat rundown acara yang pesanannya berstatus `terkonfirmasi`/`selesai`; vendor hanya dapat menulis item yang ia buat sendiri dan tertaut ke pesanan miliknya yang `terkonfirmasi`.
4. Venue dan Admin hanya melihat rundown; tidak ada route tulis rundown untuk keduanya.
5. Setelah acara `selesai`/`dievaluasi`, rundown terkunci permanen untuk semua peran.
6. `start_time` dan `end_time` berformat `HH:MM` 24 jam; `end_time` (opsional) harus setelah `start_time`. Kegiatan maks 150 karakter, keterangan maks 1000 karakter.
7. Maksimal 100 item rundown per acara. Item yang tumpang tindih diperbolehkan; urutan tampil `start_time` lalu `id`.
8. Evaluasi: satu per acara, final (tidak dapat diubah/dihapus), hanya oleh klien pemilik dan hanya saat acara `selesai`. Pengiriman kedua menghasilkan 409.
9. Subjek evaluasi dihitung server dari pesanan acara berstatus `selesai`: satu venue, satu per vendor (per penyedia, bukan per layanan), dan satu Admin. Pesanan ditolak/dialihkan/dibatalkan tidak dinilai. Subjek asing atau tidak lengkap ditolak 422 tanpa menyimpan apa pun.
10. Rating wajib bilangan bulat 1–5 per subjek; komentar opsional maks 1000, `system_feedback` opsional maks 2000.
11. Evaluasi mengubah status acara `selesai` → `dievaluasi`, menulis AuditLog, dan mengirim notifikasi ke Admin serta penyedia yang dinilai. Tidak ada perubahan pada tagihan, pembayaran, refund, kompensasi, atau pencairan.
12. Rata-rata rating dihitung server dengan satu angka desimal, `null` bila belum ada penilaian. Rating hanya informasi; tidak memengaruhi pencairan atau status penyedia.
13. Penyedia melihat rating dan komentar tentang dirinya saja, tanpa nama klien dan tanpa `system_feedback`. Admin melihat seluruh evaluasi termasuk nama klien dan `system_feedback`.
14. Notifikasi rundown digabung: selama notifikasi jenis sama untuk acara dan penerima yang sama masih belum dibaca, perubahan berikutnya tidak membuat notifikasi baru.
15. Payload notifikasi penyedia hanya memuat "Acara #ID", tautan halaman, dan tanpa nama klien maupun isi komentar.

## H. Estimasi biaya rekomendasi dan denah saat verifikasi (Tahap F, 2026-11-21)

### H.1 Estimasi biaya rekomendasi
1. Estimasi biaya Klien untuk satu kandidat = harga penyedia + komisi layanan + (khusus venue) biaya kebersihan. Semua nominal `int`, aritmetika bilangan bulat.
2. Komisi memakai rumus tagihan yang sama: `intdiv(lineTotal × commissionPercent + 50, 100)` (pembulatan setengah ke atas), dengan `commissionPercent = (int) Setting::get('commission_percent', 10)` dan `lineTotal = provider_price × quantity`. Sumber: `OrderReviewController::approve`.
3. Biaya kebersihan = `venues.cleaning_fee` (null/0 = tidak dikenakan), satu baris per acara, tidak dikalikan jumlah/hari, dan tidak dikenai komisi. Sumber: `InvoiceIssuer::createInvoiceWithItems`.
4. Keluaran kandidat memuat `price` (harga penyedia, sama dengan `order_items.provider_price`), `commission`, `cleaning_fee`, dan `estimate`.
5. Filter (`estimate ≤ alokasi`), urutan (rating menurun → `estimate` menaik → id), dan ringkasan (total/sisa) memakai `estimate`. Prasaring query `harga ≤ alokasi terbesar` tetap aman karena `price ≤ estimate`.
6. `App\Services\ClientCostEstimator` adalah salinan rumus di atas yang hanya membaca `settings`. `OrderReviewController` dan `InvoiceIssuer` tetap sumber kebenaran dan tidak diubah; kesamaan angka dijaga tes pagar alur nyata (tambah item → setujui Admin → terbitkan tagihan) dengan `assertSame` terhadap `invoices.grand_total` tanpa kode unik.
7. Angka pada UI adalah estimasi. Tagihan final dapat berbeda bila `commission_percent` berubah setelah rekomendasi ditampilkan; kode unik tidak termasuk estimasi.

### H.2 Denah pada persetujuan verifikasi
1. Persetujuan verifikasi venue mempublikasikan foto pemilik (B12) **dan** media `denah` pada seluruh ruangan venue milik profil tersebut, di dalam transaksi persetujuan yang sama.
2. Venue yang dinonaktifkan Admin secara individual (`verification_status = nonaktif`) tetap tidak publik; denahnya tidak diubah.
3. Hanya media `denah` berstatus `privat` yang diubah menjadi `publik` (idempoten). Media jenis lain dan denah milik penyedia lain tidak tersentuh.
4. Tidak ada jalur yang mengembalikan foto maupun denah ke `privat` (penolakan, penonaktifan venue, atau pencabutan verifikasi tidak mengubah `visibility`); karena itu denah diperlakukan simetris dengan foto dan tidak ada perilaku pengembalian baru.
5. Untuk database yang sudah berjalan, migration data `2026_11_21_000003_publish_verified_venue_room_layouts` menaikkan denah privat pada venue milik penyedia terverifikasi yang tidak nonaktif. Idempoten; `down()` kosong.

### H.3 Kartu Estimasi Biaya di ringkasan acara (Tahap G1)
1. Kartu Estimasi Biaya di ringkasan acara = Σ(harga + komisi) + kebersihan venue sekali per acara; setelah tagihan terbit memakai `grand_total` tagihan.

## I. Rekening pencairan penyedia (Tahap G, 2026-11-21)

### I.1 Aturan
1. Rekening pencairan **diisi penyedia** (Vendor/Venue) pada halaman Profil usaha dan **diverifikasi Admin**. Admin tidak pernah mengisikan rekening atas nama penyedia.
2. Status memakai enum `VerificationStatus` yang sudah ada: `menunggu`, `terverifikasi`, `ditolak`, `nonaktif`.
3. Rekening **bukan** syarat verifikasi profil dan tidak tampil di katalog. Rekening hanya syarat pencairan; penyedia boleh terverifikasi tanpa rekening.
4. Baris `terverifikasi` **tidak pernah diubah di tempat**. Setiap perubahan membuat/memperbarui baris `menunggu`:
   - bila sudah ada baris `menunggu` untuk profil itu, baris tersebut diperbarui (tidak menumpuk);
   - rekening terverifikasi lama **tetap dipakai** `Disbursement::verifiedBankAccount()` sampai pengajuan baru disetujui;
   - saat pengajuan baru disetujui, baris terverifikasi lama menjadi `nonaktif` (satu rekening terverifikasi per profil, riwayat dipertahankan).
5. Nomor lengkap hanya untuk **Admin** (detail penyedia, halaman Pencairan) dan **pemilik** (halaman Profil). Di dashboard, halaman Pencairan penyedia, notifikasi, dan log aktivitas nomor disamarkan (`•••• 7890`).
6. Setiap pengajuan, persetujuan, dan penolakan menulis `audit_logs` (`provider_bank_account_submitted|approved|rejected`) dengan nomor **tersamar** pada `reason`.
7. Kepemilikan selalu dari profil milik user login; input `owner_id`/`profile_id` diabaikan.

### I.2 Validasi input penyedia
- `bank_name` wajib, maks 60 karakter (boleh bank di luar daftar saran).
- `account_number` wajib, hanya digit, 6–20 digit; spasi dan tanda hubung dibuang sebelum validasi dan penyimpanan.
- `account_name` wajib, maks 100 karakter.

### I.3 Alur
1. Penyedia membuka Profil usaha → kartu "Rekening pencairan" → kirim pengajuan (`POST /vendor/rekening` atau `POST /venue/rekening`).
2. Baris `menunggu` dibuat/diperbarui; Admin menerima `ProviderBankAccountSubmittedNotification` (payload tanpa nomor lengkap).
3. Admin membuka detail penyedia → **Setujui** (`admin.rekening-penyedia.approve`) atau **Tolak** dengan alasan wajib (`admin.rekening-penyedia.reject`). Keputusan berjalan dalam `DB::transaction` + `lockForUpdate`; hanya baris `menunggu` yang dapat diputuskan.
4. Penyedia menerima `ProviderBankAccountDecidedNotification` (alasan tampil bila ditolak).
5. Halaman Pencairan Admin menampilkan rekening aktif (`BCA · 1234567890` + `a.n. …`), "Rekening menunggu verifikasi" + tautan **Verifikasi rekening**, atau "Penyedia belum mengisi rekening". Tombol **Catat transfer** hanya aktif bila ada rekening terverifikasi (logika `DisbursementService` tidak diubah).

### I.4 Tidak berubah
- Tagihan, pembayaran, refund, kompensasi, dan logika pencairan tidak diubah. `DisbursementService`, `InvoiceIssuer`, `Admin\PaymentController`, `Admin\OrderReviewController`, `CancellationService`, `RefundCalculator`, `EventCompletionService`, dan `Disbursement::verifiedBankAccount()` tetap seperti semula.

### I.5 Data demo
`LocalDemoSeeder` memberi Dapur Nusantara (`BCA 1234567890`) dan Graha Melati (`Bank Mandiri 0987654321`) satu rekening `terverifikasi` (nomor fiktif). Untuk database lokal yang sudah berjalan: `php artisan db:seed --class=LocalDemoSeeder` atau isi rekening lewat UI.
