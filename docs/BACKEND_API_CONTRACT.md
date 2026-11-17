# Kontrak Integrasi Frontend–Backend — Pesta Kita

Status: kontrak diperbarui pada 2026-11-17. Aplikasi memakai form dan route web Blade, bukan API JSON publik. BE-1, BE-3, BE-3b, dan BE-4 sudah memiliki route aktif; rincian endpoint penawaran BE-4 dicatat di bawah. Kontrak BE-5 dan seterusnya tetap rancangan.

Kontrak ini memakai form POST dan Blade, sesuai aplikasi saat ini. Bukan API JSON publik. Kalender tetap membaca JSON yang diletakkan controller di halaman yang sama (`#kal-config`), karena `kalender.js` sudah bekerja seperti itu.

Setiap aksi tulis menolak peran yang salah dengan 403. Halaman milik peran lain tidak boleh 200 untuk pengguna yang salah.

## 1. Route aktif yang relevan

| Method | Path | Perilaku aktual |
|---|---|---|
| GET | `/login`, `/register` | Form autentikasi untuk guest |
| POST | `/login`, `/register`, `/logout` | Login, registrasi, dan logout BE-1 |
| POST | `/profil`, `/profil/sandi` | Perbarui profil dan kata sandi |
| GET/POST | `/klien/permintaan-penawaran` | Daftar dan pengajuan quote Klien |
| GET | `/klien/permintaan-penawaran-detail/{quoteRequest}` | Detail quote milik Klien |
| GET | `/vendor/permintaan-penawaran` | Daftar quote untuk item katalog milik Vendor |
| GET/POST | `/vendor/permintaan-penawaran-detail/{quoteRequest}` | Detail dan usulan harga Vendor |
| GET | `/venue/permintaan-penawaran` | Daftar quote untuk ruangan milik Pemilik Venue |
| GET/POST | `/venue/permintaan-penawaran-detail/{quoteRequest}` | Detail dan usulan harga Pemilik Venue |
| GET | `/admin/permintaan-penawaran` | Antrean Admin dengan filter status |
| GET | `/admin/permintaan-penawaran-detail`, `/admin/permintaan-penawaran-detail/{quoteRequest}` | Empty state kompatibilitas dan detail quote |
| POST | `/admin/permintaan-penawaran/{quoteRequest}/{action}` | Keputusan `approve` atau `reject` |
| GET | `/up` | Health Laravel |

Route portal memakai middleware autentikasi dan role terkait. Route tulis eksplisit didaftarkan sebelum catch-all Blade. Route katalog, kalender, ketersediaan, dan pengajuan harga BE-3/BE-3b juga aktif; rincian tahap itu ada di laporan implementasinya.

## 2. Matriks kebutuhan

| Modul | Role | Kebutuhan frontend | Backend saat ini | Kekurangan |
|---|---|---|---|---|
| Katalog Vendor | Klien | Daftar dan detail: usaha, kategori, area, mode harga tetap vs penawaran, verifikasi, paket, kebijakan, galeri. | Backend BE-3: data publik hanya dari penyedia terverifikasi. | Sebagian filter dan fitur lanjutan masih di luar tahap. |
| Katalog Venue | Klien | Jenis, lokasi, kapasitas, fasilitas, sewa, kebersihan, aturan, kebijakan, ketersediaan perlu konfirmasi. | Backend BE-3: venue/ruangan publik mengikuti verifikasi penyedia. | Status ketersediaan tetap perlu konfirmasi; bukan jaminan booking. |
| Peralatan | Klien, Vendor | Katalog sewa dan form alat. | Terhubung melalui struktur katalog bersama dengan atribut `kind` (B15). | Fitur inventori/stok belum ada. |
| Profil | Semua | Profil usaha, portofolio, verifikasi Admin, ganti sandi. Publik tanpa kontak pribadi. | Profil, kata sandi, verifikasi, dan media terhubung sesuai role (BE-1/BE-3). | UI rekening bank dan beberapa fitur lanjutan belum terhubung. |
| Request Quote | Empat role | Klien mengajukan. Penyedia mengusulkan. Admin memutuskan. Tiga nominal terpisah. | BE-4 aktif untuk Klien, Vendor, Venue, Admin; snapshot harga katalog, proposal, keputusan, dan audit disimpan server. | Tidak membuat acara baru; tautan acara draft opsional. Keputusan final tidak dapat diulang. |
| Kalender | Klien, Vendor, Venue | Status per tanggal. Venue per ruangan. Klien dapat meneruskan tanggal ke penawaran. | BE-3b aktif: penyedia mengubah ketersediaan; Klien membaca jadwal. | Status dipesan tetap berasal dari pesanan, belum ada booking/lock tanggal. |
| Pesanan | Semua | Tahap acara, setujui atau tolak Admin, keberatan penyedia, tanpa nama Klien di sisi penyedia. | BE-3 menyediakan baca-saja daftar/detail acara Klien; BE-4 dapat menautkan quote ke draft. Belum ada route tulis acara/pesanan. | State machine dan masa keberatan setelah B7. |
| Tagihan | Klien, Admin | Satu invoice per acara: harga penyedia, komisi, kebersihan, kode unik, total, versi. | Tidak ada. | Hitung di server. Versi setelah B8. |
| Pembayaran | Klien, Admin | Pilih rekening perusahaan, unggah bukti, validasi Admin, nota hanya jika lunas. | Tidak ada. | Disk privat, cocokkan nominal, status. |
| Refund | Klien, Admin | Alasan pengajuan, keputusan, bukti transfer refund. Tanpa hitung lokal. | Tidak ada. | Snapshot kebijakan. Nominal setelah B1. |
| Pencairan | Vendor, Venue, Admin | Hak penuh penyedia, kompensasi, rekening tersamar, bukti cair. | Tidak ada. | Unique per item, hanya setelah acara selesai. |
| Koordinasi | Semua | Rundown, evaluasi setelah selesai. Checklist belum punya view terpisah. | Tidak ada. | Hak tulis rundown. Ulasan tanpa identitas ke penyedia. |
| Rekening perusahaan | Admin | Daftar dan form rekening tujuan transfer. | Tidak ada. | CRUD Admin. |
| Pengajuan harga | Vendor, Venue, Admin | Harga lama berlaku sampai disetujui. | BE-3b aktif; keputusan Admin dan perubahan harga tercatat. | Tidak mengubah pesanan lama. |
| Pengguna dan audit | Admin | Mitra, nonaktifkan, log aktivitas. | Audit log tersedia; halaman aktivitas Admin bersifat baca-saja. | Manajemen pengguna Admin belum terhubung penuh. |

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
| Profil penyedia | usaha, kategori, telepon, alamat, penanggung jawab, email, bank, rekening, atas nama, sandi, foto | Pemilik. Rekening butuh verifikasi ulang menurut teks UI. |
| `klien/tagihan` | rekening tujuan, bukti | Klien. Total tidak diinput. |
| `admin/pencairan-detail` | tanggal transfer, bukti, catatan | Admin |
| `admin/pembatalan-detail` | tanggal transfer refund, bukti | Admin. Nominal tidak diinput Klien. |

## 4. Route implementasi dan usulan

Route POST eksplisit harus didaftarkan sebelum catch-all `/{page}`. Rute BE-1, BE-3, BE-3b, dan BE-4 sudah dibuat; BE-5–BE-7 tetap rancangan.

### BE-1

- POST `/login` — tamu — sesi dan redirect sesuai role.
- POST `/logout` — pengguna login — hapus sesi.
- POST `/register` — tamu — buat Klien aktif, atau Vendor/Venue menunggu verifikasi.
- GET dan POST profil serta ganti sandi per peran.
- Middleware `auth` dan `role` pada prefix portal.

### BE-3

- GET katalog/detail terverifikasi, CRUD katalog/venue, media privat, verifikasi, kalender, dan pengajuan harga sudah aktif (BE-3/BE-3b).
- Penambahan item ke acara draft belum tersedia; alur acara/pesanan masuk tahap BE-5.

### BE-4

- Aktif: `POST /klien/permintaan-penawaran` — menyimpan quote untuk item/ruangan publik, snapshot harga katalog, dan `event_id` draft milik Klien bila dipilih.
- Aktif: `POST /vendor/permintaan-penawaran-detail/{quoteRequest}` dan `POST /venue/permintaan-penawaran-detail/{quoteRequest}` — usulan hanya oleh pemilik subjek dan hanya saat status menunggu usulan.
- Aktif: `POST /admin/permintaan-penawaran/{quoteRequest}/approve|reject` — Admin memutuskan proposal terbaru; harga disetujui diambil dari server. Penolakan memerlukan alasan.
- Semua keputusan berstatus final pada versi skema saat ini; usulan/keputusan ulang tidak tersedia. Belum ada notifikasi atau pembuatan acara.

### BE-5

- POST ajukan acara.
- POST setujui atau tolak item oleh Admin, alasan wajib saat tolak.
- POST keberatan oleh penyedia di dalam masa yang disepakati.
- Transisi ke menunggu pembayaran hanya jika seluruh item sudah berstatus akhir.

### BE-6

- POST terbitkan tagihan. Server menghitung komisi, kebersihan, kode unik, dan total.
- POST unggah bukti oleh Klien pemilik acara.
- POST sahkan atau tolak oleh Admin. Sahkan gagal jika nominal tidak sama dengan total.
- POST ajukan pembatalan. POST putuskan refund. POST catat transfer refund.
- POST catat pencairan. Constraint menolak pencairan hak kedua.

### BE-7

- POST rundown oleh Klien atau Vendor pemilik item.
- POST ulasan setelah acara selesai dan tagihan lunas.
- GET agregat untuk penyedia tanpa identitas penulis.

## 5. Validasi saat implementasi

- Setiap id acara, item, dan tagihan dicek kepemilikan atau peran; quote memvalidasi acara draft milik Klien dan subjek yang masih publik.
- Harga katalog, proposal terakhir, dan harga yang disetujui tetap dibaca/disimpan terpisah oleh server; field harga final dari Klien tidak diterima.
- Alasan wajib pada penolakan proposal Admin; alasan keputusan lain mengikuti tahap terkait.
- File mengikuti batas B12 dan B13. Nama file diacak.
- Tanggal ketersediaan tidak menimpa pesanan yang sudah mengikat.
- Transisi status di luar peta `BACKEND_BUSINESS_RULES.md` ditolak.

## 6. Cara halaman terhubung

Controller mengirim data ke Blade yang sudah ada. BE-4 mengganti data contoh pada delapan view quote untuk alur Klien, Vendor, Venue, dan Admin tanpa redesign. Halaman lain yang belum masuk tahap wiring tetap dapat berupa prototipe.

`aksi.js` tetap untuk halaman yang belum POST. Halaman yang sudah menyimpan tidak memakai pesan sukses palsu.
