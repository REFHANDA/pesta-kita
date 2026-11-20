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
