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
