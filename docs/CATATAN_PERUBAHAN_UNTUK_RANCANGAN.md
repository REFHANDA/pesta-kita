# Catatan Penyesuaian & Keputusan Implementasi untuk Dokumen Rancangan EMS

Dokumen ini mencatat penyesuaian aturan bisnis dan keputusan implementasi teknis pada project **Pesta Kita** yang disepakati selama pengerjaan sinkronisasi rancangan:

---

## 1. Fase 5 — Tagihan, Pembayaran, Validasi, dan Dokumen Transaksi

### 1.1 Penggantian Library Binary PDF dengan View Cetak HTML Print-Friendly
- **Ketentuan Awal Rancangan:** Tagihan dan nota lunas diunduh dalam bentuk file binary PDF.
- **Penyesuaian:** Mengacu pada aturan kerja yang melarang penambahan dependensi Composer tanpa persetujuan dan hasil konfirmasi pengguna, fitur unduh file binary PDF server-side (misal melalui `barryvdh/laravel-dompdf`) digantikan dengan **tampilan cetak HTML print-friendly** (`invoices.print` dan `invoices.receipt`).
- **Implementasi:**
  - Dilengkapi tombol cetak interaktif yang memicu dialog cetak browser (`window.print()`) sehingga pengguna dapat mencetak fisik atau memilih *Save as PDF*.
  - Dilindungi oleh `InvoicePolicy`: hanya pemilik acara (klien) dan Admin yang dapat mengakses. Nota lunas hanya dapat diakses setelah tagihan berstatus `lunas`.

### 1.2 Batasan Perpanjangan `due_at` untuk Bukti Pembayaran yang Ditolak
- **Latar Belakang:** Klien mengunggah bukti pembayaran sebelum atau mendekati batas waktu bayar (`due_at`). Jika Admin menolak bukti pembayaran, sistem memberikan kesempatan perbaikan tanpa kehilangan slot atau pembatalan seketika.
- **Aturan Bisnis & Pembatasan:** Saat Admin menolak bukti pembayaran, batas pembayaran (`due_at`) disetel ke:
  $$\text{due\_at} = \max(\text{due\_at}, \text{now} + 24\text{ jam})$$
  dengan batasan ketat:
  1. **Maksimal 2 kali perpanjangan** per tagihan (pada penolakan bukti ke-1 dan ke-2). Pada penolakan ke-3 atau seterusnya, batas waktu tidak diperpanjang lagi.
  2. **Batas absolut 7 hari:** Perpanjangan `due_at` tidak boleh melampaui 7 hari sejak tagihan diterbitkan (`issued_at` + 7 hari).
  3. Setelah batasan tersebut tercapai atau batas waktu baru terlewati, tagihan tetap dapat kedaluwarsa dan dibatalkan secara otomatis melalui perintah `invoices:expire`.

### 1.3 Penomoran Dokumen Anti-Balapan (Race-Condition Proof)
- **Ketentuan:** Tagihan (`invoice_number`) dan Nota Lunas (`receipt_number`) wajib memiliki nomor unik berformat `PK/YYYY/MM/NNNN` (contoh: `PK/2026/11/0001`).
- **Tabel Counter Khusus:** Penomoran dikelola melalui tabel `document_counters` (`type`, `period`, `current_number`) dengan `unique(['type', 'period'])`.
- **Locking & Retry:** Pengambilan nomor berikutnya dilakukan dengan `lockForUpdate()` di dalam transaksi database dan dibungkus mekanisme `retry(3, ...)` untuk mengantisipasi balapan antar-transaksi konkruen.
- **Invarian:** Nomor nota lunas terjamin tidak akan pernah sama dengan nomor tagihan pada acara tersebut.

### 1.4 Transaksi & Perlindungan Validasi Ganda (Idempotensi)
- Seluruh mutasi status pembayaran, validasi, dan penolakan dibungkus dalam `DB::transaction` dengan `lockForUpdate()`.
- **Hanya satu pembayaran aktif** (`menunggu_validasi`) per tagihan: klien tidak dapat mengunggah bukti baru selama bukti sebelumnya belum diputuskan Admin.
- **Penolakan validasi ganda:** Percobaan memvalidasi atau menolak pembayaran yang statusnya sudah bukan `menunggu_validasi` akan langsung ditolak oleh server dengan validasi error.

### 1.5 Scheduler `invoices:expire`
- Scheduler `invoices:expire` berjalan tiap jam dengan `->withoutOverlapping()`.
- Scheduler **hanya** memproses tagihan berstatus `belum_dibayar` yang telah melewati `due_at`. Tagihan berstatus `menunggu_validasi` tidak pernah dibatalkan oleh scheduler agar klien yang sudah mengunggah bukti tidak dirugikan.

### 1.6 Pengingat SLA Validasi Pembayaran Admin (`payments:remind-sla`)
- Admin memiliki target SLA validasi pembayaran dalam 24 jam sejak bukti transfer diunggah oleh klien (`admin_review_sla_hours`).
- Scheduler `payments:remind-sla` berjalan tiap jam dengan `->withoutOverlapping()` untuk mengirimkan notifikasi database kepada seluruh akun Admin jika ada bukti pembayaran yang mendekati batas waktu (peringatan dini 4 jam sebelumnya) atau telah melewati SLA 24 jam.

## 2. Tahap A — Penyederhanaan UI, Tombol WhatsApp, Pengaturan, Notifikasi

### 2.1 Fitur yang dihapus dari aplikasi
- **Kalender ketersediaan manual** (`/klien|vendor|venue/kalender-ketersediaan`): halaman, controller, form request, dan test dihapus. Tabel `availability_days` dan `AvailabilityService` **tetap**; ketersediaan dicek otomatis di server saat Klien menambah item ke acara (stok/kuota peralatan, satu venue per tanggal) dan saat acara diajukan. Pesan venue bentrok: “Venue sudah dipesan pada tanggal ini.”
- **Pencarian global** di header keempat portal.
- **Hapus item katalog dan hapus venue** oleh penyedia (route `DELETE`, method controller, ability `delete` di policy, dan tombol). Item katalog memakai *Nonaktifkan*; venue individual dinonaktifkan oleh Admin lewat formulir venue Admin.
- **Tambah akun penyedia oleh Admin**: akun penyedia hanya lewat pendaftaran mandiri + verifikasi.
- **Form tambah/ubah/hapus rundown di portal Venue**: rundown venue hanya-baca.
- **Halaman `klien/pesanan` dan menu `klien/riwayat`**: pesanan & tagihan dibuka dari detail acara; riwayat menjadi tab *Riwayat* di *Acara saya* (`/klien/riwayat` dialihkan ke `/klien/acara?tab=riwayat`).
- **Route catch-all `/{page}`** di keempat portal. Setiap halaman kini punya route eksplisit bernama dengan middleware `auth` + `role`; URL acak menghasilkan 404.
- Halaman statis/data contoh lain yang dihapus: `klien/{pesanan-detail,katalog-detail,acara-batalkan,evaluasi-selesai,tagihan-ditolak,register}`, `vendor/registrasi-penyedia`, `vendor|venue/{keberatan,pencairan-detail}`, `admin/{admin-pesanan-venue,katalog-form,keberatan,keberatan-detail,notifikasi-detail,pembatalan-detail,pencairan-detail,pengguna-detail,pengguna-form,tagihan-detail,rundown,evaluasi,evaluasi-detail}`.

### 2.2 Menu final
| Portal | Menu sidebar |
| --- | --- |
| Klien (5) | Beranda · Acara saya (Mendatang, Riwayat) · Katalog (Vendor, Venue, Peralatan) · Notifikasi · Profil |
| Vendor (6) | Beranda · Katalog saya (Layanan, Peralatan, Pengajuan harga) · Pesanan (Aktif, Riwayat, Keberatan 24 jam) · Rundown · Pencairan · Profil & verifikasi (Profil usaha, Verifikasi dokumen) |
| Venue (6) | Beranda · Venue saya (Data & denah venue, Pengajuan harga) · Pesanan (Aktif, Riwayat, Keberatan 24 jam) · Rundown (hanya-baca) · Pencairan · Profil & verifikasi |
| Admin (9) | Beranda · Pesanan (Menunggu review, Berjalan, Keberatan, Riwayat) · Tagihan & pembayaran · Pembatalan & refund · Pencairan · Penyedia & katalog (Verifikasi penyedia, Katalog, Pengajuan harga, Pengguna) · Acara · Pengaturan (Parameter, Rekening perusahaan) · Log aktivitas |

Nama/avatar di header membuka menu *Profil* dan *Keluar*. Lonceng notifikasi menampilkan jumlah belum dibaca.

### 2.3 Tombol “Tanya Admin” (WhatsApp)
- Hanya di portal Klien (tombol mengambang kanan bawah). Klien tidak pernah melihat kontak vendor/venue; semua pertanyaan lewat Admin.
- Nomor dari `settings.admin_whatsapp` (dinormalisasi ke `62…`, valid `^62[0-9]{8,13}$`); tombol disembunyikan bila kosong.
- Pesan dibangun server: salam + nama klien, konteks *Acara #ID (nama)* di detail acara atau *item dari usaha* di detail katalog, dan penutup “Keputusan resmi (pesanan, pembayaran, pembatalan) hanya melalui aplikasi.” Tidak ada tabel percakapan.

### 2.4 Pengaturan Admin
- `/admin/pengaturan` menyimpan `commission_percent` (0–50), `payment_deadline_hours` (1–720), `objection_window_hours` (1–168), `admin_review_sla_hours` (1–168), `unique_code_enabled`, `admin_whatsapp`, `refund_tiers` (urut turun, unik, baris terakhir 0 hari, persen tidak naik), dan `budget_allocation` (total 100%).
- Setiap perubahan tercatat di log aktivitas (`setting_updated`, nilai lama → baru). `Setting::get()` membaca langsung dari basis data (tanpa cache) sehingga nilai baru langsung berlaku.
- Perubahan komisi hanya berlaku untuk pesanan yang disetujui setelah disimpan; pesanan lama tetap memakai komisi yang sudah dikunci.
- Rekening perusahaan menjadi tab *Rekening perusahaan* di Pengaturan (`/admin/rekening`).

### 2.5 Notifikasi
- Halaman `/{portal}/notifikasi` membaca notifikasi database milik pengguna login (terbaru dulu, 15 per halaman). Membuka notifikasi menandainya dibaca lalu mengarahkan ke halaman terkait; tersedia *Tandai semua sudah dibaca*.
- Notifikasi untuk penyedia hanya menyebut *Acara #ID* dan nama item, tanpa nama atau kontak klien.

### 2.6 Halaman tahap berikutnya
Rundown, evaluasi, dan rekomendasi paket menampilkan empty state “Belum ada data.” tanpa angka contoh sampai tahap implementasinya. Pembatalan & refund serta pencairan telah dibangun di Tahap B (bagian 3).

## 3. Tahap B — Pembatalan, Refund, Selesai, dan Pencairan

### 3.1 Perubahan skema
- `cancellations` mendapat kolom nullable `refund_bank_name`, `refund_account_number`, `refund_account_name` (rekening tujuan refund yang diisi klien saat mengajukan) dan `refund_paid_at` (tanggal transfer refund). Skema lain tidak berubah.

### 3.2 Jenis media baru
- `MediaKind::BuktiRefund` (pemilik = pembatalan): dilihat Admin dan klien pemilik acara.
- `MediaKind::BuktiPencairan` (pemilik = pencairan): dilihat Admin dan penyedia penerima.
- Keduanya disimpan di disk `private` (JPG/PNG/PDF maks 5 MB).

### 3.3 Rumus Pendapatan Perusahaan
- Sebelumnya: Σ (komisi + kebersihan) tagihan lunas.
- Sekarang: Σ (komisi + kebersihan) tagihan lunas milik acara yang **tidak dibatalkan** + Σ `company_income` pembatalan berstatus `refund_selesai`. Contoh acuan p = 70%: kontribusi 1.350.000, bukan 4.500.000.

### 3.4 Alur
- Klien membatalkan langsung acara `diajukan`/`menunggu_pembayaran` (diblokir saat bukti bayar divalidasi); acara lunas diajukan lewat form dengan pratinjau perkiraan dari server.
- Admin: tinjau → setujui (penyebab klien/vendor/force majeure, override persen dengan alasan) atau tolak → proses refund → catat refund selesai (bukti). Refund selesai membatalkan acara, melepas slot, dan membuat pencairan kompensasi.
- Admin menandai acara selesai setelah tanggal acara → pencairan hak pesanan → catat transfer.
- Vendor/Venue melihat pencairan miliknya (hanya baca, tanpa nama klien) dan mengunduh bukti transfernya.

## 4. Tahap C — Rundown dan Evaluasi

### 4.1 Perubahan aturan
- Vendor boleh mengisi item rundown **miliknya sendiri** (tertaut pesanan yang terkonfirmasi). Rancangan hanya menyebut rundown disusun klien.
- Venue dan Admin hanya melihat rundown, tanpa aksi tulis.
- Rundown terkunci permanen setelah acara `selesai` (termasuk Admin tidak dapat mengubah).
- Notifikasi perubahan rundown digabung per acara/penerima selama belum dibaca.
- Evaluasi satu kali final per acara; subjek dihitung server (venue, tiap vendor, Admin), bukan dari input klien.
- Rating tidak memengaruhi uang atau status penyedia.

### 4.2 Use case baru
- Klien menyusun rundown di dalam detail acara (tanpa menu baru).
- Vendor menyusun item rundown miliknya dari portal Vendor.
- Admin memiliki tab Rundown dan Evaluasi pada detail acara.
- Kartu rating Admin dan penyedia pada dashboard; daftar penilaian penyedia tanpa nama klien.

### 4.3 Fitur dihapus
- Halaman statis `klien/rundown`, `klien/evaluasi`, `klien/rekomendasi` (Route::view) dihapus; URL lama 404.

### 4.4 Skema
- Tidak ada migration baru pada Tahap C. Tabel `rundown_items`, `evaluations`, `evaluation_items` dipakai apa adanya.

## 5. Tahap D — Rekomendasi Paket, Denah, Susunan Ruangan

### 5.1 Perubahan aturan
- Rekomendasi membandingkan **estimasi biaya Klien** = harga penyedia (`catalog_price`/`rental_price`) + komisi layanan + (khusus venue) biaya kebersihan, bukan harga penyedia saja (lihat bagian 6).
- Alokasi anggaran berasal dari `settings.budget_allocation` (35/30/15/10/5/5). Selisih pembulatan masuk dana cadangan sehingga jumlah alokasi selalu sama dengan anggaran.
- Kandidat hanya penyedia terverifikasi/aktif, kapasitas tamu terpenuhi, estimasi biaya ≤ alokasi, dan tersedia pada tanggal acara; maksimal 3 kandidat per kategori; urutan rating menurun (tanpa rating di akhir), estimasi menaik, lalu id.
- Kategori katalog adalah teks bebas. Pemetaan yang dipakai: `Katering & F&B`/`Katering`/`Catering` → catering, `Dekorasi`/`Dekorasi & Tata Rias` → dekorasi, `Hiburan`/`Musik & Hiburan` → hiburan; peralatan dipetakan dari `kind`. Kategori lain diabaikan.
- Denah adalah gambar per ruangan, maksimal 3, bukan editor denah interaktif.
- Susunan ruangan adalah pilihan enum tetap; tidak ada validasi kapasitas per susunan.
- Klien dapat mengubah susunan ruangan dari `draft` sampai `terkonfirmasi`; terkunci setelah `selesai`/`dibatalkan`.

### 5.2 Use case baru
- Klien melihat rekomendasi paket pada detail acara draft dan menambahkan kandidat ke acara.
- Klien memilih susunan ruangan dan menulis catatan.
- Pemilik venue mengunggah denah per ruangan; klien melihat denah pada detail venue.

### 5.3 Skema
- `events.room_layout`, `events.room_layout_notes` (nullable).
- `media.kind` menerima `denah` (tanpa perubahan tipe kolom).

## 6. Tahap F — Perbaikan pasca-review Tahap C dan D

### 6.1 Perubahan aturan
- **Denah mengikuti persetujuan verifikasi.** Persetujuan Admin kini mempublikasikan foto pemilik **dan** denah ruangan pada semua venue milik profil yang disetujui, kecuali venue yang dinonaktifkan Admin secara individual. Sebelumnya denah yang diunggah sebelum verifikasi tetap privat selamanya.
- **Rekomendasi memakai estimasi biaya total.** Estimasi = harga penyedia + komisi layanan + (khusus venue) biaya kebersihan. Seluruh filter, urutan, dan ringkasan memakai estimasi; `price` pada keluaran kandidat tetap harga penyedia (sama dengan `order_items.provider_price`).
- Rumus estimasi disalin dari jalur tagihan: komisi `intdiv(lineTotal × commission_percent + 50, 100)` dengan persen dari `settings.commission_percent` (bawaan 10); kebersihan `venues.cleaning_fee` (null/0 = tidak dikenakan), satu baris per acara, tidak dikenai komisi. Kelas `App\Services\ClientCostEstimator` hanya membaca Pengaturan; `OrderReviewController` dan `InvoiceIssuer` tidak diubah.
- Label UI rekomendasi menyebut bahwa estimasi sudah termasuk komisi dan biaya kebersihan, dan angka final ada pada tagihan.

### 6.2 Perbaikan data
- Migration `2026_11_21_000003_publish_verified_venue_room_layouts` menaikkan denah `privat` menjadi `publik` untuk venue milik penyedia `terverifikasi` yang tidak `nonaktif`. Idempoten, hanya media `denah`, `down()` kosong.

### 6.3 Tidak berubah
- Tagihan, pembayaran, refund, kompensasi, dan pencairan tidak berubah. Tidak ada dependency baru.

## 7. Tahap G — Konsistensi kartu Estimasi Biaya
- Kartu "Estimasi biaya" di ringkasan acara Klien kini = Σ(harga + komisi) + biaya kebersihan venue sekali per acara (komisi terkunci dipakai bila ada); setelah tagihan aktif terbit, kartu menampilkan `grand_total` tagihan tanpa kode unik. Tidak ada perubahan skema, route, maupun alur tagihan.

## 8. Tahap G — Rekening pencairan penyedia dan perbaikan UI

### 8.1 Perubahan aturan
- **Rekening pencairan disimpan sebagai baris berversi dengan riwayat.** Baris `terverifikasi` tidak diubah di tempat: perubahan dari penyedia membuat/memperbarui baris `menunggu`, rekening aktif lama tetap dipakai pencairan sampai pengajuan baru disetujui, lalu baris lama menjadi `nonaktif`. Satu profil hanya memiliki satu rekening `terverifikasi`.
- **Verifikasi rekening terpisah dari verifikasi profil.** Rekening bukan prasyarat verifikasi profil dan tidak muncul di katalog; rekening hanya prasyarat pencairan. Persetujuan/penolakan rekening memakai route dan keputusan sendiri (`admin.rekening-penyedia.*`) serta tidak menyentuh `verification_status` profil.
- Kolom baru pada `provider_bank_accounts`: `reviewed_by`, `reviewed_at`, `rejection_reason`, dan index `(owner_type, owner_id, verification_status)` — additive, kolom lama tidak diubah.
- Nomor lengkap hanya di halaman Profil pemilik dan halaman Admin; dashboard, halaman Pencairan penyedia, notifikasi, dan log memakai versi samaran.

### 8.2 Use case baru (untuk dokumen rancangan)
- **Mengisi rekening pencairan (Penyedia)** — aktor Vendor/Venue; prasyarat login; penyedia mengisi bank, nomor rekening, atas nama; sistem menyimpan baris `menunggu` dan memberi tahu Admin; penyedia melihat status Belum diisi / Menunggu / Terverifikasi / Ditolak (dengan alasan).
- **Memverifikasi rekening penyedia (Admin)** — aktor Admin; prasyarat ada pengajuan `menunggu`; Admin melihat nomor penuh, memutuskan Setujui atau Tolak (alasan wajib); sistem menonaktifkan rekening terverifikasi lama, menulis log audit (nomor tersamar), dan mengirim notifikasi ke penyedia.

### 8.3 Class diagram
- Class baru **`RekeningPenyedia`** (implementasi: `App\Models\ProviderBankAccount`) dengan atribut `bank_name`, `account_number`, `account_name`, `verification_status`, `reviewed_by`, `reviewed_at`, `rejection_reason`; relasi ke `VendorProfile`/`VenueProfile` (morph owner) dan ke `User` sebagai reviewer.

### 8.4 Perbaikan tampilan
- Tab status Admin → Acara memakai komponen `x-subnav` (`.tabs`) dengan hitungan per status; kelas `.pill` tidak lagi dipakai di panel Admin.
- Kartu Rekening perusahaan memakai kelas halaman `page-bank-accounts`; gaya inline kotak sukses/alat bantu diganti kelas `.alert`, `.alert-success`, `.toolbar`, `.form-actions`.
- Tabel Pencairan/Pembatalan Admin memakai `<small class="sub-t">` dan lebar kolom minimum untuk Layanan/Rekening; jumlah uang tidak membungkus.

### 8.5 Tidak berubah
- Tagihan, pembayaran, refund, kompensasi, dan logika pencairan tidak berubah. Tidak ada dependency baru.
