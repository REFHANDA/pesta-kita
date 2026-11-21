# Kontrak Integrasi Frontend–Backend — Pesta Kita

Status: **rancangan**. Tidak ada endpoint domain pada `routes/web.php` hari ini. Route yang ada hanya GET penampil Blade.

Kontrak ini memakai form POST dan Blade, sesuai aplikasi saat ini. Bukan API JSON publik. Kalender tetap membaca JSON yang diletakkan controller di halaman yang sama (`#kal-config`), karena `kalender.js` sudah bekerja seperti itu.

Setiap aksi tulis menolak peran yang salah dengan 403. Halaman milik peran lain tidak boleh 200 untuk pengguna yang salah.

## 1. Route yang ada sekarang

| Method | Path | Perilaku aktual |
|---|---|---|
| GET | `/` | View `landing` |
| GET | `/login`, `/register` | Form yang tidak mengirim kredensial ke server |
| GET | `/logout` | Redirect ke `/login` |
| GET | `/klien`, `/vendor`, `/venue`, `/admin` | Dashboard statis |
| GET | `/{role}/{page}` | View Blade jika file ada, selain itu 404 |
| GET | `/up` | Health Laravel |

Tidak ada POST, PUT, atau DELETE.

## 2. Matriks kebutuhan

| Modul | Role | Kebutuhan frontend | Backend saat ini | Kekurangan |
|---|---|---|---|---|
| Katalog Vendor | Klien | Daftar dan detail: usaha, kategori, area, mode harga tetap vs penawaran, verifikasi, paket, kebijakan, galeri. Filter kategori, nama, harga maksimum. | Tidak ada. HTML statis. | Query, filter, sembunyikan yang belum terverifikasi dan kontak pribadi. |
| Katalog Venue | Klien | Daftar dan detail: jenis, lokasi, kapasitas, fasilitas, sewa, kebersihan, komisi terpisah, aturan, kebijakan, ketersediaan perlu konfirmasi. | Tidak ada. | Query dan estimasi yang bukan tagihan final. |
| Peralatan | Klien, Vendor | Katalog sewa dan form alat. | Tidak ada. | Menunggu keputusan B15 sebelum dipisah dari layanan. |
| Profil | Semua | Profil usaha, rekening pencairan, portofolio, verifikasi Admin, ganti sandi. Publik tanpa kontak pribadi. | Tidak ada. | Simpan, verifikasi, disk privat untuk foto. |
| Request Quote | Empat role | Klien mengajukan. Penyedia mengusulkan. Admin memutuskan. Tiga nominal terpisah. | Tidak ada. Delapan view statis. | Simpan, snapshot harga, alasan wajib saat tolak. |
| Kalender | Klien, Vendor, Venue | Status per tanggal. Venue per ruangan. Klien meneruskan tanggal ke penawaran. | Tidak ada. JSON contoh di Blade. | Simpan status manual. Status dipesan dihitung dari pesanan. |
| Pesanan | Semua | Tahap acara, setujui atau tolak Admin, keberatan penyedia, tanpa nama Klien di sisi penyedia. | Tidak ada. | State machine dan masa keberatan setelah B7. |
| Tagihan | Klien, Admin | Satu invoice per acara: harga penyedia, komisi, kebersihan, kode unik, total, versi. | Tidak ada. | Hitung di server. Versi setelah B8. |
| Pembayaran | Klien, Admin | Pilih rekening perusahaan, unggah bukti, validasi Admin, nota hanya jika lunas. | Tidak ada. | Disk privat, cocokkan nominal, status. |
| Refund | Klien, Admin | Alasan pengajuan, keputusan, bukti transfer refund. Tanpa hitung lokal. | Tidak ada. | Snapshot kebijakan. Nominal setelah B1. |
| Pencairan | Vendor, Venue, Admin | Hak penuh penyedia, kompensasi, rekening tersamar, bukti cair. | Tidak ada. | Unique per item, hanya setelah acara selesai. |
| Koordinasi | Semua | Rundown, evaluasi setelah selesai. Checklist belum punya view terpisah. | Tidak ada. | Hak tulis rundown. Ulasan tanpa identitas ke penyedia. |
| Rekening perusahaan | Admin | Daftar dan form rekening tujuan transfer. | Tidak ada. | CRUD Admin. |
| Pengajuan harga | Vendor, Venue, Admin | Harga lama berlaku sampai disetujui. | Tidak ada. | Tidak mengubah pesanan lama. |
| Pengguna dan audit | Admin | Mitra, nonaktifkan, log aktivitas. | Tidak ada. | Status akun dan `audit_logs`. |

## 3. Field yang sudah terlihat di form

| Halaman | Field | Pemilik data |
|---|---|---|
| `auth/register` | peran, nama, email, telepon, nama usaha, kategori, alamat, sandi | Pengguna baru. Admin tidak ada di form. |
| `klien/acara-baru` | nama, tanggal, tamu, tema, deskripsi | Klien |
| `klien/permintaan-penawaran` | item, acara, tanggal, tamu, anggaran, kebutuhan | Klien. Harga final tidak ada. |
| `vendor/permintaan-penawaran-detail` | harga usulan, rincian, catatan | Vendor |
| `venue/permintaan-penawaran-detail` | harga sewa, biaya tambahan, catatan | Venue |
| `admin/permintaan-penawaran-detail` | keputusan, alasan | Admin. Nominal final dihitung atau dikunci server. |
| `vendor/item-form` | nama, kategori, kuota harian, deskripsi, harga, alasan perubahan | Vendor, lalu Admin untuk harga baru |
| `venue/form` | nama, jenis, kapasitas, alamat, deskripsi, harga sewa, alasan, kebersihan read-only, durasi, deposit, fasilitas, ketentuan | Venue. Kebersihan menunggu B4. Deposit menunggu B5. |
| Profil penyedia | usaha, kategori, telepon, alamat, penanggung jawab, email, bank, rekening, atas nama, sandi, foto | Pemilik. Rekening butuh verifikasi ulang menurut teks UI. |
| `klien/tagihan` | rekening tujuan, bukti | Klien. Total tidak diinput. |
| `admin/pencairan-detail` | tanggal transfer, bukti, catatan | Admin |
| `admin/pembatalan-detail` | tanggal transfer refund, bukti | Admin. Nominal tidak diinput Klien. |

## 4. Route usulan

Nama di bawah belum dibuat. Route POST eksplisit harus didaftarkan sebelum catch-all `/{page}`.

### BE-1

- POST `/login` — tamu — sesi dan redirect sesuai role.
- POST `/logout` — pengguna login — hapus sesi.
- POST `/register` — tamu — buat Klien aktif, atau Vendor/Venue menunggu verifikasi.
- GET dan POST profil serta ganti sandi per peran.
- Middleware `auth` dan `role` pada prefix portal.

### BE-3

- GET katalog dan detail dari controller, hanya data terverifikasi untuk Klien.
- POST tambah item ke acara draft milik Klien.
- POST dan PUT katalog oleh vendor. POST dan PUT venue serta ruangan oleh pemilik.
- POST pengajuan harga. POST keputusan Admin.
- POST ketersediaan oleh penyedia untuk subjek miliknya.
- POST media ke disk privat.

### BE-4

- POST permintaan penawaran oleh Klien.
- POST usulan oleh penyedia pemilik subjek.
- POST keputusan Admin yang menulis harga disetujui dan snapshot.

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

- Setiap id acara, item, dan tagihan dicek kepemilikan atau peran.
- Harga pada form Klien diabaikan.
- Alasan wajib pada tolak pesanan, tolak penawaran, tolak bukti, dan tolak refund.
- File mengikuti batas B12 dan B13. Nama file diacak.
- Tanggal ketersediaan tidak menimpa pesanan yang sudah mengikat.
- Transisi status di luar peta `BACKEND_BUSINESS_RULES.md` ditolak.

## 6. Cara halaman terhubung

Controller mengirim data ke Blade yang sudah ada. Layout dan class tidak diganti. Penanda prototipe dilepas hanya pada halaman yang datanya sudah berasal dari server. Halaman di luar tahap yang sedang dikerjakan tetap statis.

`aksi.js` tetap untuk halaman yang belum POST. Halaman yang sudah menyimpan tidak memakai pesan sukses palsu.

## Endpoint Tahap C — Rundown dan Evaluasi

Semua route di bawah middleware `auth` + `role:<portal>`; halaman Klien mengembalikan 404/403 untuk acara milik klien lain.

| Method | URI | Nama route | Peran | Keterangan |
|---|---|---|---|---|
| GET | `/klien/acara/{event}/rundown` | `klien.acara.rundown.index` | Klien pemilik | Redirect ke `#rundown` pada detail acara. |
| POST | `/klien/acara/{event}/rundown` | `klien.acara.rundown.store` | Klien pemilik | Tambah item; hanya status `terkonfirmasi`. |
| PUT | `/klien/acara/{event}/rundown/{rundownItem}` | `klien.acara.rundown.update` | Klien pemilik | Ubah item. |
| DELETE | `/klien/acara/{event}/rundown/{rundownItem}` | `klien.acara.rundown.destroy` | Klien pemilik | Hapus item. |
| GET | `/klien/acara/{event}/evaluasi` | `klien.acara.evaluasi.index` | Klien pemilik | Redirect ke `#evaluasi`. |
| POST | `/klien/acara/{event}/evaluasi` | `klien.acara.evaluasi.store` | Klien pemilik | Evaluasi final; 409 bila sudah ada, 422 bila subjek tidak lengkap. |
| GET | `/vendor/rundown` | `vendor.rundown` | Vendor | Daftar acara dengan pesanan terkonfirmasi/selesai. |
| GET | `/vendor/rundown/{event}` | `vendor.rundown.show` | Vendor pemilik pesanan | 403 bila bukan pesanannya. |
| POST | `/vendor/rundown/{event}` | `vendor.rundown.store` | Vendor | Wajib `order_item_id` miliknya yang terkonfirmasi. |
| PUT | `/vendor/rundown/{event}/{rundownItem}` | `vendor.rundown.update` | Vendor pembuat item | 403 untuk item klien/vendor lain. |
| DELETE | `/vendor/rundown/{event}/{rundownItem}` | `vendor.rundown.destroy` | Vendor pembuat item | 403 untuk item klien/vendor lain. |
| GET | `/venue/rundown` | `venue.rundown` | Venue | Hanya lihat. |
| GET | `/venue/rundown/{event}` | `venue.rundown.show` | Venue pemilik pesanan | Hanya lihat; tanpa route tulis. |

Body item rundown: `start_time` (HH:MM), `end_time` (opsional, HH:MM > start), `activity` (≤150), `description` (≤1000), `order_item_id` (opsional untuk klien, wajib untuk vendor).

Body evaluasi: `ratings[<key>][rating]` 1–5 wajib untuk setiap subjek (`venue`, `vendor:<id>`, `admin`), `ratings[<key>][comment]` ≤1000 opsional, `system_feedback` ≤2000 opsional.

## Endpoint Tahap D — Rekomendasi, Denah, Susunan Ruangan

| Method | URI | Nama route | Peran | Keterangan |
|---|---|---|---|---|
| GET | `/klien/acara/{event}/rekomendasi` | `klien.acara.rekomendasi` | Klien pemilik | Redirect ke `#rekomendasi`; isi rekomendasi dirender di detail acara draft. |
| PUT | `/klien/acara/{event}/susunan-ruangan` | `klien.acara.layout.update` | Klien pemilik | `room_layout` enum, `room_layout_notes` ≤500. 403 setelah acara selesai/dibatalkan. |
| POST | `/venue/ruangan/{room}/denah` | `venue.ruangan.denah.store` | Venue pemilik ruangan | Multipart `photo` (JPG/PNG/WebP ≤5 MB), maksimal 3 denah. |
| DELETE | `/media/{media}` | `media.destroy` | Pemilik media | Dipakai juga untuk menghapus denah. |

Rekomendasi hanya menampilkan data; penambahan item memakai endpoint yang sudah ada `POST /klien/acara/{event}/pesanan` dengan `subject_type` (`venue_room`/`catalog_item`) dan `subject_id`.
