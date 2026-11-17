# Kontrak Integrasi Frontend–Backend — Pesta Kita

Status: diperbarui setelah implementasi backlog wiring. Aplikasi memakai form dan route web Blade, bukan API JSON publik. BE-1 sampai BE-7 dan fitur backlog yang disetujui memiliki route aktif. Refund tetap terbatas pada penerimaan permohonan; keputusan dan nominal final belum tersedia.

Kontrak ini memakai form POST dan Blade, sesuai aplikasi saat ini. Bukan API JSON publik. Kalender tetap membaca JSON yang diletakkan controller di halaman yang sama (`#kal-config`), karena `kalender.js` sudah bekerja seperti itu.

Setiap aksi tulis menolak peran yang salah dengan 403. Halaman milik peran lain tidak boleh 200 untuk pengguna yang salah.

## 1. Route aktif yang relevan

| Method | Path | Perilaku aktual |
|---|---|---|
| GET | `/login`, `/register` | Form autentikasi untuk guest |
| POST | `/login`, `/register`, `/logout` | Login, registrasi, dan logout BE-1 |
| POST | `/profil`, `/profil/sandi` | Perbarui profil dan kata sandi |
| GET | `/{role}/notifikasi` | Inbox notifikasi untuk role portal login |
| POST | `/{role}/notifikasi/{notification}/baca`, `/{role}/notifikasi/baca-semua` | Tandai notifikasi milik akun login sebagai dibaca |
| GET | `/klien/rekomendasi` | Filter item katalog/venue publik menurut kategori, area, tanggal, dan budget |
| POST | `/venue/data/{venue}/denah` | Unggah/replace file denah venue milik pemilik |
| GET | `/media/{media}` | Baca media melalui policy; denah untuk Klien hanya jika venue terverifikasi |
| GET/POST | `/{role}/rekening-pencairan` | Daftar rekening payout milik Vendor/Venue dan ajukan rekening baru |
| GET | `/admin/rekening-penyedia` | Antrean review rekening payout penyedia |
| POST | `/admin/rekening-penyedia/{account}` | Setujui atau tolak rekening payout; catatan wajib saat menolak |
| GET | `/admin/pengguna`, `/admin/pengguna-detail/{user}` | Daftar dan lihat akun pengguna |
| POST | `/admin/pengguna/{user}/status` | Ubah status aktif/nonaktif tanpa mengganti peran |
| GET/POST | `/klien/permintaan-penawaran` | Daftar dan pengajuan quote Klien |
| GET | `/klien/permintaan-penawaran-detail/{quoteRequest}` | Detail quote milik Klien |
| GET | `/vendor/permintaan-penawaran` | Daftar quote untuk item katalog milik Vendor |
| GET/POST | `/vendor/permintaan-penawaran-detail/{quoteRequest}` | Detail dan usulan harga Vendor |
| GET | `/venue/permintaan-penawaran` | Daftar quote untuk ruangan milik Pemilik Venue |
| GET/POST | `/venue/permintaan-penawaran-detail/{quoteRequest}` | Detail dan usulan harga Pemilik Venue |
| GET | `/admin/permintaan-penawaran` | Antrean Admin dengan filter status |
| GET | `/admin/permintaan-penawaran-detail`, `/admin/permintaan-penawaran-detail/{quoteRequest}` | Empty state kompatibilitas dan detail quote |
| POST | `/admin/permintaan-penawaran/{quoteRequest}/{action}` | Keputusan `approve` atau `reject` |
| POST | `/klien/acara`, `/klien/acara/{event}/items`, `/klien/acara/{event}/submit` | Buat draft, tambahkan item, dan ajukan acara |
| GET | `/klien/acara-detail/{event}`, `/klien/acara-draft`, `/klien/acara-diajukan`, `/klien/acara-terkonfirmasi`, `/klien/acara-dibatalkan` | Detail dan daftar acara milik Klien |
| GET/POST | `/vendor/pesanan-detail/{orderItem}`, `/vendor/pesanan-detail/{orderItem}/keberatan` | Detail item milik Vendor dan ajukan keberatan |
| GET/POST | `/venue/pesanan-detail/{orderItem}`, `/venue/pesanan-detail/{orderItem}/keberatan` | Detail item milik Venue dan ajukan keberatan |
| GET | `/admin/pesanan`, `/admin/keberatan`, `/admin/pesanan-detail/{orderItem}`, `/admin/keberatan-detail/{orderObjection}` | Antrean dan detail pesanan/keberatan |
| POST | `/admin/pesanan/{orderItem}/{action}`, `/admin/keberatan/{orderObjection}/{action}` | Keputusan item dan keberatan oleh Admin |
| POST | `/admin/acara/{event}/tagihan` | Terbitkan invoice setelah seluruh item dan masa keberatan selesai |
| GET | `/klien/tagihan/{invoice}` | Lihat invoice milik Klien |
| POST | `/klien/tagihan/{invoice}/pembayaran` | Unggah bukti pembayaran |
| GET | `/admin/pembayaran/{invoice}` | Tinjau pembayaran invoice |
| POST | `/admin/pembayaran-bukti/{payment}/{action}` | Verifikasi bukti dan nominal oleh Admin |
| GET/POST | `/klien/acara/{event}/refund` | Catat permohonan refund |
| GET | `/admin/pembatalan/{refundRequest}` | Tinjau permohonan tanpa keputusan final |
| GET/POST | `/admin/pencairan/{orderItem}` | Catat pencairan satu kali per item dan akses bukti |
| GET | `/admin/rekening`, `/admin/rekening-form`, `/admin/rekening-form/{companyBankAccount}` | Daftar, form rekening baru, dan form edit |
| POST/PUT | `/admin/rekening`, `/admin/rekening/{companyBankAccount}`, `/admin/rekening/{companyBankAccount}/status` | Simpan atau ubah rekening perusahaan |
| GET | `/admin/acara`, `/admin/acara/{event}` | Pantau daftar dan detail acara |
| POST | `/admin/acara/{event}/selesai` | Tandai acara selesai setelah syarat terpenuhi |
| GET | `/vendor/pencairan/{payout}`, `/venue/pencairan/{payout}` | Riwayat dan bukti pencairan milik penyedia |
| GET/POST | `/klien/acara/{event}/rundown`, `/vendor/rundown/{event}` | Baca dan tambah segmen rundown sesuai kepemilikan |
| PUT/DELETE | `/klien/acara/{event}/rundown/{rundownItem}`, `/vendor/rundown/{event}/{rundownItem}` | Ubah atau hapus segmen milik pengguna |
| GET | `/venue/rundown`, `/admin/rundown`, `/vendor/ulasan`, `/venue/ulasan` | Rundown read-only dan agregat ulasan anonim |
| POST | `/klien/acara/{event}/ulasan/{orderItem}` | Ulasan Klien setelah acara selesai dan invoice lunas |
| GET/POST | `/klien/acara/{event}/checklist` | Lihat dan tambah item checklist acara milik Klien |
| PUT/DELETE | `/klien/acara/{event}/checklist/{item}` | Ubah atau hapus item checklist milik acara tersebut |
| GET | `/vendor/checklist/{event}`, `/venue/checklist/{event}`, `/admin/acara/{event}/checklist` | Checklist baca-saja untuk penyedia terkait dan Admin |
| GET | `/up` | Health Laravel |

Route portal memakai middleware autentikasi dan role terkait. Route tulis eksplisit didaftarkan sebelum catch-all Blade. Route katalog, kalender, ketersediaan, dan pengajuan harga BE-3/BE-3b juga aktif; rincian tahap itu ada di laporan implementasinya.

## 2. Matriks kebutuhan

| Modul | Role | Kebutuhan frontend | Backend saat ini | Kekurangan |
|---|---|---|---|---|
| Katalog Vendor | Klien | Daftar dan detail: usaha, kategori, area, mode harga tetap vs penawaran, verifikasi, paket, kebijakan, galeri. | Backend BE-3: data publik hanya dari penyedia terverifikasi. | Sebagian filter dan fitur lanjutan masih di luar tahap. |
| Katalog Venue | Klien | Jenis, lokasi, kapasitas, fasilitas, sewa, kebersihan, aturan, kebijakan, ketersediaan perlu konfirmasi. | Backend BE-3: venue/ruangan publik mengikuti verifikasi penyedia. | Status ketersediaan tetap perlu konfirmasi; bukan jaminan booking. |
| Peralatan | Klien, Vendor | Katalog sewa dan form alat. | Terhubung melalui struktur katalog bersama dengan atribut `kind` (B15). | Fitur inventori/stok belum ada. |
| Profil | Semua | Profil usaha, portofolio, verifikasi Admin, ganti sandi. Publik tanpa kontak pribadi. | Profil, kata sandi, verifikasi, media, dan pengajuan rekening payout Vendor/Venue terhubung sesuai role. Rekening payout baru harus disetujui Admin. | Perubahan rekening dibuat sebagai pengajuan baru; rekening terverifikasi lama tetap dapat digunakan. |
| Request Quote | Empat role | Klien mengajukan. Penyedia mengusulkan. Admin memutuskan. Tiga nominal terpisah. | BE-4 aktif untuk Klien, Vendor, Venue, Admin; snapshot harga katalog, proposal, keputusan, dan audit disimpan server. | Tidak membuat acara baru; tautan acara draft opsional. Keputusan final tidak dapat diulang. |
| Kalender | Klien, Vendor, Venue | Status per tanggal. Venue per ruangan. Klien dapat meneruskan tanggal ke penawaran. | BE-3b aktif: penyedia mengubah ketersediaan; Klien membaca jadwal. | Status dipesan tetap berasal dari pesanan, belum ada booking/lock tanggal. |
| Pesanan | Semua | Tahap acara, keputusan Admin, keberatan penyedia, checklist acara, tanpa nama Klien di sisi penyedia. | BE-5 aktif: draft dan item pesanan, keputusan Admin, keberatan 24 jam sejak persetujuan, banyak venue per acara. Checklist dikelola Klien; penyedia terkait dan Admin baca-saja. | Invoice menunggu seluruh keputusan dan masa keberatan. |
| Tagihan | Klien, Admin | Invoice gabungan: harga penyedia, komisi, kebersihan, kode unik, total, versi. | BE-6 aktif: kode unik 3 digit masuk total; komisi 10% dari harga penyedia; versi baru menggantikan versi lama. Halaman invoice dapat dicetak/disimpan sebagai PDF melalui browser. | Tidak ada renderer PDF server-side. Deadline H-7 pukul 23.59 WIB atau 24 jam setelah penerbitan; waktu acara belum tersedia dalam skema. |
| Pembayaran | Klien, Admin | Pilih rekening perusahaan, unggah bukti, validasi Admin, nota hanya jika lunas. | BE-6 aktif: bukti privat; Admin harus mencocokkan nominal. | Invoice kedaluwarsa menolak pembayaran baru, tetapi tidak membatalkan acara otomatis. |
| Refund | Klien, Admin | Ajukan alasan dan tinjau status; tanpa hitung lokal. | BE-6 menerima permohonan dan menampilkan antrean/tinjauan. | Keputusan, persentase, nominal final, dan transfer ditunda sampai kebijakan B1 disetujui. |
| Pencairan | Vendor, Venue, Admin | Hak penyedia, rekening tersamar, bukti cair. | BE-6 aktif: satu pencairan per item setelah acara selesai; penyedia hanya mengakses bukti miliknya. Review rekening payout penyedia oleh Admin juga tersedia. | Kompensasi refund belum tersedia. |
| Koordinasi | Semua | Rundown, checklist, notifikasi, rekomendasi, unggahan denah, dan ulasan setelah selesai. | BE-7 aktif; checklist Klien/Vendor terkait/Admin (yang terakhir read-only); notifikasi in-app database-only; rekomendasi dengan filter; denah melalui media ber-policy. | Tidak ada editor visual layout; rekomendasi hanya memberi estimasi dan tidak membuat pesanan. |
| Rekening perusahaan | Admin | Daftar dan form rekening tujuan transfer. | BE-6 CRUD Admin aktif dengan validasi dan masking. | Integrasi payment gateway tidak ada. |
| Pengajuan harga | Vendor, Venue, Admin | Harga lama berlaku sampai disetujui. | BE-3b aktif; keputusan Admin dan perubahan harga tercatat. | Tidak mengubah pesanan lama. |
| Pengguna dan audit | Admin | Lihat akun, aktifkan/nonaktifkan tanpa mengubah peran, log aktivitas, ringkasan. | Audit log tersedia; manajemen status akun, komisi invoice lunas, dan agregat rating penyedia ditampilkan dari database. | Akun Admin tidak dapat menonaktifkan dirinya sendiri. |

## 3. Field yang sudah terlihat di form

| Halaman | Field | Pemilik data |
|---|---|---|
| `auth/register` | peran, nama, email, telepon, nama usaha, kategori, alamat, sandi | Pengguna baru. Admin tidak ada di form. |
| `klien/acara-baru` | nama, tanggal, tamu, tema, deskripsi | Klien |
| `klien/permintaan-penawaran` | `subject`, `event_id` opsional, `event_date`, `guest_count`, `budget`, `notes` | Klien. `subject` harus berupa item/ruangan publik; harga katalog diambil server. |
| `vendor/permintaan-penawaran-detail` | `proposed_amount`, `notes` | Vendor pemilik item; nominal bilangan bulat rupiah. |
| `venue/permintaan-penawaran-detail` | `proposed_amount`, `notes` | Pemilik ruangan venue; nominal bilangan bulat rupiah. |
| `admin/permintaan-penawaran-detail` | aksi `approve`/`reject`, `reason` | Admin. Alasan wajib saat menolak; harga disetujui berasal dari proposal terbaru di server. |
| `vendor/item-form` | nama, kategori, kuota harian, deskripsi, harga, alasan perubahan | Vendor, lalu Admin untuk harga baru |
| `venue/form` | nama, jenis, kapasitas, alamat, deskripsi, harga sewa, alasan, kebersihan read-only, durasi, deposit, fasilitas, ketentuan | Venue. Kebersihan menunggu B4. Deposit menunggu B5. |
| Profil penyedia | usaha, kategori, telepon, alamat, penanggung jawab, email, sandi, foto | Pemilik; rekening payout dikelola melalui workflow pengajuan dan review Admin terpisah. |
| `klien/tagihan` | rekening tujuan, bukti | Klien. Total tidak diinput. |
| `admin/pencairan-detail` | tanggal transfer, bukti, catatan | Admin |
| `admin/pembatalan-detail` | tanggal transfer refund, bukti | Admin. Nominal tidak diinput Klien. |

## 4. Route implementasi dan usulan

Route POST eksplisit harus didaftarkan sebelum catch-all `/{page}`. Rute BE-1 sampai BE-7 sudah dibuat; detail resource memakai route dengan parameter ID, bukan catch-all view.

### BE-1

- POST `/login` — tamu — sesi dan redirect sesuai role.
- POST `/logout` — pengguna login — hapus sesi.
- POST `/register` — tamu — buat Klien aktif, atau Vendor/Venue menunggu verifikasi.
- GET dan POST profil serta ganti sandi per peran.
- Middleware `auth` dan `role` pada prefix portal.

### BE-3

- GET katalog/detail terverifikasi, CRUD katalog/venue, media privat, verifikasi, kalender, dan pengajuan harga sudah aktif (BE-3/BE-3b).
- Penambahan item ke acara draft kini aktif melalui route BE-5 yang didokumentasikan di bawah.

### BE-4

- Aktif: `POST /klien/permintaan-penawaran` — menyimpan quote untuk item/ruangan publik, snapshot harga katalog, dan `event_id` draft milik Klien bila dipilih.
- Aktif: `POST /vendor/permintaan-penawaran-detail/{quoteRequest}` dan `POST /venue/permintaan-penawaran-detail/{quoteRequest}` — usulan hanya oleh pemilik subjek dan hanya saat status menunggu usulan.
- Aktif: `POST /admin/permintaan-penawaran/{quoteRequest}/approve|reject` — Admin memutuskan proposal terbaru; harga disetujui diambil dari server. Penolakan memerlukan alasan.
- Semua keputusan berstatus final pada versi skema saat ini; usulan/keputusan ulang tidak tersedia. Pengajuan quote tidak membuat acara; perubahan penting menghasilkan notifikasi in-app.

### BE-5

- Aktif: `POST /klien/acara` membuat draft; `PUT /klien/acara/{event}` memperbarui draft; `POST /klien/acara/{event}/items` dan `DELETE /klien/acara/{event}/items/{orderItem}` mengelola item.
- Aktif: `POST /klien/acara/{event}/submit` mengajukan acara; Admin memakai `POST /admin/pesanan/{orderItem}/approve|reject`, alasan wajib saat menolak.
- Aktif: Vendor/Venue mengirim keberatan melalui `POST /{role}/pesanan-detail/{orderItem}/keberatan`; Admin memutuskan melalui `POST /admin/keberatan/{orderObjection}/approve|reject`.
- Aktif: Klien mengelola checklist melalui `GET/POST /klien/acara/{event}/checklist` dan `PUT/DELETE /klien/acara/{event}/checklist/{item}`. Vendor/Venue yang terkait serta Admin hanya membaca; checklist tidak dapat diubah setelah acara ditutup.
- Batas keberatan 24 jam sejak persetujuan Admin. Invoice hanya dapat diterbitkan setelah seluruh item dan keberatan berstatus final. Satu acara dapat mempunyai beberapa venue.

### BE-6

- Aktif: `POST /admin/acara/{event}/tagihan` menghitung snapshot harga, komisi 10% dari harga penyedia, kebersihan, kode unik 3 digit, dan total.
- Aktif: Klien pemilik invoice mengunggah bukti melalui `POST /klien/tagihan/{invoice}/pembayaran`; Admin menyetujui/menolak bukti. Persetujuan gagal jika nominal tidak sama dengan total invoice.
- Invoice baru menonaktifkan versi sebelumnya. Deadline H-7 pukul 23.59 WIB atau 24 jam setelah penerbitan bila lewat H-7, dibatasi akhir tanggal acara karena jam acara belum tersimpan. Invoice kedaluwarsa tidak membatalkan pesanan otomatis.
- Bukti bayar disimpan privat selama lima tahun sejak acara ditutup; setelahnya file dihapus dan path dibersihkan.
- Refund hanya menerima permohonan Klien serta antrean tinjauan Admin. Tidak ada endpoint keputusan, nominal final, atau transfer refund sampai B1 disepakati.
- Aktif: Admin mencatat pencairan melalui `/admin/pencairan/{orderItem}`; unique constraint menolak pencairan kedua. Vendor/Venue hanya dapat mengakses riwayat dan bukti miliknya.

### BE-7

- Aktif: Klien dan Vendor pemilik pesanan mengelola rundown melalui `POST/PUT/DELETE` pada route acara masing-masing. Venue dan Admin hanya membaca.
- Aktif: Klien mengirim ulasan melalui `POST /klien/acara/{event}/ulasan/{orderItem}` setelah acara selesai dan invoice lunas.
- Aktif: Vendor/Venue membaca agregat tanpa identitas penulis.
- Aktif: Notifikasi database-only tampil dalam inbox portal setiap role, dengan aksi tandai dibaca.
- Aktif: Rekomendasi Klien memakai filter kategori, area, tanggal, dan budget; rekomendasi bukan pemesanan.
- Aktif: Denah diunggah sebagai file privat melalui `POST /venue/data/{venue}/denah`; file hanya dapat dibaca melalui policy media. Tidak ada editor visual.
- Aktif: Pengajuan rekening payout Vendor/Venue menunggu review Admin; rekening baru tidak dapat dipakai untuk pencairan sebelum disetujui.
- Aktif: Admin dapat melihat akun dan mengubah status aktif/nonaktif tanpa mengganti peran.
- Aktif: Dashboard Admin menghitung komisi dari invoice lunas dan agregat rating penyedia. Halaman invoice menggunakan print/save-as-PDF browser, bukan PDF server-side.

## 5. Validasi saat implementasi

- Setiap id acara, item, dan tagihan dicek kepemilikan atau peran; quote memvalidasi acara draft milik Klien dan subjek yang masih publik.
- Harga katalog, proposal terakhir, dan harga yang disetujui tetap dibaca/disimpan terpisah oleh server; field harga final dari Klien tidak diterima.
- Alasan wajib pada penolakan proposal Admin; alasan keputusan lain mengikuti tahap terkait.
- File mengikuti batas B12 dan B13. Denah venue menerima PDF/JPG/JPEG/PNG/WebP hingga 20 MB pada disk privat. Nama file diacak.
- Tanggal ketersediaan tidak menimpa pesanan yang sudah mengikat.
- Transisi status di luar peta `BACKEND_BUSINESS_RULES.md` ditolak.

## 6. Cara halaman terhubung

Controller mengirim data ke Blade yang sudah ada. Alur quote, acara/pesanan, tagihan, refund intake, pencairan, rundown, checklist, notifikasi, rekomendasi, denah, dan ulasan terhubung tanpa redesign. Halaman di luar backlog teridentifikasi tetap dapat berupa prototipe.

`aksi.js` tetap untuk halaman yang belum POST. Halaman yang sudah menyimpan tidak memakai pesan sukses palsu.
