# Aturan Bisnis Backend — Pesta Kita

Sumber: `docs/AI_AGENT_CONTEXT.md` bagian 4, roadmap Tahap 6, keputusan pengguna, dan perilaku backend. Keputusan yang sudah disetujui dicatat di bagian E dan H; keputusan yang masih terbuka tidak boleh ditebak.

## A. Aturan terkunci

1. Pesta Kita adalah managed marketplace. Admin mengendalikan persetujuan pesanan pada MVP.
2. Klien membayar satu tagihan gabungan untuk satu acara.
3. Pembayaran MVP adalah transfer manual ke rekening perusahaan. Tidak ada payment gateway sampai pengguna menyetujui.
4. Komisi 10% ditambahkan pada tagihan Klien. Komisi tidak dipotong dari hak Vendor atau Pemilik Venue.
5. Tiga nominal wajib terpisah: harga katalog, harga usulan penyedia, harga final yang disahkan Admin. Frontend tidak menetapkan harga final.
6. Pencairan hak penyedia dilakukan Admin setelah acara selesai.
7. Kontak pribadi penyedia (telepon, email usaha, penanggung jawab, rekening utuh) tidak ditampilkan ke Klien.
8. Ulasan hanya setelah acara selesai dan transaksi memenuhi syarat. Penyedia melihat agregat, tanpa identitas Klien. Penilaian untuk Pesta Kita dan umpan balik aplikasi bersifat internal.
9. Backend adalah sumber kebenaran nominal, kode unik transfer, refund, payout, dan status.
10. Jika kebijakan refund disetujui, kebijakan yang berlaku saat transaksi harus disimpan sebagai snapshot. Sampai B1 disetujui, refund hanya menerima permohonan dan tidak memiliki keputusan atau nominal final.
11. Perubahan harga katalog tidak mengubah pesanan yang sudah disetujui. Selama pengajuan harga berstatus menunggu, harga katalog lama tetap berlaku.
12. Nama Klien tidak ditampilkan pada daftar pesanan penyedia. Komunikasi lewat Pesta Kita.
13. Akun Admin tidak dibuat dari formulir daftar publik. UI daftar hanya menawarkan Klien, Vendor, dan Pemilik Venue.
14. Satu akun pada UI daftar memilih tepat satu peran.

## B. Keputusan yang masih terbuka

Jangan mengisi migration atau menambah alur final untuk hal yang masih ditunda di tabel ini.

| No  | Topik                  | Bukti di UI                                                                         | Yang perlu diputuskan                                                                                                                                                                                                                                              |
| --- | ---------------------- | ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| B1  | Kebijakan refund       | Detail refund tidak menetapkan persentase atau nominal.                              | **DITUNDA:** hanya pencatatan permohonan Klien yang diizinkan. Persentase, tenggat, kompensasi, keputusan final, nominal, dan transfer refund belum disepakati dan tidak boleh dikodekan.                                                                          |
| B2  | Kode unik              | Tagihan perlu kode unik transfer.                                                    | **DISETUJUI:** tepat tiga digit, unik di antara invoice aktif, dan ditambahkan ke total transfer.                                                                                                                                                                    |
| B3  | Dasar komisi           | Baris venue, katering, dan sound punya kolom biaya layanan. Baris kebersihan tidak. | **DISETUJUI:** komisi 10% dihitung dari harga final penyedia saja; tidak atas biaya kebersihan, deposit, atau kode unik.                                                                                                                                             |
| B4  | Biaya kebersihan       | Form venue: biaya kebersihan hanya diubah Admin.                                    | **DISETUJUI (2026-11-16):** opsional, dicatat terpisah dari harga sewa, boleh kosong/tidak dikenakan; snapshot transaksi menyusul di BE-6. Lihat bagian E.                                                                                                         |
| B5  | Deposit venue          | Form venue punya field deposit. Tagihan contoh tidak memuat deposit.                | Deposit masuk MVP atau ditunda.                                                                                                                                                                                                                                    |
| B6  | Jumlah venue per acara | Satu acara dapat membutuhkan beberapa ruangan/lokasi.                               | **DISETUJUI:** satu acara dapat memiliki beberapa venue.                                                                                                                                                                                                            |
| B7  | Masa keberatan         | UI penyedia menyebut 24 jam.                                                        | **DISETUJUI:** 24 jam dihitung sejak persetujuan Admin; invoice menunggu seluruh keputusan dan masa keberatan selesai.                                                                                                                                              |
| B8  | Versi tagihan          | Admin dapat menerbitkan ulang tagihan.                                               | **DISETUJUI:** versi baru menggantikan invoice aktif sebelumnya; versi lama dinonaktifkan dan tetap menjadi riwayat. Invoice lunas tidak dapat diterbitkan ulang.                                                                                                  |
| B9  | Batas bayar            | Contoh menampilkan tanggal dan jam 23.59 WIB.                                       | **DISETUJUI:** jatuh tempo H-7 pukul 23.59 WIB; jika diterbitkan setelah H-7, 24 jam sejak penerbitan. Implementasi membatasi jatuh tempo pada akhir tanggal acara karena skema belum mencatat jam acara.                                                            |
| B10 | Status kedaluwarsa     | Roadmap pembayaran menyebut kedaluwarsa jika didukung.                              | **DISETUJUI:** invoice kedaluwarsa menolak pembayaran baru, tetapi tidak membatalkan acara/pesanan otomatis.                                                                                                                                                         |
| B11 | Kalender dipesan       | Tahap 5.6 menunda status booked dan diblokir.                                       | Penyedia boleh menandai tidak tersedia. Status dipesan hanya berasal dari pesanan terkonfirmasi. Perlu konfirmasi.                                                                                                                                                 |
| B12 | Unggah media           | Profil menandai JPG/PNG 5 MB sebagai konfigurasi contoh.                            | **DISETUJUI (2026-11-16, Gate 4B):** foto JPG/JPEG/PNG/WebP maks 5 MB, maks 10 per subjek; dokumen verifikasi PDF/JPG/JPEG/PNG maks 5 MB; disk `private` dengan `serve=false`; foto menjadi publik hanya setelah penyedia terverifikasi. Lihat bagian E.           |
| B13 | Bukti bayar            | Bukti disimpan di disk privat.                                                      | **DISETUJUI:** retensi lima tahun sejak acara ditutup; setelahnya file dihapus dan path bukti dibersihkan. Validasi saat ini menerima JPG/JPEG/PNG/PDF maksimal 5 MB; tipe/ukuran ini mengikuti implementasi UI dan bukan keputusan kebijakan terpisah.             |
| B14 | Verifikasi mitra       | UI: ubah data rekening butuh verifikasi ulang.                                      | **DISETUJUI (2026-11-16, Gate 4B):** Vendor wajib KTP (NIB/NPWP opsional); Venue wajib KTP + bukti venue (NPWP opsional); bukti rekening bukan persyaratan; katalog/venue tayang hanya setelah profil terverifikasi; satu dokumen aktif per jenis. Lihat bagian E. |
| B15 | Peralatan              | Ada katalog dan form peralatan terpisah dari layanan.                               | **DISETUJUI (2026-11-16):** satu struktur katalog dengan atribut `kind`/`type`; tanpa entitas/tabel peralatan terpisah. Lihat bagian E.                                                                                                                            |
| B16 | Denah                  | Form venue dan roadmap Tahap 7 menyebut layout.                                     | MVP cukup unggah dokumen, atau editor visual ditunda.                                                                                                                                                                                                              |
| B17 | Rekomendasi paket      | `klien/rekomendasi` masih prototipe.                                                | Masuk MVP backend atau tetap di luar sampai ada aturan pencocokan budget.                                                                                                                                                                                          |
| B18 | Lingkungan demo        | Demo bar dan tes portal mengizinkan akses tanpa login.                              | **DISETUJUI (2026-11-16):** demo bar hanya local/demo dan bukan bypass auth; tes portal diperbarui setelah auth hidup, memakai akun dan role sesuai. Lihat bagian E.                                                                                               |

## C. Alur status dan batas implementasi

### Acara

`draft` → `diajukan` → `menunggu_pembayaran` → `terkonfirmasi` → `selesai` → `dinilai`

Cabang `dibatalkan` hanya tersedia setelah aturan pembatalan disetujui. Halaman Klien menyediakan draft, diajukan, menunggu pembayaran, terkonfirmasi, dibatalkan, riwayat, dan evaluasi.

### Item pesanan

`menunggu_admin` → `disetujui` atau `ditolak` → jika disetujui, `masa_keberatan` → `mengikat`.

Penolakan satu item tidak otomatis membatalkan acara. UI `acara-diajukan` menampilkan item ditolak dan meminta keputusan Klien.

### Permintaan penawaran

`diajukan` → `diusulkan_penyedia` → `disetujui_admin` atau `ditolak_admin`.

Harga katalog tetap tersimpan sebagai snapshot. Harga usulan tidak menjadi harga final sebelum keputusan Admin.

Implementasi BE-4 memakai alur di atas. Proposal penyedia terbaru di database menjadi sumber harga saat Admin menyetujui; harga final tidak berasal dari input browser. Satu quote hanya dapat diputuskan sekali. Status disetujui maupun ditolak bersifat final pada skema sekarang; pengusulan ulang setelah keputusan belum tersedia dan memerlukan perubahan alur/skema yang disetujui.

### Tagihan dan pembayaran

`menunggu_pembayaran` → `bukti_diunggah` → `menunggu_verifikasi` → `lunas` atau `bukti_ditolak`.

`bukti_ditolak` kembali ke unggah ulang. Status lunas hanya ditulis server setelah Admin mengesahkan jumlah yang cocok, termasuk kode unik.

### Refund

Implementasi saat ini hanya menyimpan status `diajukan` dan menampilkan antrean/tinjauan Admin. Status keputusan, nominal final, proses transfer, dan status selesai **belum tersedia** sampai B1 disetujui.

Jangan menghitung atau menjanjikan nominal refund dari frontend maupun backend sebelum snapshot kebijakan dan keputusan B1 disetujui.

### Pencairan

Admin mencatat hak penyedia setelah acara selesai dan tagihan lunas. Pencairan dicatat satu kali per item pesanan; nilai dan detail rekening/bukti disimpan sebagai snapshot. Kompensasi refund belum tersedia.

### Pengajuan harga katalog

`menunggu` → `disetujui` atau `ditolak`. Harga aktif berubah hanya saat disetujui.

### Verifikasi penyedia

`menunggu` → `terverifikasi` atau `ditolak`, plus `nonaktif` yang menyembunyikan katalog tanpa menghapus riwayat.

## D. Konsistensi uang

- Simpan rupiah sebagai bilangan bulat. Jangan memakai float.
- Setiap tagihan menyimpan snapshot baris: harga final penyedia, komisi, kebersihan, biaya lain, kode unik, total.
- Perubahan status finansial terjadi di dalam transaksi database, dengan kunci baris tagihan, dan penolakan jika status saat ini tidak mengizinkan aksi.
- Satu tagihan aktif per acara. Versi lama tetap tersimpan dan tidak menerima pembayaran baru.
- Satu pembayaran “menunggu verifikasi” per tagihan. Unggah ulang hanya sah jika status sebelumnya ditolak atau kedaluwarsa.
- Satu pencairan hak per item pesanan. Percobaan kedua ditolak oleh unique constraint.
- Bukti transfer disimpan di disk privat. Response unduhan memeriksa peran. Klien melihat bukti miliknya. Penyedia tidak melihat bukti bayar Klien. Admin melihat bukti yang diperlukan untuk verifikasi dan pencairan.
- Setiap keputusan status dan pencatatan permohonan refund menulis audit log: aktor, waktu, subjek, status lama, status baru, dan alasan.

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

## G. Catatan implementasi BE-4 (2026-11-17)

- Quote dapat merujuk ke item katalog Vendor dengan mode perlu penawaran atau ruangan venue publik/terverifikasi. Harga katalog diambil server saat pengajuan sebagai snapshot.
- `event_id` opsional dan hanya menerima acara draft milik Klien. BE-4 tidak membuat acara atau menambahkan item ke pesanan.
- Proposal Vendor/Venue hanya dapat dibuat untuk subjek milik akun login dan saat quote berstatus `diajukan`. Detail penyedia tidak menampilkan nama Klien atau nama acara.
- Admin hanya dapat memutuskan quote berstatus `diusulkan_penyedia`; persetujuan memakai proposal terakhir tersimpan. Penolakan mewajibkan alasan. Keputusan dan perubahan status dicatat di audit log dalam transaksi.
- Keputusan disimpan satu kali per quote. Penolakan final dan tidak ada siklus usulan ulang pada implementasi saat ini; jangan menganggap ini sebagai kebijakan daur ulang yang dapat diubah tanpa penyesuaian skema dan persetujuan produk.
- Tidak ada notifikasi yang dikirim pada tahap ini.

## H. Keputusan dan implementasi BE-5–BE-7 (2026-11-17)

- Pengguna menyetujui pelaksanaan BE-5, BE-6, dan BE-7 dengan rekomendasi yang dicatat pada dokumen ini.
- Satu acara dapat memuat beberapa venue. Keberatan penyedia berlangsung 24 jam sejak Admin menyetujui item; invoice menunggu seluruh keputusan dan masa keberatan selesai.
- Kode unik invoice tiga digit masuk dalam total transfer. Komisi 10% hanya dihitung dari harga final penyedia. Versi invoice baru menggantikan versi aktif sebelumnya; kedaluwarsa tidak membatalkan pesanan otomatis.
- Jatuh tempo invoice adalah H-7 pukul 23.59 WIB atau 24 jam setelah penerbitan jika lewat H-7. Karena waktu acara belum disimpan, implementasi membatasi deadline ke akhir tanggal acara.
- Bukti pembayaran/pencairan disimpan privat dan dihapus lima tahun setelah acara ditutup.
- Refund hanya menerima permohonan. Kebijakan, keputusan final, nominal, dan transfer tetap ditunda; UI tidak menyediakan kontrol keputusan Admin.
- Rundown dikelola Klien dan Vendor terkait; Venue/Admin hanya membaca. Ulasan Klien tersedia setelah acara selesai dan tagihan lunas, sementara penyedia hanya melihat agregat anonim.
