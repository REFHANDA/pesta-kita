# Roadmap Penerapan Pembaruan Frontend Pesta Kita

**Versi:** 1.0\
**Tanggal penyusunan:** 13 November 2026\
**Dasar:** *Analisis Perbandingan Fitur dan Rekomendasi Pengembangan
Pesta Kita* serta temuan audit UI/UX Portal Penyedia dan Admin.\
**Cakupan:** pembaruan frontend untuk empat area pengguna: Klien,
Vendor, Pemilik Venue, dan Admin.

------------------------------------------------------------------------

## 1. Tujuan Dokumen

Dokumen ini menjadi panduan bertahap untuk menerapkan rekomendasi
pembaruan frontend Pesta Kita. Fokus utamanya adalah memperbaiki fondasi
antarmuka yang sudah ada sebelum menambahkan modul baru.

Tujuan penerapan:

1.  Menyamakan pola tampilan dan interaksi pada seluruh role.
2.  Memperbaiki masalah aksesibilitas dan responsivitas yang ditemukan
    pada audit.
3.  Membuat informasi transaksi, pembayaran, refund, dan pencairan lebih
    mudah dipahami.
4.  Memperjelas alur kerja Klien, Vendor, Pemilik Venue, dan Admin.
5.  Menyiapkan komponen antarmuka yang dapat digunakan ulang.
6.  Mengadopsi pola baik dari The Bash, Tagvenue, Bridestory, Peerspace,
    dan Cvent tanpa mengubah model bisnis inti Pesta Kita.

> **Batasan penting:** dokumen ini adalah roadmap frontend. Integrasi
> backend, database, payment gateway, scheduler, dan proses otomatis
> harus dikerjakan sebagai pekerjaan terkait yang terpisah. Tampilan
> frontend tidak boleh menampilkan status berhasil sebelum backend
> benar-benar mengonfirmasi proses tersebut.

## 2. Prinsip yang Harus Dipertahankan

Pembaruan frontend harus tetap mengikuti karakter Pesta Kita sebagai
*managed marketplace*.

-   Pesta Kita menjadi perantara utama antara Klien dan penyedia.
-   Admin tetap menjadi pengendali persetujuan pesanan pada MVP.
-   Kontak pribadi penyedia tidak ditampilkan kepada Klien.
-   Komunikasi dilakukan melalui kanal internal yang disediakan sistem
    jika fitur tersebut sudah diterapkan.
-   Pembayaran Klien tetap menggunakan satu tagihan gabungan per acara.
-   Pembayaran MVP tetap diarahkan ke rekening perusahaan melalui
    transfer manual.
-   Pencairan hak Vendor/Pemilik Venue dilakukan setelah acara selesai
    dan diproses sesuai alur Admin.
-   Komisi 10% ditambahkan ke tagihan Klien sesuai Model A yang menjadi
    keputusan analisis.
-   Kebijakan refund berjenjang dan aturan transaksi harus terlihat
    serta tersimpan sebagai ketentuan transaksi.
-   Ulasan hanya dapat diberikan setelah acara selesai.

Frontend tidak boleh mengubah aturan tersebut secara sepihak. Perubahan
kebijakan harus disepakati pada level produk dan backend terlebih
dahulu.

## 3. Dasar Pembaruan dari Hasil Perbandingan

  -----------------------------------------------------------------------
  Referensi                           Pola yang diadaptasi ke Pesta Kita
  ----------------------------------- -----------------------------------
  The Bash                            Katalog vendor berbasis portofolio,
                                      Request Quote, informasi layanan,
                                      ulasan terverifikasi, dan
                                      penanganan vendor pengganti

  Tagvenue                            Informasi venue, kalender
                                      ketersediaan, kapasitas, aturan
                                      venue, kebijakan pembatalan, dan
                                      transparansi pembayaran

  Bridestory                          Profil bisnis vendor, paket
                                      layanan, inbox internal, statistik
                                      performa, dan pengelolaan prospek

  Peerspace                           Kalender venue, aturan ruang, biaya
                                      tambahan yang transparan, dan
                                      status pencairan

  Cvent                               Perencanaan acara, rundown, layout,
                                      checklist operasional, dan
                                      pelaporan secara bertahap
  -----------------------------------------------------------------------

Pola yang tidak menjadi prioritas awal: listing berbayar, ranking
berbayar, Instant Book untuk semua penyedia, CRM enterprise, event app
kompleks, analitik ROI tingkat lanjut, dan cicilan kompleks.

## 4. Urutan Tahapan

  -----------------------------------------------------------------------
  Tahap             Nama              Prioritas         Hasil utama
  ----------------- ----------------- ----------------- -----------------
  0                 Audit awal dan    Wajib             Inventaris
                    persiapan                           halaman,
                                                        komponen, dan
                                                        masalah aktual

  1                 Perbaikan blocker Sangat tinggi     Aksesibilitas,
                    UI/UX                               kontras, modal,
                                                        target sentuh

  2                 Design system dan Sangat tinggi     Tampilan
                    komponen bersama                    konsisten dan
                                                        komponen reusable

  3                 Responsivitas dan Tinggi            Tabel, form,
                    state antarmuka                     loading, empty,
                                                        error, success

  4                 Penyempurnaan     Tinggi            Navigasi dan
                    portal per role                     dashboard Klien,
                                                        Vendor, Venue,
                                                        Admin

  5                 Katalog, profil,  Tinggi            Informasi
                    dan ketersediaan                    penyedia/venue
                                                        lebih lengkap

  6                 Transaksi,        Sangat tinggi     Informasi
                    tagihan,                            keuangan jelas
                    pembayaran, dan                     dan mudah
                    pencairan                           diverifikasi

  7                 Koordinasi acara  Menengah          Rundown, layout,
                    dan evaluasi                        checklist, review

  8                 Fitur lanjutan    Setelah fondasi   Inbox, kalender
                    dan optimasi      stabil            sinkron,
                                                        pengganti vendor,
                                                        analitik
  -----------------------------------------------------------------------

Tahap 0--3 sebaiknya diselesaikan sebelum pengembangan fitur frontend
baru dalam jumlah besar. Tahap 6 perlu dikerjakan dengan pengujian ketat
karena berkaitan dengan nominal dan status keuangan.

------------------------------------------------------------------------

# TAHAP 0 --- Audit Awal dan Persiapan

## 0.1 Tujuan

Memastikan tim mengetahui kondisi frontend yang benar-benar tersedia
sebelum melakukan perubahan. Tahap ini mencegah pembuatan ulang halaman
yang sebenarnya sudah ada dan mencegah fitur yang belum didukung backend
ditampilkan seolah-olah sudah aktif.

## 0.2 Pekerjaan

1.  Petakan struktur proyek frontend: framework, routing, folder
    halaman, komponen, stylesheet, aset, dan sistem autentikasi yang
    sudah tersedia.
2.  Inventaris seluruh halaman berdasarkan role:
    -   Klien.
    -   Vendor.
    -   Pemilik Venue.
    -   Admin.
3.  Catat halaman yang sudah ada, belum ada, duplikat, rusak, atau belum
    memiliki alur interaksi.
4.  Cocokkan halaman dengan proses bisnis: katalog, acara, pesanan,
    persetujuan, keberatan, perubahan harga, tagihan, pembayaran,
    refund, rundown, penyelesaian, pencairan, dan ulasan.
5.  Periksa komponen berulang yang seharusnya menjadi komponen bersama,
    misalnya sidebar, header, badge status, tabel, modal, dan form
    field.
6.  Periksa kondisi data: data asli dari API, data dummy, data statis,
    dan data yang belum tersedia.
7.  Buat daftar temuan dengan kategori Blocker, Critical, Major, Minor,
    dan Enhancement.
8.  Ambil screenshot baseline pada lebar 1440 px, 768 px, dan 375 px
    untuk halaman utama setiap role.

## 0.3 Keluaran

-   Daftar halaman per role.
-   Peta navigasi frontend.
-   Daftar komponen yang sudah ada.
-   Daftar masalah UI/UX berdasarkan tingkat prioritas.
-   Daftar ketergantungan backend.
-   Screenshot kondisi sebelum pembaruan.

## 0.4 Kriteria selesai

-   Tidak ada halaman penting yang luput dari inventaris.
-   Setiap halaman memiliki role pemilik yang jelas.
-   Setiap fitur diberi status: sudah terhubung backend, masih statis,
    atau belum tersedia.
-   Tersedia baseline visual untuk perbandingan setelah implementasi.

------------------------------------------------------------------------

# TAHAP 1 --- Perbaikan Blocker UI/UX dan Aksesibilitas

## 1.1 Tujuan

Menyelesaikan masalah yang menghambat penggunaan sebelum menambah fitur.
Tahap ini menjadi prioritas pertama karena audit UI/UX menemukan masalah
akses keyboard, ukuran target sentuh, dan kontras teks.

## 1.2 Modal dan dialog

Perbaikan yang dilakukan:

-   Saat modal dibuka, fokus keyboard berpindah ke elemen yang relevan
    di dalam modal.
-   Fokus tidak boleh berpindah ke konten di belakang modal (*focus
    trap*).
-   Tombol Escape menutup modal jika tindakan tersebut aman dan sesuai
    konteks.
-   Setelah modal ditutup, fokus dikembalikan ke tombol yang membuka
    modal.
-   Berikan judul modal yang jelas dan hubungan semantik antara judul,
    deskripsi, dan dialog.
-   Dialog konfirmasi untuk aksi berisiko harus menjelaskan objek dan
    konsekuensi tindakan.

Aksi yang perlu menggunakan konfirmasi: hapus data, batalkan pesanan,
tolak permintaan, menyetujui perubahan harga, memproses refund, dan
menandai acara selesai.

## 1.3 Ukuran target sentuh

-   Perbesar tombol ikon aksi yang terlalu kecil.
-   Gunakan target interaksi minimal 24 × 24 CSS px sesuai batas minimum
    WCAG 2.2 AA, dan usahakan lebih besar untuk aksi utama pada
    perangkat sentuh.
-   Beri jarak antartombol agar tidak mudah salah tekan.
-   Jangan hanya mengandalkan ikon tanpa label atau tooltip yang sesuai.

## 1.4 Kontras dan keterbacaan

-   Periksa seluruh teks, placeholder, label sekunder, badge, dan ikon
    terhadap warna latar.
-   Teks normal harus memenuhi rasio kontras minimal 4,5:1.
-   Teks besar mengikuti ketentuan minimal 3:1.
-   Jangan menggunakan warna muted yang terlalu pucat untuk informasi
    yang masih diperlukan pengguna.
-   Status tidak boleh dibedakan hanya dengan warna; sertakan teks
    seperti "Menunggu", "Disetujui", atau "Ditolak".

## 1.5 Navigasi keyboard dan fokus

-   Semua tombol, tautan, menu, tab, dropdown, dan kontrol form dapat
    diakses dengan keyboard.
-   Urutan Tab harus logis mengikuti urutan visual.
-   Indikator fokus harus terlihat jelas.
-   Jangan menghilangkan outline tanpa pengganti yang setara.
-   Pastikan menu dropdown dapat dibuka, dipilih, dan ditutup
    menggunakan keyboard.

## 1.6 Formulir yang aksesibel

-   Setiap input memiliki label yang jelas.
-   Kesalahan validasi ditampilkan dekat dengan field terkait.
-   Pesan kesalahan menjelaskan cara memperbaiki input.
-   Field wajib ditandai secara jelas.
-   Instruksi tidak hanya disampaikan melalui placeholder.
-   Setelah submit gagal, fokus atau ringkasan kesalahan diarahkan ke
    bagian yang relevan.

## 1.7 Kriteria penerimaan

-   Modal dapat digunakan sepenuhnya dengan keyboard.
-   Fokus tidak bocor ke halaman di belakang modal.
-   Fokus kembali ke pemicu modal setelah ditutup.
-   Target sentuh tidak berada di bawah batas minimum yang ditetapkan.
-   Teks normal memenuhi rasio kontras 4,5:1.
-   Semua input memiliki label dan pesan validasi yang dapat dipahami.
-   Tidak ada status penting yang hanya mengandalkan warna.

------------------------------------------------------------------------

# TAHAP 2 --- Design System dan Komponen Bersama

## 2.1 Tujuan

Membuat standar visual dan interaksi yang sama untuk seluruh role agar
perubahan dapat diterapkan konsisten dan lebih mudah dipelihara.

## 2.2 Design tokens

Buat token terpusat untuk:

-   Warna utama, sekunder, latar, permukaan, border, teks, dan status.
-   Tipografi: jenis font, ukuran, ketebalan, dan line-height.
-   Spacing: jarak antar elemen dan padding.
-   Radius: sudut kartu, tombol, input, dan modal.
-   Elevation/shadow.
-   Breakpoint responsif.
-   Ukuran ikon.
-   Z-index untuk dropdown, modal, toast, dan elemen overlay.

Token status minimal mencakup status netral, informasi, sukses,
peringatan, dan bahaya. Pemilihan warna harus tetap lolos pemeriksaan
kontras.

## 2.3 Komponen bersama yang harus dibuat

### A. Navigasi

-   `AppShell`: kerangka halaman.
-   `Sidebar`: navigasi utama per role.
-   `Topbar`: judul halaman, profil, dan akses notifikasi.
-   `Breadcrumb`: penanda posisi halaman.
-   `MobileNavigation`: navigasi pada layar kecil.

### B. Status dan informasi

-   `StatusBadge`: status pesanan, pembayaran, verifikasi, refund, dan
    payout.
-   `Alert`: informasi, peringatan, dan kesalahan.
-   `Tooltip`: keterangan singkat untuk ikon atau istilah.
-   `Timeline`: urutan proses transaksi.
-   `SlaCountdown`: sisa waktu review Admin jika data SLA tersedia.

### C. Data dan daftar

-   `DataTable`: tabel desktop dengan header yang jelas.
-   `ResponsiveTable`: pola tampilan tabel untuk layar kecil.
-   `SearchInput`: pencarian.
-   `FilterBar`: filter status, tanggal, dan kategori.
-   `Pagination`: navigasi halaman data.
-   `EmptyState`: tampilan ketika belum ada data.
-   `LoadingState`: indikator pemuatan.
-   `ErrorState`: kegagalan beserta tindakan pemulihan.

### D. Form dan dialog

-   `FormField`: label, input, hint, dan error.
-   `SelectField`: pilihan.
-   `DateField`: tanggal.
-   `FileUpload`: unggah dokumen atau bukti.
-   `FilePreview`: pratinjau dokumen.
-   `Modal`: dialog umum.
-   `ConfirmDialog`: konfirmasi aksi berisiko.
-   `Toast`: pemberitahuan hasil aksi.

### E. Keuangan

-   `InvoiceSummary`: ringkasan tagihan.
-   `PaymentBreakdown`: rincian pembayaran.
-   `UniqueCodeDisplay`: kode unik transfer.
-   `PayoutSummary`: ringkasan hak penyedia.
-   `DocumentPreview`: pratinjau invoice, nota, atau bukti transfer.

## 2.4 Aturan penggunaan

-   Komponen harus memiliki tampilan konsisten di semua role.
-   Komponen menerima data dan callback melalui properti yang jelas.
-   Jangan membuat salinan komponen dengan perilaku sama hanya karena
    dipakai di halaman berbeda.
-   Komponen boleh memiliki variasi, misalnya ukuran kecil/besar atau
    mode desktop/mobile, tetapi aturan dasarnya sama.
-   Semua komponen interaktif wajib mendukung keyboard dan fokus
    terlihat.
-   Komponen status harus menggunakan label teks yang berasal dari
    status sistem, bukan status buatan frontend.

## 2.5 Kriteria penerimaan

-   Token warna, tipografi, spacing, radius, dan breakpoint
    terdokumentasi.
-   Komponen bersama digunakan pada lebih dari satu halaman yang
    relevan.
-   Badge status konsisten di seluruh role.
-   Modal, form field, tabel, dan state dasar tidak memiliki
    implementasi duplikat yang tidak perlu.
-   Perubahan gaya global dapat dilakukan tanpa mengedit setiap halaman
    satu per satu.

------------------------------------------------------------------------

# TAHAP 3 --- Responsivitas dan State Antarmuka

## 3.1 Tujuan

Memastikan aplikasi tetap dapat digunakan pada desktop, tablet, dan
ponsel serta memberikan umpan balik yang benar pada setiap kondisi data.

## 3.2 Breakpoint pengujian

Gunakan tiga ukuran minimum untuk validasi:

  Perangkat     Lebar uji Fokus pemeriksaan
  ----------- ----------- -------------------------------------------
  Desktop         1440 px Sidebar, tabel, grid, dan detail
  Tablet           768 px Navigasi, grid dua kolom, dan tabel
  Mobile           375 px Form, tabel, tombol, modal, dan ringkasan

Breakpoint implementasi boleh menyesuaikan framework, tetapi ketiga
lebar pengujian harus tetap dilakukan.

## 3.3 Responsivitas tabel

-   Hindari halaman melebar melebihi viewport.
-   Tentukan kolom yang paling penting untuk ditampilkan pada layar
    kecil.
-   Gunakan scroll horizontal hanya jika memang dibutuhkan dan berikan
    petunjuk visual.
-   Untuk data yang lebih kompleks, tampilkan setiap baris sebagai kartu
    pada layar kecil.
-   Aksi utama harus tetap mudah ditemukan.
-   Pastikan kolom nominal dan tanggal tidak terpotong tanpa cara
    melihat detailnya.

Halaman yang perlu diperiksa secara khusus berdasarkan audit: tabel
pencairan dan daftar Admin penyedia.

## 3.4 Responsivitas layout

-   Sidebar desktop dapat berubah menjadi drawer atau navigasi ringkas
    pada mobile.
-   Grid kartu menyesuaikan jumlah kolom.
-   Form satu kolom pada layar sempit.
-   Tombol utama tidak terpotong dan memiliki area klik memadai.
-   Modal pada mobile menggunakan lebar yang sesuai dan tidak menutupi
    tombol penting.
-   Header tidak bertumpuk dengan konten.

## 3.5 State antarmuka wajib

Setiap halaman daftar dan proses data harus menangani:

1.  **Loading:** data sedang dimuat; hindari tampilan kosong yang
    disangka error.
2.  **Empty:** data belum tersedia; jelaskan penyebab yang diketahui dan
    tindakan berikutnya.
3.  **Error:** proses gagal; tampilkan pesan dan opsi coba lagi jika
    aman.
4.  **Success:** tampilkan hasil setelah server mengonfirmasi
    keberhasilan.
5.  **Disabled:** jelaskan mengapa tindakan belum dapat dilakukan jika
    alasannya relevan.
6.  **Partial:** tampilkan bagian yang berhasil dan gagal secara
    terpisah bila proses memang dapat parsial.

## 3.6 Pencegahan aksi ganda

-   Tombol submit dinonaktifkan sementara ketika permintaan sedang
    diproses.
-   Tampilkan indikator proses.
-   Jangan mengirim permintaan berulang karena pengguna menekan tombol
    beberapa kali.
-   Setelah kegagalan jaringan, jelaskan apakah pengguna perlu mencoba
    ulang atau memeriksa status terlebih dahulu.
-   Untuk transaksi keuangan, status harus diperiksa kembali dari server
    sebelum pengguna mengulangi tindakan.

## 3.7 Kriteria penerimaan

-   Halaman utama dapat digunakan pada lebar 1440, 768, dan 375 px.
-   Tidak ada konten utama yang terpotong tanpa akses alternatif.
-   Semua halaman daftar memiliki loading, empty, dan error state.
-   Submit form memiliki indikator proses dan mencegah klik ganda.
-   Keberhasilan hanya ditampilkan setelah respons backend menyatakan
    berhasil.

------------------------------------------------------------------------

# TAHAP 4 --- Penyempurnaan Portal Berdasarkan Role

## 4.1 Tujuan

Membuat area setiap role mudah dipahami tanpa mencampur fungsi yang
bukan kewenangannya.

## 4.2 Portal Klien

Prioritas tampilan:

1.  **Dashboard Klien**
    -   Ringkasan acara aktif.
    -   Status pesanan.
    -   Status tagihan dan pembayaran.
    -   Agenda atau tanggal acara.
    -   Tindakan yang perlu dilakukan Klien.
2.  **Perencanaan acara**
    -   Konsep, tanggal, tema, jumlah tamu, dan budget.
    -   Ringkasan kebutuhan acara.
    -   Rekomendasi paket Hemat, Seimbang, dan Premium jika hasil
        rekomendasi tersedia.
    -   Penjelasan alasan rekomendasi secara ringkas.
3.  **Katalog**
    -   Kategori vendor, venue, dan peralatan.
    -   Foto, portofolio, deskripsi, kapasitas, lokasi, harga/paket, dan
        kebijakan.
    -   Filter sesuai kebutuhan acara.
    -   Tombol lihat detail dan ajukan permintaan.
4.  **Detail acara**
    -   Daftar penyedia yang terkait.
    -   Status setiap pesanan.
    -   Tagihan gabungan.
    -   Rundown dan layout sesuai hak akses.
    -   Riwayat perubahan yang relevan.
5.  **Pusat transaksi**
    -   Tagihan.
    -   Instruksi transfer.
    -   Unggah bukti pembayaran.
    -   Status validasi Admin.
    -   Pengajuan pembatalan/refund sesuai kebijakan.

Kontak pribadi Vendor atau Pemilik Venue tidak boleh ditampilkan.

## 4.3 Portal Vendor

Prioritas tampilan:

1.  **Dashboard Vendor**
    -   Ringkasan pesanan.
    -   Pesanan yang menunggu tindakan atau informasi.
    -   Pengajuan harga yang masih diproses.
    -   Status pencairan.
    -   Ringkasan performa jika datanya tersedia.
2.  **Profil bisnis**
    -   Nama usaha, deskripsi, kategori, lokasi, foto, portofolio, dan
        dokumen.
    -   Status verifikasi.
    -   Informasi yang belum lengkap.
3.  **Layanan dan paket**
    -   Daftar layanan.
    -   Deskripsi dan cakupan layanan.
    -   Harga/paket.
    -   Foto atau contoh hasil.
    -   Aturan dan kebutuhan khusus.
4.  **Pesanan**
    -   Daftar pesanan dan filter status.
    -   Detail acara yang relevan.
    -   Timeline pesanan.
    -   Tombol ajukan keberatan jika diizinkan.
    -   Riwayat keputusan Admin.
5.  **Pengajuan perubahan harga**
    -   Harga saat ini.
    -   Harga yang diajukan.
    -   Alasan perubahan.
    -   Status persetujuan.
    -   Riwayat pengajuan.
6.  **Pencairan**
    -   Nilai hak penyedia.
    -   Status pencairan.
    -   Rekening tujuan terverifikasi.
    -   Tanggal proses.
    -   Bukti transfer setelah tersedia.

Vendor tidak boleh mengubah status persetujuan final pesanan yang
menjadi kewenangan Admin.

## 4.4 Portal Pemilik Venue

Prioritas tampilan:

1.  Dashboard venue dan ringkasan booking.
2.  Profil venue, foto, lokasi, kapasitas, fasilitas, dan aturan ruang.
3.  Daftar ruang atau paket venue jika ada.
4.  Kalender ketersediaan.
5.  Daftar booking dan status pengajuan.
6.  Kebijakan pembatalan yang berlaku.
7.  Riwayat perubahan dan informasi pencairan.

Kalender harus membedakan tanggal tersedia, terblokir, dan telah
dipesan. Jangan menyatakan slot tersedia jika data ketersediaan belum
berhasil dimuat.

## 4.5 Portal Admin

Prioritas tampilan:

1.  Dashboard operasional: antrean review, pesanan, pembayaran,
    keberatan, refund, dan payout.
2.  Verifikasi penyedia: dokumen, profil, keputusan, dan alasan.
3.  Pengelolaan pesanan: detail, persetujuan, penolakan, pengalihan, dan
    histori.
4.  Pengelolaan keberatan dan perubahan harga.
5.  Validasi pembayaran dan dokumen.
6.  Pembatalan dan refund.
7.  Penyelesaian acara dan pencairan.
8.  Pengelolaan katalog dan master data.
9.  Log aktivitas dan laporan.

## 4.6 Aturan akses tampilan

-   Menu hanya menampilkan fungsi yang relevan dengan role.
-   Menyembunyikan tombol di frontend bukan pengganti otorisasi backend.
-   Jika pengguna membuka URL yang bukan kewenangannya, tampilkan
    halaman akses ditolak atau arahkan sesuai aturan aplikasi.
-   Data sensitif hanya ditampilkan kepada role yang berhak.
-   Setiap aksi Admin yang berdampak pada transaksi perlu menampilkan
    objek, nilai, dan konsekuensinya sebelum konfirmasi.

## 4.7 Kriteria penerimaan

-   Navigasi empat role tidak saling tercampur.
-   Setiap halaman memiliki tujuan dan tindakan utama yang jelas.
-   Pengguna dapat mengetahui apa yang perlu dilakukan berikutnya.
-   Tombol tidak muncul untuk tindakan yang tidak relevan dengan role.
-   Backend tetap memvalidasi seluruh hak akses.

------------------------------------------------------------------------

# TAHAP 5 --- Katalog, Profil Penyedia, dan Ketersediaan

> **Catatan penyelarasan penomoran (15 November 2026).** Penomoran subtahap
> Tahap 5 telah diselaraskan dengan standar final yang dipakai pada instruksi
> TRAE, `docs/AI_AGENT_CONTEXT.md`, dan laporan tahap. Standar yang berlaku:
>
> | Nomor | Nama subtahap | Status |
> |---|---|---|
> | 5.1 | Katalog Vendor | Selesai bersyarat |
> | 5.2 | Katalog Venue | Selesai bersyarat |
> | 5.3 | Profil dan Portofolio Penyedia | Selesai bersyarat |
> | 5.4 | Request Quote / Custom Quotation | Frontend selesai bersyarat; backend BE-4 terhubung (2026-11-17) |
> | 5.5 | Kalender Ketersediaan | Frontend selesai bersyarat; backend BE-3b terhubung (2026-11-16) |
> | 5.6 | Kriteria Penerimaan dan Evaluasi Tahap 5 | Selesai (2026-11-15) — hasil: **Diterima Bersyarat** |
>
> Penomoran lama (katalog vendor 5.2, katalog venue 5.3, portofolio 5.4,
> request quote 5.5, kalender 5.6, kriteria penerimaan 5.7) sudah tidak
> berlaku. Bagian "Tujuan" tidak lagi bernomor agar tidak bertabrakan dengan
> nomor subtahap.

## Tujuan Tahap 5

Mengadopsi kekuatan katalog The Bash dan Bridestory serta pengelolaan
venue Tagvenue dan Peerspace, sambil tetap mempertahankan persetujuan
Admin.

## 5.1 Katalog vendor

Tampilkan informasi:

-   Nama usaha dan kategori layanan.
-   Deskripsi singkat dan detail.
-   Foto/portofolio.
-   Paket layanan dan cakupannya.
-   Harga atau keterangan harga berdasarkan penawaran.
-   Lokasi layanan.
-   Kapasitas atau batas layanan jika relevan.
-   Kebijakan dan kebutuhan khusus.
-   Status verifikasi penyedia.

Jangan menampilkan klaim "terverifikasi" jika status verifikasi belum
diberikan oleh sistem.

## 5.2 Katalog venue

Tampilkan informasi:

-   Foto venue.
-   Lokasi.
-   Kapasitas.
-   Fasilitas.
-   Aturan ruang.
-   Harga dan biaya tambahan yang berlaku.
-   Kebijakan pembatalan.
-   Kalender atau informasi ketersediaan.
-   Status verifikasi pemilik venue.

Biaya kebersihan dan biaya lain harus dijelaskan secara terpisah agar
tidak mengejutkan Klien pada tahap tagihan.

## 5.3 Profil dan Portofolio Penyedia

Bagian ini mencakup profil penyedia (Vendor dan Pemilik Venue) beserta
portofolio media yang tampil kepada Klien.

### Portofolio dan media

-   Sediakan galeri foto yang responsif.
-   Gunakan rasio gambar yang konsisten tanpa memotong informasi
    penting.
-   Sediakan teks alternatif untuk gambar informatif.
-   Berikan batas format dan ukuran file pada unggahan.
-   Tampilkan pratinjau sebelum media disimpan jika alur unggah
    mendukungnya.
-   Sediakan pesan ketika portofolio belum tersedia.

## 5.4 Request Quote / Custom Quotation

> **Status frontend: selesai bersyarat (2026-11-15).** Saat laporan UI dibuat,
> backend belum tersedia. Sejak itu BE-4 menghubungkan pengajuan, proposal,
> dan keputusan quote ke server (2026-11-17). Rincian frontend:
> `docs/reports/tahap-5.4-request-quote.md`; backend:
> `docs/reports/BE-4_Implementation_Report_Pesta_Kita.md`.

Alur frontend yang disarankan:

1.  Klien memilih layanan atau venue.
2.  Klien mengisi kebutuhan acara dan detail permintaan.
3.  Sistem menampilkan ringkasan permintaan sebelum dikirim.
4.  Permintaan masuk ke alur Pesta Kita untuk diproses sesuai aturan
    bisnis.
5.  Penyedia dapat memberikan informasi atau usulan harga melalui proses
    yang diizinkan.
6.  Admin memeriksa dan menyetujui perubahan/penawaran.
7.  Klien melihat penawaran yang sudah disahkan dan dapat melanjutkan
    sesuai alur pemesanan.

Frontend harus membedakan dengan jelas antara harga katalog, harga yang
diajukan, dan harga yang sudah disetujui.

## 5.5 Kalender ketersediaan

> **Status: Selesai bersyarat (2026-11-15).** Frontend Kalender Ketersediaan
> telah dibuat untuk tiga portal: Klien (`/klien/kalender-ketersediaan`),
> Vendor (`/vendor/kalender-ketersediaan`), dan Pemilik Venue
> (`/venue/kalender-ketersediaan`). Membedakan tiga status — **Tersedia,
> Tidak tersedia, Belum dikonfirmasi/Sedang ditinjau** — melalui warna +
> ikon + label teks (tidak hanya warna). Klien memilih kategori
> (Vendor/Venue) + penyedia; Vendor/Venue memilih layanan/ruangan (per
> ruangan, bukan satu venue = satu jadwal). Vendor & Venue memiliki kontrol
> prototype yang hanya mengubah state di memori dengan peringatan eksplisit.
> Pemilihan tanggal Klien diteruskan ke alur Request Quote (5.4) lewat
> parameter `?tanggal=`. Bagian ini mencatat kondisi frontend saat dibuat
> (2026-11-15); BE-3b kemudian menghubungkan data ketersediaan dan pengajuan
> harga ke backend. Rincian UI:
> `docs/reports/tahap-5.5-kalender-ketersediaan.md`; implementasi:
> `docs/reports/BE-3b_Wiring_Report_Pesta_Kita.md`.

-   Tampilkan tampilan kalender bulanan.
-   Gunakan legenda teks untuk tersedia, tidak tersedia, dan menunggu
    konfirmasi.
-   Tampilkan detail tanggal ketika dipilih.
-   Sediakan filter venue/layanan jika pengguna mengelola lebih dari
    satu item.
-   Tampilkan waktu pembaruan data jika berguna.
-   Beri peringatan bahwa ketersediaan perlu dikonfirmasi sistem sebelum
    pesanan disahkan.

Instant Book tidak diterapkan secara umum pada tahap ini. Pola tersebut
hanya dapat dipertimbangkan setelah data kalender terbukti akurat dan
aturan operasional mendukungnya.

## 5.6 Kriteria Penerimaan dan Evaluasi Tahap 5

> **Status: Selesai (2026-11-15) — hasil evaluasi: Diterima Bersyarat.**
> Evaluasi menyeluruh Tahap 5.1–5.5 telah dilakukan (tanpa redesign, tanpa
> fitur baru, tanpa memulai Tahap 6). Seluruh antarmuka Tahap 5 berjalan pada
> sisi frontend dengan penanda prototipe yang eksplisit; keterbatasan tersisa
> berasal dari backend yang belum tersedia (batas integrasi), bukan bukti
> fitur backend selesai. Rincian: `docs/reports/tahap-5.6-kriteria-penerimaan-dan-evaluasi.md`.

**Hasil evaluasi ringkas:**

| Kriteria | Status |
|---|---|
| Profil penyedia dan venue menampilkan informasi penting sebelum Klien mengajukan pesanan | Lulus Bersyarat (data statis tanpa backend) |
| Harga katalog, penawaran, dan harga final tidak tertukar | **Lulus** (tiga nominal dibedakan tegas) |
| Media dapat dilihat dengan baik pada desktop dan mobile | Lulus Bersyarat (bukti CSS/HTML; tanpa uji viewport langsung pada sesi evaluasi) |
| Kalender membedakan status tanggal dengan teks/indikator yang dapat dipahami | Lulus Bersyarat (ikon + label + legenda) |
| Ketersediaan yang belum terkonfirmasi tidak ditampilkan sebagai kepastian | **Lulus** (status "Perlu konfirmasi"/"Belum dikonfirmasi", bukan kepastian) |

**Keterbatasan utama yang terdokumentasi:** kalender hanya membedakan tiga status (**Tersedia, Tidak tersedia, Belum dikonfirmasi**) — status **"diblokir"** dan **"sudah dipesan (booked)"** belum dibedakan karena membutuhkan data transaksi backend; tidak dibuat status "booked" palsu. Perbaikan aman yang dilakukan pada evaluasi ini: data role pada `venue/profil` (Vendor→Venue) dan hierarki heading `admin/permintaan-penawaran` (`h3`→`h2`).

Kriteria penerimaan yang dievaluasi:

-   Profil penyedia dan venue menampilkan informasi penting sebelum
    Klien mengajukan pesanan.
-   Harga katalog, penawaran, dan harga final tidak tertukar.
-   Media dapat dilihat dengan baik pada desktop dan mobile.
-   Kalender membedakan status tanggal dengan teks/indikator yang dapat
    dipahami.
-   Ketersediaan yang belum terkonfirmasi tidak ditampilkan sebagai
    kepastian.

------------------------------------------------------------------------

# TAHAP 6 --- Transaksi, Tagihan, Pembayaran, Refund, dan Pencairan

> **Status frontend: selesai bersyarat (2026-11-15).** Pembaruan frontend Tahap 6
> telah dilakukan pada empat portal (Klien, Vendor, Pemilik Venue, Admin)
> untuk delapan fokus: informasi tagihan & rincian biaya, kode unik
> transfer, unggah bukti pembayaran, status & riwayat pembayaran,
> invoice/nota, pembatalan & refund, pencairan penyedia, dan dashboard
> keuangan Admin. Saat laporan ini ditulis, seluruhnya adalah **UI prototipe
> frontend** dan backend transaksi belum tersedia. Perbaikan yang dilakukan:
> penghapusan perhitungan refund final lokal, penegasan komisi 10%
> ditambahkan ke tagihan Klien (bukan dipotong dari hak penyedia),
> penyamaran nomor rekening, penghapusan pencampuran data Vendor pada portal
> Venue, penambahan handler tombol salin kode unik, dan penambahan penanda
> prototipe `.data-note` pada halaman keuangan. Rincian:
> `docs/reports/tahap-6-transaksi-tagihan-pembayaran-refund-pencairan.md`.
>
> **Pembaruan backend (2026-11-17):** BE-6 kini menangani invoice,
> pembayaran, rekening perusahaan, dan pencairan. Refund hanya menerima
> permohonan; keputusan dan nominal final tetap ditunda sampai kebijakan B1
> disetujui. Lihat addendum §8 laporan update backend.

## 6.1 Tujuan

Memperjelas tampilan seluruh proses finansial. Tahap ini memiliki
prioritas sangat tinggi karena kesalahan informasi nominal atau status
dapat menimbulkan salah transfer dan sengketa.

## 6.2 Halaman ringkasan tagihan

Tampilkan setiap komponen secara terpisah:

1.  Subtotal harga penyedia.
2.  Komisi Pesta Kita.
3.  Biaya kebersihan venue jika berlaku.
4.  Biaya lain yang telah disetujui jika ada.
5.  Kode unik transfer.
6.  Total yang harus ditransfer.

Gunakan format mata uang yang konsisten. Jangan menyembunyikan biaya di
balik teks kecil atau label yang ambigu.

Komisi mengikuti keputusan Model A: 10% ditambahkan ke tagihan Klien,
bukan dipotong dari harga penyedia. Perhitungan final tetap berasal dari
backend.

## 6.3 Kode unik transfer

Berdasarkan temuan audit, kode unik tiga digit perlu diberi penekanan
visual.

-   Tampilkan kode unik terpisah dari nominal utama.
-   Berikan label "Kode unik transfer".
-   Sediakan tombol salin.
-   Setelah disalin, tampilkan konfirmasi yang dapat dibaca screen
    reader.
-   Pastikan total transfer ditampilkan dengan jelas.
-   Berikan instruksi agar Klien mentransfer nominal yang tepat.
-   Jangan mengandalkan warna saja untuk membedakan kode unik.

## 6.4 Unggah bukti pembayaran

-   Tampilkan format file yang diterima dan batas ukuran.
-   Sediakan pratinjau file sebelum dikirim.
-   Tampilkan nama file dan status unggah.
-   Beri pesan jika unggah gagal.
-   Setelah berhasil, tampilkan status "Menunggu verifikasi Admin",
    bukan "Lunas".
-   Sediakan akses untuk melihat bukti yang telah dikirim.
-   Hindari menampilkan bukti transfer kepada role yang tidak berhak.

## 6.5 Status pembayaran

Gunakan status yang eksplisit, misalnya:

-   Belum dibayar.
-   Bukti pembayaran diunggah.
-   Menunggu verifikasi.
-   Terverifikasi/lunas.
-   Ditolak/perlu perbaikan.
-   Kedaluwarsa atau dibatalkan jika status tersebut didukung proses
    bisnis.

Tampilkan tanggal dan keterangan perubahan status. Label final harus
mengikuti enum/status dari backend.

## 6.6 Invoice dan nota

-   Sediakan ringkasan tagihan yang dapat dibaca di layar.
-   Sediakan pratinjau dokumen.
-   Tampilkan nomor invoice unik, tanggal, identitas transaksi, rincian
    item, dan total.
-   Nota lunas hanya tersedia setelah pembayaran dinyatakan
    terverifikasi.
-   Pastikan tampilan dokumen dapat dicetak atau disimpan jika fitur
    ekspor tersedia.

## 6.7 Pembatalan dan refund

-   Tampilkan kebijakan pembatalan sebelum Klien mengajukan pesanan atau
    pembayaran.
-   Pada halaman pengajuan refund, tampilkan ringkasan transaksi dan
    alasan pembatalan.
-   Jelaskan bahwa nilai refund ditentukan berdasarkan kebijakan yang
    berlaku dan keputusan proses.
-   Tampilkan status pengajuan: diajukan, ditinjau, disetujui, ditolak,
    diproses, atau selesai sesuai status backend.
-   Tampilkan nominal estimasi hanya jika backend menyediakan
    perhitungan yang sah.
-   Simpan tampilan snapshot kebijakan yang berlaku pada transaksi.
-   Konfirmasi ulang sebelum Klien mengirim pengajuan atau Admin
    menyetujui refund.

Frontend tidak boleh menghitung sendiri refund final berdasarkan aturan
yang tersebar di komponen UI.

## 6.8 Halaman pencairan penyedia

Tampilkan:

-   Nama penyedia.
-   Acara/pesanan terkait.
-   Nilai hak penyedia.
-   Rekening tujuan yang telah diverifikasi, dengan penyamaran sebagian
    nomor rekening.
-   Status pencairan.
-   Tanggal proses.
-   Referensi transfer.
-   Bukti transfer jika tersedia.
-   Riwayat perubahan status.

Status dapat berupa menunggu penyelesaian acara, menunggu proses Admin,
diproses, berhasil, gagal, atau perlu pemeriksaan, sesuai status yang
ditetapkan backend.

## 6.9 Dashboard Admin untuk keuangan

Sediakan filter berdasarkan:

-   Status pembayaran.
-   Status refund.
-   Status payout.
-   Tanggal transaksi/acara.
-   Nomor invoice atau referensi.
-   Penyedia.

Pada detail transaksi, tampilkan histori aktivitas dan dokumen
pendukung. Aksi validasi, refund, dan payout harus memiliki konfirmasi
serta ringkasan nilai.

## 6.10 Kriteria penerimaan

-   Semua rincian tagihan dapat dibaca dan dibedakan.
-   Kode unik mudah dibaca dan disalin.
-   Status "lunas" hanya muncul setelah backend mengonfirmasi
    pembayaran.
-   Nota lunas tidak tersedia sebelum pembayaran terverifikasi.
-   Refund tidak menampilkan nominal final yang dihitung secara lokal.
-   Halaman pencairan menampilkan hak penyedia, status, tanggal, dan
    bukti jika tersedia.
-   Nomor rekening disamarkan pada tampilan yang tidak memerlukan
    informasi penuh.
-   Aksi finansial memiliki konfirmasi dan jejak aktivitas.

------------------------------------------------------------------------

# TAHAP 7 --- Koordinasi Acara dan Evaluasi

> **Pembaruan backend (2026-11-17):** BE-7 menghubungkan rundown dan ulasan.
> Klien/Vendor mengelola rundown; Venue/Admin read-only. Ulasan tersedia setelah
> acara selesai dan invoice lunas; penyedia hanya melihat agregat anonim. Checklist
> terpisah dan editor layout tetap di luar lingkup implementasi ini.

## 7.1 Tujuan

Meningkatkan pengalaman koordinasi acara dengan tetap mengembangkan
fitur secara bertahap, bukan langsung membangun sistem enterprise
seperti Cvent.

## 7.2 Rundown acara

-   Tampilkan daftar kegiatan berdasarkan waktu.
-   Tampilkan nama kegiatan, waktu mulai, waktu selesai, penanggung
    jawab, dan catatan.
-   Bedakan item yang belum dimulai, sedang berjalan, selesai, atau
    tertunda jika status tersebut didukung.
-   Sediakan tampilan ringkas untuk mobile.
-   Tampilkan siapa yang terakhir mengubah item dan kapan perubahan
    dilakukan jika histori tersedia.

## 7.3 Layout venue

-   Tampilkan layout yang tersimpan.
-   Sediakan pratinjau gambar atau dokumen.
-   Tampilkan versi atau waktu pembaruan.
-   Batasi tindakan edit sesuai hak akses.
-   Jika editor visual belum tersedia, gunakan unggah/preview dokumen
    sebagai pendekatan awal.

## 7.4 Checklist operasional

Checklist dapat dikembangkan setelah proses rundown dan layout stabil.

Contoh kelompok checklist:

-   Persiapan sebelum acara.
-   Konfirmasi penyedia.
-   Kesiapan venue.
-   Peralatan.
-   Pelaksanaan acara.
-   Penutupan dan dokumentasi.

Setiap item dapat memiliki penanggung jawab, tenggat, dan status jika
backend mendukungnya. Jangan menambahkan status atau penanggung jawab
yang tidak disimpan sistem.

## 7.5 Penyelesaian acara

-   Tampilkan ringkasan pesanan yang harus dipenuhi.
-   Tampilkan rundown dan checklist.
-   Admin dapat memeriksa kelengkapan sebelum menandai acara selesai.
-   Konfirmasi aksi selesai dengan ringkasan dampaknya terhadap
    pencairan dan ulasan.
-   Catat waktu dan aktor yang melakukan perubahan status.

## 7.6 Ulasan terverifikasi

-   Form ulasan hanya tersedia setelah acara selesai.
-   Klien dapat memberi rating pada target yang memang terkait dengan
    acara.
-   Tampilkan panduan penulisan ulasan yang sopan dan relevan.
-   Tampilkan ulasan pada profil penyedia setelah lolos aturan
    publikasi/moderasi yang berlaku.
-   Jika vendor dapat merespons ulasan, pisahkan respons vendor dari
    ulasan Klien.
-   Jangan izinkan ulasan untuk transaksi yang tidak selesai atau tidak
    terkait.

## 7.7 Kriteria penerimaan

-   Rundown mudah dibaca pada desktop dan mobile.
-   Layout dapat dipratinjau.
-   Checklist tidak menampilkan data palsu.
-   Penyelesaian acara memerlukan konfirmasi.
-   Form ulasan hanya muncul ketika syarat acara selesai terpenuhi.

------------------------------------------------------------------------

# TAHAP 8 --- Fitur Lanjutan dan Optimasi

## 8.1 Tujuan

Menambahkan kemampuan lanjutan setelah fondasi UI/UX, katalog,
ketersediaan, dan transaksi stabil.

## 8.2 Inbox internal

-   Menyediakan percakapan terkait acara/pesanan.
-   Menghubungkan pesan dengan konteks transaksi.
-   Menampilkan pengirim, waktu, dan status pesan.
-   Memberi notifikasi pesan baru.
-   Tidak menampilkan nomor telepon atau kontak pribadi penyedia kepada
    Klien.
-   Menyediakan pelaporan atau moderasi jika diperlukan.

## 8.3 Sinkronisasi kalender

-   Menampilkan kalender internal sebagai sumber tampilan utama.
-   Menyediakan status sinkronisasi.
-   Menangani konflik tanggal dan perubahan kalender.
-   Menampilkan waktu sinkronisasi terakhir.
-   Jangan menganggap kalender eksternal berhasil tersinkron sebelum
    layanan mengonfirmasi.

## 8.4 Penggantian vendor terstruktur

-   Admin melihat alasan penyedia tidak dapat memenuhi pesanan.
-   Sistem menampilkan kandidat pengganti yang sesuai kebutuhan dan
    ketersediaan.
-   Admin membandingkan harga, layanan, dan jadwal.
-   Klien menerima informasi dan menyetujui perubahan jika diwajibkan
    proses bisnis.
-   Tampilkan dampak perubahan harga atau jadwal sebelum persetujuan.
-   Simpan histori penyedia awal dan pengganti.

## 8.5 Statistik performa penyedia

Jika data sudah memadai, tampilkan:

-   Jumlah permintaan/pesanan.
-   Jumlah pesanan selesai.
-   Tingkat pembatalan.
-   Rating.
-   Ringkasan pendapatan/hak pencairan.
-   Periode laporan.

Jelaskan periode dan definisi metrik. Jangan menampilkan statistik dari
data yang belum lengkap seolah-olah angka final.

## 8.6 Otomasi dan integrasi

Tahap ini dapat mencakup tampilan untuk payment gateway, payout
otomatis, dan notifikasi lanjutan. Integrasi teknis harus dikerjakan dan
diuji di backend; frontend hanya menampilkan status dari hasil
integrasi.

## 8.7 Kriteria penerimaan

-   Inbox menjaga model komunikasi perantara Pesta Kita.
-   Kalender menampilkan status sinkronisasi yang sebenarnya.
-   Penggantian vendor memiliki persetujuan dan histori.
-   Statistik memiliki definisi dan rentang waktu.
-   Integrasi tidak menampilkan status berhasil sebelum mendapat
    konfirmasi sistem.

------------------------------------------------------------------------

# 5. Aturan UX Lintas Seluruh Tahap

## 5.1 Progressive disclosure

Tampilkan informasi terpenting lebih dahulu. Detail tambahan seperti
histori panjang, rincian dokumen, atau aturan lanjutan dapat ditempatkan
pada panel detail, accordion, atau halaman khusus. Jangan menyembunyikan
biaya, kebijakan pembatalan, atau konsekuensi transaksi.

## 5.2 Bahasa antarmuka

-   Gunakan bahasa Indonesia yang konsisten.
-   Hindari istilah teknis yang tidak diperlukan pengguna.
-   Gunakan istilah status yang sama di dashboard, tabel, detail, dan
    notifikasi.
-   Berikan instruksi tindakan yang spesifik, bukan pesan umum seperti
    "Terjadi kesalahan" tanpa penjelasan.

## 5.3 Notifikasi dan feedback

-   Beri feedback setelah aksi.
-   Bedakan notifikasi informasi, sukses, peringatan, dan gagal.
-   Pesan sukses hanya muncul setelah proses terkonfirmasi.
-   Pesan penting tidak boleh hilang terlalu cepat.
-   Untuk kesalahan, jelaskan langkah pemulihan yang aman.

## 5.4 Konsistensi status

Satu status transaksi harus memiliki label, warna, dan makna yang sama
di seluruh halaman. Daftar status final harus disepakati bersama backend
agar frontend tidak menciptakan variasi status sendiri.

## 5.5 Privasi dan keamanan tampilan

-   Sembunyikan data rekening yang tidak perlu ditampilkan.
-   Batasi pratinjau dokumen berdasarkan role.
-   Jangan menampilkan dokumen verifikasi penyedia kepada Klien.
-   Hindari menyimpan token atau data sensitif di komponen UI.
-   Jangan menganggap validasi frontend cukup untuk melindungi data.

------------------------------------------------------------------------

# 6. Strategi Pengujian

## 6.1 Pengujian visual dan responsif

Lakukan pada setiap tahap:

-   Desktop 1440 px.
-   Tablet 768 px.
-   Mobile 375 px.
-   Orientasi portrait dan landscape jika relevan.
-   Browser utama yang menjadi target proyek.

Periksa overflow horizontal, teks terpotong, tombol terlalu kecil, modal
keluar viewport, tabel, dan urutan konten.

## 6.2 Pengujian aksesibilitas

-   Navigasi keyboard penuh.
-   Fokus terlihat.
-   Focus trap modal.
-   Label form.
-   Pesan error.
-   Kontras warna.
-   Struktur heading.
-   Alt text.
-   Penggunaan screen reader pada alur utama.
-   Target sentuh.

## 6.3 Pengujian alur per role

  -----------------------------------------------------------------------
  Role                                Alur yang harus diuji
  ----------------------------------- -----------------------------------
  Klien                               Membuat acara, melihat katalog,
                                      mengajukan pesanan, melihat
                                      tagihan, mengunggah bukti, melihat
                                      status, mengajukan
                                      pembatalan/refund, melihat rundown,
                                      memberi ulasan setelah acara
                                      selesai

  Vendor                              Mengelola profil dan layanan,
                                      melihat pesanan, mengajukan
                                      keberatan, mengajukan perubahan
                                      harga, melihat pencairan

  Pemilik Venue                       Mengelola profil venue, kapasitas,
                                      aturan, kalender, melihat booking,
                                      melihat pencairan

  Admin                               Memverifikasi penyedia, mengelola
                                      pesanan, menyetujui
                                      keberatan/perubahan harga,
                                      memvalidasi pembayaran, memproses
                                      refund, menandai acara selesai,
                                      memproses payout
  -----------------------------------------------------------------------

Pengujian harus menggunakan akun dan data uji yang sesuai. Jangan
menggunakan data keuangan nyata untuk pengujian.

## 6.4 Pengujian transaksi

-   Total tagihan sesuai hasil backend.
-   Komisi dan biaya terpisah.
-   Kode unik tampil benar.
-   Bukti pembayaran tidak otomatis menjadikan transaksi lunas.
-   Nota lunas hanya muncul setelah verifikasi.
-   Refund mengikuti hasil backend.
-   Payout sesuai data hak penyedia.
-   Aksi berulang tidak menimbulkan transaksi ganda.

## 6.5 Regression testing

Setelah setiap tahap:

-   Uji halaman yang diubah.
-   Uji halaman lain yang menggunakan komponen bersama.
-   Pastikan navigasi dan hak akses role tidak rusak.
-   Pastikan data yang sudah tersimpan tetap dapat ditampilkan.
-   Bandingkan screenshot dengan baseline Tahap 0.

------------------------------------------------------------------------

# 7. Definisi Selesai (Definition of Done)

Sebuah halaman atau komponen dianggap selesai jika:

-   [ ] Sesuai kebutuhan role dan proses bisnis.
-   [ ] Menggunakan design token dan komponen bersama.
-   [ ] Responsif pada 1440, 768, dan 375 px.
-   [ ] Dapat digunakan dengan keyboard.
-   [ ] Fokus terlihat dan modal dikelola dengan benar.
-   [ ] Kontras teks memenuhi standar yang ditetapkan.
-   [ ] Memiliki loading, empty, error, dan success state yang relevan.
-   [ ] Validasi form dan pesan error jelas.
-   [ ] Status dan nominal berasal dari sumber data yang benar.
-   [ ] Tidak menampilkan data sensitif kepada role yang tidak berhak.
-   [ ] Tidak menampilkan keberhasilan sebelum server mengonfirmasi.
-   [ ] Sudah melalui pengujian regresi.
-   [ ] Dokumentasi komponen atau perubahan diperbarui.

------------------------------------------------------------------------

# 8. Prioritas Implementasi yang Disarankan

Jika pengerjaan dilakukan bertahap dengan sumber daya terbatas, urutan
praktisnya:

1.  **Pertama:** Tahap 0 dan Tahap 1 --- audit kondisi aktual dan
    perbaiki blocker aksesibilitas.
2.  **Kedua:** Tahap 2 --- bangun design tokens dan komponen bersama
    agar perbaikan selanjutnya tidak berulang.
3.  **Ketiga:** Tahap 3 --- selesaikan responsivitas dan state dasar.
4.  **Keempat:** Tahap 4 --- rapikan portal berdasarkan role.
5.  **Kelima:** Tahap 5 --- perbaiki katalog, profil penyedia, venue,
    dan kalender.
6.  **Keenam:** Tahap 6 --- sempurnakan seluruh tampilan transaksi dan
    keuangan.
7.  **Ketujuh:** Tahap 7 --- tingkatkan koordinasi acara dan evaluasi.
8.  **Terakhir:** Tahap 8 --- tambahkan fitur lanjutan setelah fondasi
    stabil.

**Catatan urutan:** pekerjaan Tahap 6 dapat mulai dirancang lebih awal,
tetapi rilisnya harus menunggu kontrak data dan status backend
tervalidasi. Prioritas keselamatan transaksi lebih tinggi daripada
kecepatan menambah tampilan.

------------------------------------------------------------------------

# 9. Indikator Keberhasilan

  -----------------------------------------------------------------------
  Area                                Indikator
  ----------------------------------- -----------------------------------
  Aksesibilitas                       Tidak ada blocker keyboard/modal;
                                      kontras teks memenuhi target

  Responsivitas                       Halaman utama berfungsi pada 1440,
                                      768, dan 375 px

  Konsistensi                         Komponen status, form, modal,
                                      tabel, dan navigasi memiliki pola
                                      yang sama

  Kejelasan transaksi                 Rincian biaya, kode unik, total,
                                      dan status pembayaran mudah
                                      dipahami

  Pencairan                           Hak penyedia, rekening, status,
                                      tanggal, dan bukti dapat ditelusuri

  Pengalaman role                     Pengguna melihat menu dan tindakan
                                      yang sesuai kewenangannya

  Stabilitas                          Perubahan komponen bersama tidak
                                      merusak halaman lain

  Kejujuran status                    UI tidak menyatakan berhasil
                                      sebelum backend mengonfirmasi
  -----------------------------------------------------------------------

Target kuantitatif tambahan seperti skor Lighthouse, persentase
keberhasilan task, atau waktu penyelesaian tugas dapat ditetapkan
setelah baseline pengujian awal tersedia.

------------------------------------------------------------------------

# 10. Hal yang Tidak Termasuk Prioritas Awal

Fitur berikut tidak perlu dikerjakan sebelum fondasi utama stabil:

-   Paket listing berbayar, ranking berbayar, dan Pay Per Click.
-   Instant Book untuk seluruh vendor/venue tanpa kalender yang dapat
    dipercaya.
-   CRM enterprise dan event app kompleks.
-   Analitik ROI tingkat lanjut.
-   Sistem cicilan kompleks sebelum pembayaran penuh dan refund berjalan
    konsisten.
-   Pembukuan penuh sebelum ledger transaksi, refund, dan payout dasar
    stabil.

------------------------------------------------------------------------

# 11. Penutup

Pembaruan frontend Pesta Kita sebaiknya dilakukan dengan pendekatan
bertahap. Tahap awal berfokus pada aksesibilitas, konsistensi komponen,
dan responsivitas. Setelah itu, penyempurnaan dilakukan pada portal
empat role, katalog, ketersediaan, serta tampilan transaksi dan
keuangan. Fitur koordinasi dan fitur lanjutan baru ditambahkan setelah
proses inti stabil.

Keberhasilan pembaruan bukan diukur dari banyaknya halaman atau fitur
baru, melainkan dari kemudahan pengguna menyelesaikan tugas, kejelasan
status, konsistensi data, aksesibilitas, dan keamanan proses transaksi.

Dokumen ini perlu disesuaikan kembali dengan struktur kode, framework,
API, dan kondisi backend Pesta Kita yang benar-benar digunakan pada saat
implementasi.
