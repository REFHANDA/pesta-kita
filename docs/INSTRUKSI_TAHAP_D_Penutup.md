# Instruksi Antigravity IDE — TAHAP D: Rekomendasi Paket, Layout Ruangan, Pembersihan Akhir

> Tempel seluruh isi file ini sebagai prompt (atau simpan di `docs/` lalu minta agent membacanya). Tahap D menggabungkan **Fase 10** (rekomendasi paket, versi minimal), **Fase 11** (layout ruangan, versi minimal), dan **Fase 13** (pembersihan akhir dan sinkronisasi dokumen). Tahap A, B, dan C sudah selesai dan tidak boleh dibongkar.
>
> **Tahap D tidak menyentuh uang.** Rekomendasi hanya menampilkan saran; ia tidak membuat pesanan, tagihan, atau angka yang disimpan. Pembersihan (D3) dikerjakan **paling akhir** dan tidak mengubah logika bisnis.

---

## 0. Tujuan, batas, dan aturan kerja

Tujuan: melengkapi pengalaman klien (saran paket sesuai anggaran, informasi tata ruang) lalu merapikan seluruh proyek menjadi satu kesatuan yang konsisten dengan dokumen.

### Yang TIDAK BOLEH diubah
- `InvoiceIssuer`, `Admin\PaymentController`, `CancellationService`, `RefundCalculator`, `DisbursementService`, `EventCompletionService`, aturan rundown dan evaluasi dari Tahap C.
- Rumus harga yang dilihat klien. Rekomendasi **memakai ulang** perhitungan harga yang sudah dipakai saat menambah item ke acara; **dilarang** menulis rumus harga/komisi baru (lihat D0).
- Menu dan struktur navigasi dari Tahap A.

### Aturan kerja
- Ikuti `CLAUDE.md`/`AGENTS.md`. Lanjutkan branch `sinkronisasi-rancangan`.
- Satu **commit per sub-langkah** (D1, D2, D3a…D3d). Jalankan `php artisan test --compact` dan `vendor/bin/pint --dirty` setelah tiap sub-langkah; lanjut hanya jika semua lulus.
- Nominal selalu **bilangan bulat rupiah**, aritmetika integer (tanpa float). Frontend tidak menghitung angka; server yang menghitung.
- Tidak ada dependency baru. **Tidak ada AI/LLM/API luar** untuk rekomendasi: aturan deterministik dari data sendiri.
- Tidak ada data contoh di UI; pakai empty state jujur. Klien tidak pernah melihat kontak penyedia (nama usaha boleh).
- Berhenti dan bertanya hanya jika ada ketidakpastian yang mengubah aturan bisnis. Selain itu lanjutkan, laporkan sekali di akhir.

### Kondisi kode saat ini (hasil pemeriksaan, jangan dibangun ulang)
- `events` sudah punya `budget` (integer, wajib saat membuat acara) dan `guest_count`. Pengaturan `budget_allocation` sudah tersimpan dan bisa diedit Admin (kunci: `venue`, `catering`, `dekorasi`, `hiburan`, `peralatan`, `cadangan`; jumlah tepat 100).
- `AvailabilityService` sudah punya `isVenueRoomAvailable`, `isCatalogItemAvailable`, `isSubjectAvailable`. `RatingSummary` dibuat di Tahap C5.
- `venues` punya `capacity`; `venue_rooms` punya `name` dan `capacity` (nullable). `catalog_items` punya `kind` (`layanan`/`peralatan`), `category` (string bebas), `catalog_price`, `pricing_mode`.
- `klien/rekomendasi` masih `Route::view` empty state ("Belum ada data.").
- Route catch-all `/{page}` **sudah tidak ada** sejak Tahap A; yang tersisa hanya **komentar usang** yang menyebutnya di `routes/web.php`.
- Folder `_prototypes/` (±1,4 MB) dan `_updates/` (±1,3 MB) di akar repo, serta `docs/frontend-audit*` dan `docs/reports`, adalah sisa proses desain.
- Masih ada ±73 komentar kode berisi rujukan `BE-n` (komentar, bukan tampilan).

---

## D0 — Pemeriksaan awal (wajib, jangan lewati)

Laporkan (cukup di laporan akhir):
- Bagaimana harga yang dilihat klien dihitung saat item ditambahkan ke acara (file dan metode; termasuk komisi dan biaya kebersihan venue). **Rekomendasi wajib memakai metode itu.**
- Nilai `category` yang benar-benar dipakai di katalog (validasi form vendor dan seeder), lalu usulkan **tabel pemetaan** `category → kunci budget_allocation` (`catering`, `dekorasi`, `hiburan`, atau tak terpetakan). Peralatan dipetakan dari `kind = peralatan`. Kategori tak terpetakan diabaikan dan dilaporkan.
- Cara klien menambah item ke acara draft (route dan syaratnya), dan cara foto venue/ruangan diunggah, divalidasi, dan diverifikasi (`MediaKind`, `MediaPolicy`, batas ukuran), karena D2 memakai pola yang sama.
- Isi tab "Data & denah venue" di portal venue saat ini.

---

## D1 — Rekomendasi paket (Fase 10, versi minimal)

Buat `app/Services/PackageRecommender.php`, **kelas deterministik tanpa penulisan database**. Menerima acara dan mengembalikan struktur data; controller hanya menampilkan.

### Masukan dan alokasi
- Masukan: `budget`, `guest_count`, `event_date` dari acara, dan `budget_allocation` dari Pengaturan (dibaca saat dipanggil; **tidak ada persen tertanam di kode**).
- Alokasi per kategori = `intdiv(budget × persen, 100)`. Kategori `cadangan` **tidak punya kandidat**; ditampilkan sebagai "Dana cadangan: Rp X" saja. Selisih pembulatan masuk ke cadangan (sehingga jumlah semua alokasi = `budget`).

### Kandidat per kategori
- **Venue:** venue terverifikasi dan aktif, `capacity ≥ guest_count`, memiliki minimal satu ruangan yang tersedia pada `event_date` (`AvailabilityService`; bila ruangan punya `capacity`, harus `≥ guest_count`), dan **harga bagi klien** (cara D0) `≤ alokasi venue`.
- **Layanan vendor** (catering/dekorasi/hiburan sesuai pemetaan D0) dan **peralatan:** terverifikasi, aktif, tersedia pada `event_date` untuk jumlah 1, dan harga bagi klien ≤ alokasi kategori.
- Penyedia tanpa verifikasi, nonaktif, atau tidak tersedia **tidak pernah** muncul.
- **Urutan:** rata-rata rating (`RatingSummary`) menurun, `null` terakhir; lalu harga menaik; lalu id. Maksimal **3 kandidat** per kategori.

### Keluaran
- Per kategori: alokasi, daftar kandidat (nama item, nama usaha, harga bagi klien, rating bila ada), atau pesan "Belum ada pilihan yang cocok untuk alokasi ini".
- **Ringkasan paket:** kandidat teratas tiap kategori, total estimasi, dan sisa anggaran (budget − total estimasi). Sisa tidak boleh negatif: bila total melebihi anggaran, kandidat teratas diganti kandidat berikutnya yang muat; bila tetap tidak muat, kategori itu dikosongkan. Ringkasan hanyalah saran; **tidak disimpan**.
- Label tetap: "Rekomendasi otomatis berdasarkan anggaran, jumlah tamu, tanggal, dan aturan alokasi. Harga dan ketersediaan final dikonfirmasi saat pesanan diajukan."

### UI
- Bagian **"Rekomendasi"** di detail acara Klien untuk acara `draft` (route eksplisit `klien.acara.rekomendasi`). **Hapus** `Route::view('/rekomendasi')` dan view lamanya; URL lama 404.
- Tombol **"Tambah ke acara"** pada tiap kandidat memakai **route dan validasi tambah item yang sudah ada** (tanpa logika pesanan baru; ketersediaan tetap dicek ulang oleh server saat ditambah). Tidak ada "tambah semua sekaligus".
- Anggaran 0 atau acara tanpa tanggal/tamu valid: empty state yang menjelaskan apa yang kurang.
- Bila ada rating, tampilkan juga di halaman katalog klien (bintang + jumlah penilaian, hanya jika jumlah > 0). Tidak ada nama atau komentar klien.

### Tes untuk D1
1. **Alokasi:** untuk `budget = 100.000.000` dan alokasi bawaan 35/30/15/10/5/5 → 35.000.000/30.000.000/15.000.000/10.000.000/5.000.000/5.000.000; untuk `budget = 100.000.001` jumlah alokasi tetap = 100.000.001 (selisih ke cadangan).
2. Filter: kapasitas venue < tamu tersingkir; ruangan terisi pada tanggal itu tersingkir; harga di atas alokasi tersingkir; belum terverifikasi/nonaktif tersingkir.
3. Urutan: rating menurun, `null` terakhir, tie-break harga lalu id; maksimal 3 per kategori.
4. Pengaturan: mengubah `budget_allocation` langsung mengubah hasil.
5. Harga yang tampil **sama** dengan harga saat item ditambahkan ke acara (bandingkan keduanya pada data yang sama).
6. Hanya pemilik acara; hanya acara `draft`; tidak ada kontak penyedia pada halaman; tidak ada penulisan database (jumlah baris `order_items` tidak berubah setelah membuka halaman).
7. Total estimasi ringkasan ≤ anggaran; tidak ada float (periksa tipe hasil).

Commit: `D1: rekomendasi paket berbasis anggaran dan ketersediaan`.

---

## D2 — Layout ruangan (Fase 11, versi minimal)

> **Definisi bawaan.** Layout ruangan = (a) **denah** (gambar tata ruang) yang diunggah venue per ruangan, dan (b) **susunan ruangan** yang dipilih klien untuk acaranya. Bila sesuai rancangan berbeda, berhenti di D0 dan laporkan; jangan membangun tafsiran lain.

### (a) Denah per ruangan (venue)
- Venue dapat mengunggah **gambar denah** per ruangan (maks 3 per ruangan; jenis media baru `Denah`, mengikuti aturan unggah, ukuran, dan verifikasi foto venue yang sudah ada di D0). Denah dapat dihapus oleh venue pemilik (ini media, bukan data transaksi).
- Visibilitas mengikuti foto: tampil ke klien di detail venue **hanya** bila venue terverifikasi; `MediaPolicy` diperluas. Venue lain tidak dapat mengubah denah milik venue ini.
- Tampil di tab "Data & denah venue" (venue) dan di halaman detail venue klien, per ruangan, dengan teks alternatif. Empty state bila belum ada.

### (b) Susunan ruangan pilihan klien
- Migration tambahan (hanya menambah kolom nullable di `events`): `room_layout` dan `room_layout_notes`.
- `room_layout` memakai **enum tetap**: `teater`, `meja_bundar`, `kelas`, `huruf_u`, `prasmanan_berdiri`, `lesehan`. `room_layout_notes` opsional maks 500 karakter.
- Klien dapat mengisi/mengubah di detail acara selama status `draft` sampai `terkonfirmasi`; terkunci setelah `selesai`/`dibatalkan`. Aksi tulis: route eksplisit, FormRequest, policy pemilik, AuditLog.
- Dibaca (hanya lihat) oleh **venue** pada halaman pesanan/rundown acaranya dan oleh **Admin** di detail acara. Tidak ada validasi kapasitas per susunan (di luar lingkup).

### Tes untuk D2
Unggah denah (jenis/ukuran valid dan tidak valid); maks 3; hanya venue pemilik yang mengunggah/menghapus; klien melihat denah venue terverifikasi saja; venue belum terverifikasi tidak menampilkan denah ke klien; susunan hanya enum yang valid; terkunci saat `selesai`; klien lain 403; venue melihat susunan tanpa nama klien; migration dapat di-rollback.

Commit: `D2: denah ruangan venue dan susunan ruangan acara`.

---

## D3 — Pembersihan akhir dan sinkronisasi dokumen (Fase 13)

**Dikerjakan paling akhir** dan dipecah menjadi commit kecil agar mudah dikembalikan. Tidak ada perubahan logika bisnis di D3.

### D3a — Audit route, tautan, dan sisa prototipe (tes baru, perbaikan seperlunya)
1. **Tes keamanan rute:** iterasi `Route::getRoutes()`; setiap route non-publik (di luar daftar putih eksplisit: landing, katalog publik, login/daftar, lupa sandi, verifikasi) wajib punya middleware `auth` + `role`. Setiap route punya nama. Tidak ada `Route::view` yang melayani data yang seharusnya dinamis.
2. **Tes tautan mati ("crawl"):** untuk tiap peran (data demo), buka semua halaman GET di sidebar dan tab, kumpulkan semua `href` internal, dan pastikan tidak ada yang 404/500 (kecuali daftar pengecualian eksplisit seperti `logout`, unduhan berkas).
3. **Tes sisa prototipe diperluas:** selain tes halaman, **grep seluruh `resources/views`** untuk `BE-` + angka, `prototipe`, `belum terhubung`, `data-note`, dan nama/angka contoh (`PK-026`, `Nadia`, `Sound system`, dan sejenisnya). Hasil harus kosong.
4. **URL lama → 404:** pastikan semua halaman yang dihapus sejak Tahap A (kalender ketersediaan, `klien/pesanan`, `klien/rundown`, `klien/evaluasi`, `klien/rekomendasi`, dan lainnya) mengembalikan 404 atau redirect yang disengaja.
5. **Uang bebas float:** `grep -rnE "float|round\(|floor\(|ceil\(" app/Services app/Http` lalu tinjau; setiap pemakaian pada nominal rupiah harus diganti aritmetika integer atau dibenarkan tertulis di laporan (mis. rating).
6. **Migrasi:** `php artisan migrate:fresh --seed` berhasil pada SQLite bersih; `migrate:rollback` seluruh migration Tahap B dan D (yang menambah kolom) tidak error.

Commit: `D3a: tes audit route, tautan, prototipe, dan migrasi`.

### D3b — Bersihkan komentar dan sisa kode
- Hapus komentar usang yang menyebut route catch-all (`routes/web.php`: "sebelum catch-all", "Halaman statis bernama (pengganti catch-all…)"); ganti dengan komentar netral atau hapus. Rujukan `BE-n` di **komentar kode**: biarkan bila masih berguna sebagai jejak, **jangan** mengubah logika; laporkan jumlahnya.
- Cari **file yatim** (view, controller, request, policy, factory, test) yang tidak lagi dirujuk: `grep` nama kelas/view. Hapus hanya yang terbukti tidak dipakai dan tidak punya tes yang bermakna; daftar semuanya di laporan.
- Pastikan `LocalDemoSeeder` jelas fiktif (nomor WhatsApp contoh, nama contoh) dan **tidak dipanggil** otomatis di lingkungan `production` (periksa `DatabaseSeeder`).
- ⛔ **Jangan hapus** `_prototypes/`, `_updates/`, `docs/frontend-audit*`, dan `docs/reports`. Tulis di laporan: ukuran, apakah masih dirujuk (`grep`), dan rekomendasi (arsipkan/hapus/pertahankan). **Tunggu persetujuan saya.**

Commit: `D3b: bersihkan komentar usang dan file yatim`.

### D3c — Sinkronisasi dokumen
- `README.md`: status seluruh modul (semua selesai), daftar menu final per portal, cara menjalankan lokal (termasuk `migrate`, `LocalDemoSeeder`, akun demo), cara menjalankan tes.
- `docs/BACKEND_BUSINESS_RULES.md`: seluruh aturan dari Tahap A–D, bernomor berurutan, tanpa aturan ganda atau bertentangan.
- `docs/BACKEND_DATABASE_DESIGN.md`: tambahan skema sejak Tahap A (kolom rekening refund dan `refund_paid_at`, jenis media `BuktiRefund`, `BuktiPencairan`, `Denah`, kolom `room_layout*`, kunci `admin_whatsapp`).
- `docs/BACKEND_API_CONTRACT.md`: daftar route final dibuat dari `php artisan route:list --except-vendor` (nama, metode, URI, peran), bukan ditulis tangan.
- `docs/AI_AGENT_CONTEXT.md` dan `docs/BACKEND_AGENT_CONTEXT.md`: perbarui konteks (status, struktur, aturan penting).
- `docs/CATATAN_PERUBAHAN_UNTUK_RANCANGAN.md`: **konsolidasikan** seluruh penyimpangan dari dokumen rancangan A–D dalam satu daftar rapi: (1) use case baru (tombol WhatsApp, rekomendasi, denah), (2) fitur dihapus (kalender manual, bar pencarian, hapus katalog, tambah akun penyedia), (3) perubahan aturan (rumus refund, pembulatan, rundown oleh vendor, pendapatan perusahaan), (4) tambahan skema. Tujuannya agar dokumen rancangan resmi dapat diperbarui dari satu sumber.
- Tambahkan `docs/INSTRUKSI_TAHAP_A…D` bila belum ada (arsip riwayat kerja).

Commit: `D3c: sinkronisasi dokumen`.

### D3d — Verifikasi akhir menyeluruh
- `php artisan test --compact` dan `vendor/bin/pint --dirty` (lalu `vendor/bin/pint --test` pada seluruh proyek) lulus.
- `php artisan optimize:clear` lalu `php artisan route:list` tanpa error; `php artisan config:cache` dan `route:cache` berhasil (menangkap route berpenutup/closure yang tidak dapat di-cache) lalu dibatalkan kembali dengan `optimize:clear`.
- **Satu skenario penuh di browser** dengan akun demo setiap peran (hasil wajib dilaporkan):
  1. Klien: buat acara dengan anggaran → buka Rekomendasi → tambah item → pilih susunan ruangan → ajukan.
  2. Admin: review pesanan → setujui → tagihan terbit.
  3. Klien: bayar (unggah bukti) → Admin validasi → acara `terkonfirmasi`.
  4. Klien dan Vendor: susun rundown; Venue dan Admin melihat rundown dan susunan ruangan.
  5. Admin: tandai selesai (data uji `event_date` kemarin) → pencairan hak pesanan → catat transfer.
  6. Klien: isi evaluasi → rating tampil di Admin dan penyedia.
  7. **Jalur batal:** acara lain → ajukan pembatalan → proses → refund selesai → kompensasi tampil di pencairan penyedia.
  8. Cek dashboard tiap peran: angka sesuai, tanpa data contoh; lonceng dan tombol WhatsApp berfungsi.
- Catat setiap temuan dan perbaikannya.

Commit: `D3d: perbaikan dari verifikasi akhir` (hanya bila ada perbaikan).

---

## Format laporan akhir Tahap D

1. Hasil pemeriksaan D0 (pemetaan kategori, sumber rumus harga, definisi layout yang dipakai).
2. Daftar commit D1…D3d dan file yang berubah per commit.
3. Hasil `php artisan test --compact` (jumlah tes) dan `vendor/bin/pint --dirty`.
4. Hasil audit D3a: jumlah route diperiksa, tautan di-crawl, file yatim yang dihapus, jumlah komentar `BE-n` yang tersisa, hasil pemeriksaan float.
5. Hasil verifikasi manual skenario penuh di browser.
6. Daftar kandidat penghapusan (`_prototypes`, `_updates`, `docs/frontend-audit*`, `docs/reports`) dengan ukuran dan rekomendasi, **menunggu persetujuan**.
7. Ringkasan akhir: kesesuaian terhadap rancangan, penyimpangan yang disengaja, dan hal yang sengaja ditunda.

### Asumsi bawaan (laporkan bila agent mengubahnya)
- Rekomendasi bersifat saran deterministik (tanpa AI), tidak disimpan, dan tidak membuat pesanan otomatis; klien menambah item satu per satu.
- Alokasi memakai `budget_allocation` dari Pengaturan; selisih pembulatan masuk cadangan; kategori katalog dipetakan dengan tabel hasil D0.
- Harga rekomendasi = harga bagi klien yang sama dengan saat item ditambahkan ke acara.
- Layout ruangan = denah gambar per ruangan oleh venue + pilihan susunan dari daftar tetap oleh klien; tidak ada editor denah interaktif dan tidak ada validasi kapasitas per susunan.
- Rating tampil di katalog klien hanya bila jumlah penilaian > 0.
- Folder prototipe dan arsip desain tidak dihapus tanpa persetujuan.
- Komentar `BE-n` di kode dibiarkan; hanya komentar catch-all usang yang dibersihkan.

**Setelah laporan ini dikonfirmasi, seluruh rencana (Tahap A–D) selesai.**
