# Analisis Perbandingan Fitur dan Rekomendasi Pengembangan Pesta Kita

> **Tanggal analisis:** 13 November 2026  
> **Ruang lingkup:** Pesta Kita EMS untuk acara ulang tahun privat, dengan fokus pada portal klien, vendor, pemilik venue, dan admin.  
> **Referensi pembanding:** The Bash, Tagvenue, Bridestory, Peerspace, dan Cvent.  
> **Status dokumen:** Analisis kebutuhan dan rekomendasi pengembangan, bukan spesifikasi implementasi final.

---

## Ringkasan Eksekutif

Pesta Kita mempunyai karakter yang berbeda dari marketplace vendor pada umumnya. Platform ini dirancang sebagai **managed marketplace**: perusahaan Pesta Kita menjadi perantara utama, Admin mengendalikan persetujuan pesanan, pembayaran diterima ke rekening perusahaan, dan penyedia jasa dibayar setelah acara selesai. Model tersebut memberikan kendali operasional dan pengalaman layanan yang terpusat, tetapi meningkatkan beban kerja Admin dan menuntut sistem audit serta otomasi yang kuat.

Hasil perbandingan menunjukkan:

1. **The Bash** paling relevan untuk memperbaiki perjalanan pemesanan jasa acara, permintaan penawaran, perlindungan pemesanan, ulasan terverifikasi, dan penanganan vendor yang membatalkan.
2. **Tagvenue** paling relevan untuk memperkuat booking venue, konfirmasi ketersediaan, kebijakan pembatalan yang terlihat sebelum transaksi, pembayaran, refund, serta pencairan.
3. **Bridestory** paling relevan untuk memperkaya katalog vendor, profil bisnis, pengelolaan prospek, paket keanggotaan/promosi, inbox, statistik, dan pengelolaan transaksi vendor.
4. **Peerspace** paling relevan untuk pengelolaan venue, kalender ketersediaan, instant booking yang bersyarat, biaya kebersihan, aturan ruang, serta pencairan otomatis setelah acara.
5. **Cvent** paling relevan sebagai referensi arsitektur sistem pengelolaan acara menyeluruh: venue sourcing, RFP, registrasi, kapasitas, check-in, engagement, diagram/layout, pelaporan, dan pengukuran hasil acara.

**Rekomendasi utama:** pertahankan model managed marketplace Pesta Kita untuk MVP, tetapi kurangi pekerjaan administratif yang berulang melalui workflow dan aturan otomatis. Bangun fondasi transaksi, ketersediaan, pembayaran, refund, dan pencairan yang dapat diaudit sebelum menambah fitur kompleks. Ambil pola fitur dari referensi, bukan menyalin seluruh model bisnis mereka.

---

## 1. Dasar Analisis dan Kondisi Pesta Kita Saat Ini

### 1.1 Sumber dan batasan

Analisis ini membedakan tiga jenis bukti:

| Jenis informasi | Sumber | Cara penggunaan |
|---|---|---|
| Rancangan dan aturan bisnis Pesta Kita | Dokumen *Perancangan Event Management System (EMS) Berbasis Web untuk Acara Ulang Tahun* | Menjadi acuan proses yang memang direncanakan |
| Kondisi antarmuka portal penyedia/admin | Laporan Audit UI/UX Penyedia Pesta Kita, 10 November 2026 | Menunjukkan halaman dan masalah yang terlihat pada prototipe HTML/CSS |
| Praktik platform referensi | Situs resmi dan pusat bantuan masing-masing platform | Menjadi pembanding fitur dan aturan yang dipublikasikan |

**Catatan penting tentang status implementasi:** dokumen perancangan menjelaskan fitur yang direncanakan, sedangkan audit memeriksa prototipe statis 24 halaman HTML/CSS. Karena audit menyatakan validasi backend dan perilaku dinamis tidak dapat dinilai dari prototipe, dokumen ini tidak menyimpulkan bahwa fitur transaksi, autentikasi, notifikasi, atau pencairan telah berjalan secara produksi.

### 1.2 Model bisnis Pesta Kita

Pesta Kita dirancang sebagai perusahaan pengelola platform yang berada di tengah transaksi:

```text
Klien  <---->  PESTA KITA / ADMIN  <---->  Vendor dan Pemilik Venue
```

Karakteristik model:

- Klien dan penyedia jasa tidak berhubungan langsung.
- Nama usaha penyedia dapat ditampilkan, tetapi kontak penyedia tidak ditampilkan kepada klien.
- Admin menjadi pihak yang menyetujui, menolak, atau mengalihkan pesanan.
- Klien membayar ke rekening perusahaan melalui satu tagihan gabungan per acara.
- Penyedia memantau pesanan dan dapat mengajukan keberatan, tetapi tidak menerima/menolak pesanan secara langsung.
- Hak penyedia dicairkan setelah acara ditandai selesai oleh Admin.
- Kebersihan venue diperlakukan sebagai biaya operasional internal, bukan aktor atau modul tenaga kebersihan tersendiri.

### 1.3 Fitur Pesta Kita yang direncanakan

| Area | Fitur dalam rancangan |
|---|---|
| Akun dan akses | Registrasi, login, logout, empat role: klien, vendor, pemilik venue, Admin; verifikasi penyedia oleh Admin |
| Perencanaan acara | Konsep acara, tanggal, tema, jumlah tamu, budget |
| Rekomendasi | Paket Hemat, Seimbang, Premium berdasarkan budget, tanggal, kapasitas, dan tema |
| Katalog | Katalog vendor, venue, layanan, dan peralatan teknis |
| Pemesanan | Pemesanan vendor, venue, dan peralatan; pemeriksaan tanggal dan stok |
| Persetujuan | Admin menyetujui, menolak, atau mengalihkan pesanan |
| Keberatan | Penyedia mengajukan keberatan dengan alasan; Admin memutuskan |
| Harga | Penyedia mengajukan perubahan harga; harga baru aktif setelah disetujui Admin |
| Tagihan | Satu tagihan gabungan per acara, berisi harga penyedia, komisi, biaya kebersihan, dan kode unik |
| Pembayaran | Transfer manual ke rekening perusahaan, unggah bukti, validasi Admin |
| Dokumen transaksi | Tagihan dan nota lunas PDF dengan nomor unik |
| Pembatalan/refund | Pembatalan otomatis sebelum pembayaran tervalidasi; setelah itu pengajuan kepada Admin dengan refund berjenjang |
| Koordinasi acara | Layout venue dan rundown kolaboratif klien-vendor |
| Penyelesaian | Admin menandai acara selesai dan mencatat pencairan penyedia |
| Evaluasi | Rating venue, masing-masing vendor, Admin, serta feedback sistem |
| Pengawasan | Dashboard Admin, notifikasi, SLA, dan log aktivitas |

### 1.4 Kondisi antarmuka portal penyedia dan Admin

Audit tanggal 10 November 2026 mencakup **24 file HTML/CSS** dan seluruhnya tercatat telah diaudit. Halaman yang tercantum antara lain dashboard/indeks, admin penyedia, admin pesanan venue, formulir venue, formulir item, keberatan, layanan, peralatan, pengajuan harga, pesanan, detail pesanan, rundown, profil, pencairan, detail pencairan, registrasi penyedia, verifikasi penyedia, serta halaman data venue.

Audit memberikan skor murni 669/1000 dan skor akhir 699/1000 karena aturan cap akibat blocker aksesibilitas. Temuan yang paling penting:

| Prioritas | Temuan audit | Dampak |
|---|---|---|
| Blocker | Modal tidak mengunci fokus keyboard (focus trap) | Pengguna keyboard dapat berpindah ke konten di belakang modal |
| Blocker | Target sentuh ikon aksi sekitar 18 px, di bawah batas minimum 24 px | Aksi sulit digunakan pada perangkat sentuh |
| Critical | Teks muted `#94a3b8` pada latar putih memiliki kontras 2,6:1 | Informasi sekunder sulit dibaca dan tidak memenuhi WCAG AA 4,5:1 |
| Major | Kode unik transfer 3 digit tidak diberi penekanan visual | Risiko salah membaca nominal saat verifikasi pembayaran |
| Major | Tabel pencairan dan admin penyedia terpotong pada lebar 375 px | Pengguna seluler kesulitan membaca dan mengelola data |
| Major/Minor | Empty state dan error state dinamis belum memadai | Pengguna tidak mendapat arahan ketika data kosong atau proses gagal |
| Perlu peningkatan | Belum ada live countdown SLA pada halaman review Admin | Risiko keterlambatan review tidak terlihat secara proaktif |

Audit juga mencatat kekuatan berupa navigasi sidebar yang konsisten, badge status semantik, tabel dengan header jelas, formulir katalog/pendaftaran yang cukup lengkap, grid desktop yang fleksibel, dan rendering CSS yang ringan.

**Interpretasi:** dari bukti yang tersedia, Pesta Kita telah memiliki cakupan rancangan dan prototipe antarmuka yang cukup luas pada sisi penyedia/Admin. Namun, kualitas interaksi, aksesibilitas, responsivitas, dan kepastian backend masih perlu dibuktikan dan diperbaiki.

---

## 2. Perbandingan Lima Website Referensi

### 2.1 The Bash — prioritas 1: alur pemesanan jasa acara

**Fokus yang relevan**

- Pencarian vendor berdasarkan jenis layanan dan lokasi.
- Perbandingan foto, video/audio, deskripsi, dan ulasan.
- Permintaan penawaran gratis (*Request Free Quote*).
- Vendor mengirimkan penawaran, lalu klien memilih dan menekan *Book Now*.
- Pembayaran online melalui kartu untuk vendor yang mengaktifkan pembayaran online.
- Deposit dan pelunasan dapat digunakan sesuai penawaran vendor.
- Booking Guarantee, dukungan Account Specialist, dan ulasan yang hanya dapat diberikan oleh klien yang memesan melalui platform.

**Aturan bisnis yang dipublikasikan**

- Booking harus dikonfirmasi secara resmi melalui akun The Bash agar perlindungan booking dan kesempatan ulasan berlaku.
- Pembayaran online hanya dilindungi oleh jaminan platform; pembayaran di luar sistem tidak tercakup dalam Booking Guarantee.
- Vendor yang membatalkan wajib memberi tahu platform sesuai tenggat kebijakan. Dalam kebijakan Event Protection yang dipublikasikan, pemberitahuan umumnya paling lambat 14 hari sebelum acara; untuk booking yang dibuat kurang dari 14 hari sebelum acara, pemberitahuan dilakukan dalam 24 jam setelah booking.
- Jika vendor membatalkan sesuai tenggat, biaya yang dibayar klien dikembalikan; jika terlambat, vendor dapat kehilangan fee dan menerima konsekuensi akun.
- The Bash berupaya membantu mencari vendor pengganti, tetapi kebijakan menyebut upaya yang wajar, bukan jaminan bahwa pengganti pasti tersedia.
- Untuk pembatalan klien, pengembalian pembayaran bergantung pada apakah pembayaran bersifat refundable atau non-refundable dan ketentuan vendor.

**Kelebihan pendekatan**

- Alur dari pencarian hingga pemesanan mudah dipahami.
- Klien dapat membandingkan bukti portofolio dan ulasan sebelum menghubungi vendor.
- Request Quote cocok untuk jasa acara yang harga akhirnya bergantung pada kebutuhan.
- Perlindungan booking dan dukungan manusia memberi jalur penyelesaian ketika vendor bermasalah.
- Review terverifikasi mengurangi risiko ulasan dari transaksi yang tidak terjadi.

**Kekurangan/risiko pendekatan**

- Klien dapat berkomunikasi dan bertransaksi lebih langsung dengan vendor, sehingga kontrol layanan tidak seketat managed marketplace.
- Pembayaran di luar platform mengurangi perlindungan transaksi.
- Harga dan syarat dapat berbeda antarvendor sehingga perbandingan total biaya memerlukan perhatian klien.
- Kebijakan refund tidak selalu berupa satu formula seragam; sebagian bergantung pada ketentuan vendor dan status pembayaran.

**Pelajaran untuk Pesta Kita**

Adopsi katalog berbasis portofolio, permintaan penawaran, penjelasan detail layanan, ulasan terverifikasi, pusat bantuan, dan mekanisme vendor pengganti. Tidak perlu meniru komunikasi langsung atau pembayaran langsung kepada vendor karena bertentangan dengan model bisnis Pesta Kita.

Sumber:
- https://www.thebash.com/help
- https://www.thebash.com/event-protection
- https://www.thebash.com/event-services

### 2.2 Tagvenue — prioritas 2: booking, pembayaran, refund, dan venue

**Fokus yang relevan**

- Pencarian dan pemesanan venue.
- Enquiry/booking request dan custom offer.
- Informasi harga, aturan venue, kapasitas, dan kebijakan pembatalan.
- Dua model pembayaran: Online Payment melalui Stripe atau Invoice Payment yang dibayarkan langsung ke venue.
- Pengelolaan booking, dokumen, perubahan detail, pembayaran, refund, dan payout.
- Opsi kebijakan pembatalan venue yang berbeda.

**Aturan bisnis yang dipublikasikan**

- Pada Online Payment, klien mengajukan booking request dan kartu dapat dikenai authorization hold. Kartu ditagih penuh setelah Venue Manager menerima booking.
- Pada panduan Online Payments terbaru, booking request memiliki waktu respons hingga dua hari kerja untuk diterima atau ditolak.
- Dana online diproses melalui Stripe; payout diteruskan ke rekening venue terverifikasi setelah acara, umumnya sekitar 3–7 hari kerja.
- Pada Invoice Payment, transaksi klien dilakukan langsung dengan venue; Tagvenue menagih komisi kepada venue berdasarkan dokumentasi final spend.
- Venue memilih kebijakan pembatalan yang ditampilkan pada listing dan checkout. Contoh kebijakan: Very Flexible, Flexible, Standard 30 Day, dan Standard 60 Day.
- Kebijakan yang berlaku untuk booking dikunci berdasarkan kebijakan pada saat booking request/konfirmasi; perubahan kebijakan berikutnya tidak mengubah booking lama.
- Grace Period memberikan pengembalian penuh untuk pembatalan dalam 24 jam setelah konfirmasi, dengan batas tidak lebih dekat dari 48 jam sebelum acara.
- Pada online payment, komisi yang disebut dalam panduan Tagvenue adalah 15% dari total pembayaran untuk booking pada skema tersebut; angka dan ketentuan dapat berubah menurut metode dan kebijakan yang berlaku.
- Tagvenue tidak menyediakan opsi cicilan bawaan pada panduan pembayaran tertentu; penawaran sebagian dapat dibuat melalui custom offer, bukan fitur cicilan terjadwal penuh.

**Kelebihan pendekatan**

- Kebijakan refund terlihat sebelum klien menyelesaikan booking.
- Venue memiliki pilihan kebijakan sesuai karakter bisnisnya.
- Terdapat jejak proses booking dan penawaran yang jelas.
- Pembayaran online dan payout dapat diotomatisasi melalui penyedia pembayaran.
- Perubahan detail booking dapat dilakukan melalui penawaran yang harus diterima klien.
- Pemisahan Online Payment dan Invoice Payment memberi fleksibilitas model operasional.

**Kekurangan/risiko pendekatan**

- Dua jalur pembayaran meningkatkan kompleksitas dukungan, rekonsiliasi, dan pemahaman pengguna.
- Kebijakan pembatalan yang berbeda-beda dapat membingungkan bila tidak ditampilkan dengan jelas.
- Ketergantungan pada Stripe/Stripe Connect memerlukan onboarding, verifikasi, dan dukungan negara yang sesuai.
- Model komisi dan aliran dana berbeda antar metode pembayaran; pelaporan keuangan harus mampu membedakannya.

**Pelajaran untuk Pesta Kita**

Prioritaskan halaman detail venue yang lengkap, kalender ketersediaan, kebijakan pembatalan yang transparan, histori perubahan booking, dan dokumen booking. Untuk MVP, Pesta Kita dapat mempertahankan satu jalur pembayaran melalui rekening perusahaan; integrasi payment gateway/marketplace payout menjadi tahap berikutnya.

Sumber:
- https://support.tagvenue.com/hc/en-gb/articles/39155719573917-Online-Payments-Guide-Everything-you-need-to-know-to-use-the-Online-Payment-Method
- https://support.tagvenue.com/hc/en-gb/articles/360018097078-What-are-the-cancellation-policies-I-can-choose-for-my-Venue
- https://support.tagvenue.com/hc/en-gb/articles/360020170998-How-do-payments-work-on-Tagvenue
- https://support.tagvenue.com/hc/en-gb/articles/360019839578-Does-Tagvenue-allow-me-to-submit-a-deposit-or-instalment-payment

### 2.3 Bridestory — prioritas 3: katalog dan pengelolaan vendor

**Fokus yang relevan**

- Katalog vendor dengan kategori jasa yang luas.
- Profil bisnis dan informasi portofolio.
- Vendor dashboard, inbox, statistik, aplikasi Pro, dan pengelolaan review.
- Model keanggotaan Trial, Silver, dan Gold.
- Premium Listing, search marketing, promosi, serta Pay Per Click.
- Bridestory Store dan Bridestory Deals untuk penawaran produk/jasa.
- Bridestory Pay dan pencairan dana vendor.

**Aturan bisnis yang dipublikasikan**

- Vendor mendaftar dan menyetujui syarat serta ketentuan vendor.
- Vendor dapat mengirim Custom Quotation sesuai kesepakatan dengan pembeli; pembeli melakukan transaksi melalui Bridestory Pay.
- Bridestory meneruskan dana kepada vendor setelah dikurangi biaya jasa transaksi dan service fee yang berlaku.
- Biaya transaksi berbeda berdasarkan status keanggotaan, kategori produk, dan ketentuan layanan.
- Penarikan dana dilakukan dari dashboard vendor; ketentuan vendor yang dipublikasikan menyebut proses 1 x 24 jam hari kerja setelah permintaan pencairan, dengan persyaratan dokumen legal dan verifikasi OTP untuk penambahan rekening atau pencairan.
- Bridestory dapat melakukan pemeriksaan, penundaan, pembekuan, atau pembatalan pencairan jika terdapat dugaan pelanggaran atau kecurangan.
- Dalam Bridestory Deals, vendor diwajibkan memberi diskon minimum tertentu dan transaksi hanya menggunakan Bridestory Pay.
- Ketentuan pembatalan/refund pada transaksi Bridestory Pay menempatkan sejumlah tanggung jawab transaksi pada vendor; biaya transaksi/service fee yang telah dipotong dapat tidak dikembalikan.
- Profil premium dan penempatan promosi dapat dipengaruhi oleh paket atau kredit yang dimiliki vendor.

**Kelebihan pendekatan**

- Katalog vendor memiliki struktur kategori yang kaya.
- Vendor memiliki alat untuk mengelola profil, prospek, pesan, review, dan statistik.
- Model keanggotaan dan promosi memberi sumber pendapatan tambahan platform.
- Vendor dapat membuat penawaran khusus, bukan hanya memasang harga tetap.
- Dashboard pencairan dan verifikasi rekening memberi kontrol finansial yang lebih jelas.

**Kekurangan/risiko pendekatan**

- Paket berbayar, kredit prospek, promosi, dan biaya transaksi dapat menambah kompleksitas bagi vendor.
- Peringkat berbayar atau promosi harus dibedakan secara transparan dari kualitas layanan.
- Beban pengelolaan transaksi dan refund dapat berada pada vendor sesuai ketentuan produk.
- Katalog yang luas memerlukan moderasi, standardisasi kategori, dan validasi profil agar kualitas data terjaga.

**Pelajaran untuk Pesta Kita**

Adopsi struktur kategori vendor, profil bisnis yang kaya, portofolio, paket layanan, custom quotation, statistik performa, pengelolaan inbox internal, review, dan verifikasi rekening. Hindari monetisasi listing berbayar pada tahap awal sebelum jumlah vendor dan permintaan klien stabil.

Sumber:
- https://helpdesk.bridestory.com/hc/id/articles/360047138893-Syarat-dan-Ketentuan-Vendor-Bridestory
- https://business.bridestory.com/id/home/pricing
- https://helpdesk.bridestory.com/hc/id/articles/360046476454-Syarat-dan-Ketentuan-Bridestory-Store-bagi-Vendor-Pernikahan

### 2.4 Peerspace — prioritas 4: pengelolaan venue dan pencairan

**Fokus yang relevan**

- Listing ruang/venue dengan foto, harga, aturan, dan ketersediaan.
- Kalender dan sinkronisasi kalender eksternal.
- Unified inbox untuk komunikasi booking.
- Booking request dan Instant Book.
- Aturan ruang dan biaya tambahan.
- Pembayaran di muka melalui platform.
- Payout kepada host setelah booking selesai.
- Kebijakan pembatalan yang berbeda berdasarkan tipe venue/host.

**Aturan bisnis yang dipublikasikan**

- Host dapat mengaktifkan Instant Book sehingga booking dapat langsung dikonfirmasi tanpa menunggu persetujuan host, dengan ketentuan waktu booking minimal 24 jam sebelum mulai.
- Instant Book menuntut kalender ketersediaan yang selalu diperbarui dan harga yang sederhana.
- Untuk booking request biasa, host tetap dapat meninjau permintaan.
- Pembayaran dilakukan di muka melalui platform.
- Payout otomatis ditransfer dalam sekitar 3–5 hari kerja setelah acara berakhir; host diminta memberi toleransi hingga 7 hari.
- Host dapat memilih kebijakan pembatalan seperti Very Flexible, Flexible, Standard 30 Day, atau Standard 90 Day.
- Refund klien dan bagian payout host ditentukan oleh kebijakan serta waktu pembatalan.
- Biaya kebersihan dikembalikan jika klien membatalkan dan tidak menggunakan ruang.
- Pembatalan oleh host dapat mengakibatkan refund penuh kepada klien dan penalti pada akun host.
- Sistem mendukung kalender, inbox, dan alat pengelolaan listing.

**Kelebihan pendekatan**

- Pengelolaan kalender menjadi pusat operasional venue.
- Instant Book dapat mengurangi waktu tunggu untuk venue yang siap menerima booking otomatis.
- Payout otomatis mengurangi pekerjaan administratif host dan platform.
- Kebijakan pembatalan dan biaya kebersihan dinyatakan secara eksplisit.
- Host memiliki alat untuk mengelola listing dan booking dari satu tempat.

**Kekurangan/risiko pendekatan**

- Instant Book berisiko jika kalender tidak sinkron atau kapasitas tidak diperbarui.
- Kebijakan pembatalan berbasis pilihan host dapat menghasilkan pengalaman berbeda antarvenue.
- Otomatisasi payout memerlukan sistem pembayaran, identitas, dan rekening yang terverifikasi.
- Model yang mengandalkan host mengelola listing secara mandiri memerlukan kontrol kualitas konten dan kepatuhan aturan ruang.

**Pelajaran untuk Pesta Kita**

Adopsi kalender ketersediaan, sinkronisasi kalender pada fase lanjutan, aturan venue, biaya tambahan yang jelas, dan status payout yang transparan. Instant Book sebaiknya hanya diberikan kepada venue terverifikasi dengan kalender yang andal dan kebijakan yang telah disetujui Admin.

Sumber:
- https://www.peerspace.com/host
- https://support.peerspace.com/en/articles/10119406-what-is-instant-book
- https://support.peerspace.com/en/articles/10119166-what-is-the-cancellation-and-refund-policy
- https://support.peerspace.com/en/articles/10119435-how-do-i-get-paid-for-a-booking

### 2.5 Cvent — prioritas 5: arsitektur pengelolaan acara menyeluruh

**Fokus yang relevan**

- Venue sourcing dan RFP.
- Registrasi acara dan pengelolaan kapasitas.
- Website acara dan komunikasi peserta.
- Agenda, sesi, pembicara, exhibitor, dan waitlist.
- Check-in dan pelaksanaan onsite.
- Engagement peserta dan aplikasi acara.
- Pengumpulan data, feedback, dashboard, integrasi CRM, dan pengukuran hasil.
- Diagram venue dan pengaturan layout.

**Aturan bisnis/operasional yang terlihat dari produk**

- Cvent mengorganisasikan fitur berdasarkan siklus sebelum acara, saat acara, dan setelah acara.
- Venue sourcing menggunakan direktori venue dan alur RFP untuk meminta informasi/penawaran.
- Registrasi dapat disesuaikan dan mengelola kapasitas acara/sesi serta waitlist.
- Pengelolaan onsite mencakup check-in dan pelacakan kehadiran.
- Data peserta dan aktivitas dapat digunakan untuk pelaporan, analitik, serta pengukuran hasil.
- Cvent merupakan platform enterprise dengan modul dan integrasi yang lebih luas daripada kebutuhan awal Pesta Kita; detail harga, kontrak, dan konfigurasi dapat berbeda menurut produk dan paket.

**Kelebihan pendekatan**

- Arsitektur mencakup siklus hidup acara secara menyeluruh.
- Data pra-acara, saat acara, dan pasca-acara dapat dihubungkan.
- Kapasitas, registrasi, check-in, engagement, dan pelaporan menjadi satu rangkaian.
- RFP dan venue sourcing membantu proses pemilihan tempat secara terstruktur.
- Layout/diagram venue mendukung koordinasi operasional.

**Kekurangan/risiko pendekatan**

- Cakupan fitur dan kompleksitasnya terlalu besar jika langsung diterapkan pada MVP ulang tahun privat.
- Implementasi memerlukan biaya, waktu, integrasi, dan proses operasional lebih tinggi.
- Fitur enterprise dapat menambah beban belajar bagi klien/vendor kecil.
- Tidak semua modul relevan untuk acara privat berskala kecil.

**Pelajaran untuk Pesta Kita**

Gunakan Cvent sebagai referensi arsitektur jangka panjang: satu data acara sebagai pusat, modul sebelum-saat-sesudah acara, pengelolaan layout, checklist operasional, laporan, dan evaluasi. Jangan meniru seluruh fitur enterprise pada tahap awal.

Sumber:
- https://www.cvent.com/en/platform
- https://www.cvent.com/en/platform/planners
- https://www.cvent.com/en/event-management-software/features
- https://www.cvent.com/en/products

---

## 3. Perbandingan Pendekatan Bisnis

### 3.1 Perbandingan model transaksi

| Aspek | Pesta Kita | The Bash | Tagvenue | Bridestory | Peerspace |
|---|---|---|---|---|---|
| Peran platform | Perantara terkelola; Admin pusat | Marketplace jasa dan booking protection | Marketplace venue; online atau invoice payment | Marketplace vendor dan layanan transaksi | Marketplace venue/space |
| Persetujuan | Admin menyetujui setiap pesanan | Klien memilih vendor dari quote dan booking | Venue Manager menerima/menolak request atau custom offer | Vendor mengirim quotation/penawaran | Host menerima request atau mengaktifkan Instant Book |
| Kontak | Kontak penyedia disembunyikan | Klien dapat menghubungi vendor | Komunikasi melalui enquiry/messaging | Profil, inbox, dan kontak mengikuti paket/fitur | Unified inbox untuk host dan guest |
| Pembayaran | Transfer manual ke rekening perusahaan | Pembayaran online tersedia untuk vendor yang mengaktifkannya; pembayaran luar sistem dimungkinkan | Stripe online payment atau invoice langsung ke venue | Bridestory Pay; potongan biaya dari transaksi vendor | Pembayaran di muka melalui platform |
| Komisi/biaya | 10% ditambahkan ke tagihan klien; penyedia menerima harga penuh | Bergantung pada layanan dan ketentuan pembayaran | Online Payment menggunakan skema komisi; Invoice Payment memiliki invoice komisi terpisah | Biaya transaksi/service fee dapat dipotong dari dana vendor | Biaya layanan platform dan payout host sesuai kebijakan |
| Pencairan | Admin mencatat transfer setelah acara selesai | Sesuai mekanisme pembayaran vendor dan kebijakan platform | Payout melalui Stripe setelah acara | Vendor mengajukan pencairan dari dashboard | Payout otomatis setelah acara |
| Pembatalan | Refund berjenjang yang diatur sistem dan diproses Admin | Dipengaruhi refundable/non-refundable dan kebijakan Event Protection | Pilihan kebijakan venue, terlihat sebelum booking | Banyak ketentuan transaksi berada pada kesepakatan vendor/pembeli | Kebijakan pilihan host dengan pembagian refund dan payout |
| Pengganti vendor | Admin mencari manual; otomatisasi belum ada | Platform membantu mencari pengganti | Tidak menjadi jaminan otomatis dalam sumber yang ditinjau | Tidak ditemukan jaminan pengganti umum pada sumber yang ditinjau | Dukungan platform/host; tidak diasumsikan sebagai pengganti otomatis |
| Ulasan | Evaluasi berjenjang setelah acara | Review dari booking terverifikasi | Review sesuai fitur platform | Review request dan respons vendor | Review/fitur marketplace sesuai kebijakan platform |

### 3.2 Perbandingan model komisi

Pesta Kita menggunakan **Model A**: komisi ditambahkan pada tagihan klien, bukan dipotong dari harga penyedia.

Contoh untuk harga penyedia Rp1.000.000:

| Model | Klien membayar (belum biaya lain) | Penyedia menerima | Pendapatan komisi platform |
|---|---:|---:|---:|
| Pesta Kita Model A | Rp1.100.000 | Rp1.000.000 | Rp100.000 |
| Komisi dipotong dari penyedia | Rp1.000.000 | Rp900.000 | Rp100.000 |
| Komisi 15% dari total pembayaran (contoh skema online Tagvenue) | Mengikuti harga booking dan skema platform | Dikurangi komisi | Mengikuti skema platform |

**Penilaian:** Model A cocok dengan janji Pesta Kita kepada penyedia bahwa harga yang diajukan diterima utuh. Konsekuensinya, harga akhir klien lebih tinggi dan komisi harus ditampilkan secara transparan sebelum klien mengajukan acara.

### 3.3 Perbandingan refund dan pembatalan

Pesta Kita menetapkan refund berdasarkan sisa hari menuju acara:

| Sisa hari | Refund Pesta Kita |
|---|---:|
| 30 hari atau lebih | 90% |
| 14–29 hari | 70% |
| 7–13 hari | 50% |
| 3–6 hari | 25% |
| Kurang dari 3 hari | 0% |

Platform referensi menunjukkan pendekatan yang lebih beragam:

- Tagvenue dan Peerspace memberi venue/host pilihan beberapa kebijakan pembatalan yang ditampilkan sebelum transaksi.
- The Bash membedakan pembayaran refundable dan non-refundable serta memiliki ketentuan khusus ketika vendor membatalkan.
- Bridestory mengatur sejumlah tanggung jawab refund melalui syarat transaksi dan kesepakatan vendor/pembeli.

**Rekomendasi:** jangan menghapus tabel refund Pesta Kita secara langsung. Jadikan tabel tersebut kebijakan default platform, tetapi rancang agar pada masa depan dapat dikonfigurasi berdasarkan kategori layanan atau jenis penyedia. Kebijakan yang berlaku harus disimpan sebagai snapshot pada saat booking agar perubahan pengaturan tidak mengubah transaksi lama.

### 3.4 Perbandingan tata kelola

| Dimensi | Pesta Kita | Praktik yang dapat dipelajari |
|---|---|---|
| Kendali kualitas | Terpusat pada Admin | Pertahankan verifikasi dan moderasi; tambahkan indikator kinerja penyedia |
| Kecepatan booking | Bergantung pada review Admin | SLA, pengingat, dan kelak auto-approval terbatas untuk penyedia tepercaya |
| Transparansi harga | Tagihan gabungan dengan komisi terpisah | Tampilkan rincian harga akhir sebelum pengajuan |
| Ketersediaan | Penahanan tanggal/stok dengan transaksi database | Tambahkan kalender yang mudah diperbarui dan sinkronisasi lanjutan |
| Keamanan transaksi | Bukti transfer dan validasi Admin | Integrasi payment gateway setelah alur manual stabil |
| Perlindungan klien | Refund berjenjang dan kontrol Admin | Tambahkan kebijakan vendor gagal hadir, pengganti, dan bukti layanan |
| Hak penyedia | Harga penyedia dibayar penuh setelah acara | Tambahkan estimasi payout, histori, dan status yang dapat dipantau |
| Audit | Log keputusan penting Admin | Perluas ke audit perubahan data, pembayaran, refund, rekening, dan akses |

---

## 4. Kelebihan dan Kekurangan Masing-Masing Pendekatan

### 4.1 Pesta Kita

**Kelebihan**

- Model bisnis dan tanggung jawab perusahaan jelas.
- Klien mendapat satu titik layanan untuk banyak kebutuhan acara.
- Satu tagihan gabungan memudahkan pemahaman biaya keseluruhan acara.
- Admin dapat menjaga kualitas, menyaring penyedia, dan mengelola konflik.
- Komisi tidak mengurangi harga yang diterima penyedia.
- Alur refund, keberatan, harga, dan pencairan sudah dirancang dengan status yang eksplisit.
- Ada pemikiran mengenai pencegahan bentrok jadwal dan stok melalui transaksi database.

**Kekurangan/risiko**

- Admin berpotensi menjadi bottleneck karena persetujuan, pembayaran, keberatan, refund, dan pencairan terpusat.
- Transfer manual menambah pekerjaan rekonsiliasi dan risiko kesalahan.
- Penyedia tidak dapat langsung menerima/menolak pesanan; masa keberatan belum tentu cukup untuk mencegah konflik jadwal.
- Pencarian pengganti vendor masih manual.
- Refund seluruh acara, bukan per item, dapat kurang fleksibel ketika hanya satu layanan yang bermasalah.
- Pembayaran penuh 100% di awal dapat menjadi hambatan untuk acara bernilai besar.
- Prototipe UI/UX masih memiliki blocker aksesibilitas dan masalah responsivitas.
- Kebenaran perilaku backend belum dapat disimpulkan dari audit prototipe statis.

### 4.2 The Bash

**Kelebihan:** alur quote-to-book sederhana, portofolio dan ulasan, booking protection, dukungan penyelesaian masalah, serta mekanisme pencarian vendor pengganti.

**Kekurangan:** pengalaman dan perlindungan dapat berbeda bila pembayaran dilakukan di luar platform; interaksi langsung mengurangi kontrol penuh platform; syarat pembayaran dan refund dapat bergantung pada vendor.

### 4.3 Tagvenue

**Kelebihan:** proses venue booking terstruktur, kebijakan refund terlihat, custom offer, pembayaran online dan invoice, payout, serta dokumen booking.

**Kekurangan:** banyak variasi kebijakan dan metode pembayaran menambah kompleksitas; ketergantungan pada penyedia pembayaran eksternal; aturan komisi berbeda antarjalur transaksi.

### 4.4 Bridestory

**Kelebihan:** katalog vendor kaya, alat promosi, inbox, statistik, review, quotation, dan dashboard pencairan.

**Kekurangan:** paket, kredit, promosi, serta biaya transaksi berpotensi membingungkan vendor; kualitas listing perlu dimoderasi; kebijakan transaksi dan refund dapat menempatkan beban lebih besar kepada vendor.

### 4.5 Peerspace

**Kelebihan:** pengelolaan venue dan kalender kuat, Instant Book, inbox, pembayaran dan payout otomatis, serta kebijakan refund terstruktur.

**Kekurangan:** Instant Book bergantung pada akurasi kalender; aturan host yang bervariasi perlu ditampilkan dengan jelas; otomasi memerlukan integrasi pembayaran dan verifikasi yang matang.

### 4.6 Cvent

**Kelebihan:** siklus acara end-to-end, venue sourcing, RFP, kapasitas, registrasi, check-in, engagement, diagram, analitik, dan pelaporan.

**Kekurangan:** terlalu kompleks untuk kebutuhan MVP Pesta Kita; biaya dan upaya implementasi lebih tinggi; sebagian besar fitur enterprise tidak dibutuhkan untuk ulang tahun privat.

---

## 5. Fitur yang Perlu Dipertahankan

Fitur berikut merupakan pembeda bisnis Pesta Kita dan sebaiknya dipertahankan.

| Fitur/keputusan | Alasan dipertahankan | Catatan implementasi |
|---|---|---|
| Empat role utama | Hak akses jelas dan sesuai proses | Terapkan role-based access control di backend |
| Admin sebagai pengendali transaksi | Menjaga standar layanan managed marketplace | Hindari semua keputusan manual tanpa SLA |
| Kontak penyedia tidak ditampilkan | Menjaga model perantara perusahaan | Sediakan komunikasi internal/inbox agar koordinasi tetap berjalan |
| Verifikasi vendor dan venue | Mengurangi risiko listing palsu/tidak layak | Tambahkan status dokumen dan tanggal verifikasi |
| Katalog vendor, venue, peralatan | Inti pencarian layanan | Perkaya foto, portofolio, kapasitas, lokasi, harga, dan kebijakan |
| Acara dan budget | Menjadi pusat seluruh kebutuhan klien | Satu acara menjadi entitas induk transaksi |
| Rekomendasi Hemat/Seimbang/Premium | Pembeda pengalaman Pesta Kita | Gunakan harga akhir, kapasitas, tanggal, dan rating |
| Satu tagihan gabungan per acara | Memudahkan klien membayar banyak layanan | Simpan snapshot item, komisi, dan biaya |
| Komisi 10% ditambahkan ke tagihan klien | Penyedia menerima harga penuh | Tampilkan rincian komisi secara eksplisit |
| Harga pesanan dikunci saat disetujui | Melindungi kesepakatan yang sudah dibuat | Simpan harga dan komisi sebagai snapshot pada order |
| Masa keberatan penyedia | Memberi ruang penyedia menyampaikan kendala | Pertahankan SLA 24 jam dan eskalasi |
| Penahanan tanggal dan stok | Mencegah double booking | Gunakan transaksi database dan locking |
| Refund berjenjang | Memberi kepastian kebijakan | Simpan snapshot kebijakan per booking |
| Run down dan layout | Mendukung koordinasi operasional | Hak edit mengikuti role dan pesanan yang terkonfirmasi |
| Pencairan setelah acara selesai | Menjaga kontrol penyelesaian layanan | Tambahkan status, bukti, rekening terverifikasi, dan histori |
| Evaluasi berjenjang | Menjadi dasar kualitas vendor dan sistem | Review hanya setelah acara selesai dan terverifikasi |
| Log aktivitas Admin | Akuntabilitas keputusan | Log harus immutable bagi pengguna biasa |

---

## 6. Fitur yang Perlu Diperbaiki

Prioritas berikut mempertimbangkan risiko transaksi, ketergantungan antarfitur, dan hasil audit UI/UX.

### P0 — Wajib sebelum transaksi digunakan secara nyata

1. **Perbaikan aksesibilitas modal dan keyboard**
   - Implementasikan focus trap, fokus awal, tombol Escape bila sesuai, dan pengembalian fokus ke pemicu modal.
   - Tambahkan `role="dialog"`, `aria-modal="true"`, label dialog, dan urutan fokus yang benar.
2. **Ukuran target sentuh**
   - Minimal memenuhi WCAG 2.2 AA Target Size (Minimum) 24 × 24 CSS px dengan memperhatikan pengecualian standar; target 44 × 44 px lebih nyaman.
3. **Kontras warna**
   - Ganti teks muted yang gagal kontras; audit seluruh kombinasi teks, badge, border, dan tombol.
4. **Validasi server-side dan otorisasi**
   - Semua aksi penting harus diperiksa di backend, bukan hanya menyembunyikan tombol di frontend.
5. **Ketersediaan venue dan peralatan**
   - Pastikan cek ketersediaan dan pembuatan pesanan berjalan dalam satu transaksi database dengan penguncian yang tepat.
6. **Audit finansial**
   - Simpan nilai tagihan, komisi, kode unik, rekening tujuan, bukti, status validasi, refund, dan pencairan secara konsisten.

### P1 — Prioritas tinggi untuk pengalaman pengguna dan operasi

1. **Responsivitas tabel**
   - Bungkus tabel dalam container responsif, sediakan tampilan kartu pada layar kecil jika lebih sesuai, dan uji 375 px.
2. **Empty, loading, success, dan error state**
   - Semua daftar dan form harus menjelaskan kondisi kosong, proses berjalan, berhasil, gagal, dan cara pemulihan.
3. **Penekanan kode unik**
   - Tampilkan tiga digit kode unik dalam komponen visual yang mudah dibedakan dan dapat disalin.
4. **Konfirmasi tindakan berisiko**
   - Aksi hapus, batalkan, tolak, refund, dan tandai selesai harus memiliki konfirmasi dan ringkasan dampak.
5. **SLA Admin**
   - Tampilkan countdown review, umur antrean, status terlambat, dan filter prioritas.
6. **Status pencairan**
   - Pisahkan status belum diproses, menunggu transfer, berhasil, gagal, dan perlu pemeriksaan.
7. **Histori perubahan**
   - Tampilkan siapa mengubah harga, rekening, status pesanan, atau keputusan refund, beserta waktunya.

### P2 — Penyempurnaan setelah alur inti stabil

1. Sinkronisasi kalender eksternal.
2. Inbox/komunikasi internal antara klien, Admin, dan penyedia tanpa membuka kontak pribadi.
3. Custom quotation untuk kebutuhan acara yang tidak tercakup harga paket.
4. Penggantian vendor dengan alur rekomendasi dan persetujuan klien.
5. Kebijakan pembatalan yang dapat berbeda berdasarkan kategori layanan, bila model bisnis mengizinkan.
6. Statistik vendor: jumlah permintaan, tingkat persetujuan, tingkat pembatalan, rating, dan pendapatan.
7. Pengujian usability dengan pengguna nyata, keyboard, dan screen reader.

---

## 7. Fitur Baru yang Layak Ditambahkan

Fitur berikut dipilih berdasarkan manfaat dan kesesuaian dengan Pesta Kita. Tidak semua harus masuk MVP.

| Fitur baru | Prioritas | Referensi utama | Manfaat | Ketergantungan/risiko |
|---|---|---|---|---|
| Portofolio foto/video dan paket layanan vendor | Tinggi | The Bash, Bridestory | Membantu klien membandingkan penyedia sebelum memesan | Moderasi media dan penyimpanan |
| Request Quote / Custom Quotation | Tinggi | The Bash, Bridestory, Tagvenue | Mendukung layanan yang harga akhirnya bergantung kebutuhan | Workflow revisi dan persetujuan harga |
| Kalender ketersediaan venue/vendor | Tinggi | Peerspace, Tagvenue | Mengurangi bentrok tanggal dan mempercepat review | Model slot, zona waktu, sinkronisasi |
| Kebijakan pembatalan ditampilkan sebelum pengajuan | Tinggi | Tagvenue, Peerspace | Meningkatkan transparansi dan mengurangi sengketa | Snapshot aturan pada transaksi |
| Pengganti vendor terstruktur | Tinggi | The Bash | Mengurangi dampak vendor berhalangan | Katalog pengganti, persetujuan klien, perubahan harga |
| Inbox internal | Tinggi | Bridestory, Peerspace | Koordinasi tanpa membuka kontak langsung | Moderasi, retensi pesan, notifikasi |
| Dashboard performa vendor | Menengah | Bridestory | Membantu vendor memperbaiki layanan dan Admin melakukan evaluasi | Data transaksi dan rating yang konsisten |
| Payout ledger dan estimasi pencairan | Tinggi | Tagvenue, Peerspace, Bridestory | Meningkatkan transparansi hak penyedia | Rekonsiliasi dan status pembayaran |
| Payment gateway | Menengah, setelah MVP stabil | The Bash, Tagvenue, Bridestory, Peerspace | Mengurangi validasi manual dan mempercepat konfirmasi | Biaya, KYC, integrasi, settlement, refund |
| Deposit/DP dan pelunasan | Menengah | The Bash; sebagian fleksibilitas Tagvenue melalui penawaran | Mengurangi beban pembayaran penuh di awal | Risiko piutang, jadwal jatuh tempo, refund parsial |
| Add-on dan biaya tambahan pasca-acara | Menengah | Tagvenue | Mencatat overtime, kerusakan, atau layanan tambahan | Persetujuan klien dan batas waktu pengajuan |
| Checklist operasional acara | Menengah | Cvent | Mengurangi kelalaian sebelum dan saat acara | Tanggung jawab tiap role |
| Check-in/kehadiran dan dokumentasi acara | Rendah untuk MVP | Cvent | Bukti pelaksanaan dan data acara | Privasi peserta dan perangkat lapangan |
| Waitlist venue/peralatan | Rendah | Cvent (konsep kapasitas/waitlist) | Mengelola permintaan ketika slot penuh | Perlu aturan prioritas dan pelepasan slot |
| Layout editor yang lebih visual | Menengah | Cvent | Memudahkan penataan ruang dan koordinasi | Kompleksitas UI dan penyimpanan objek |
| Template acara dan rundown | Menengah | Cvent | Mempercepat pembuatan acara berulang | Pengelolaan versi template |
| Rating terverifikasi dan respons vendor | Tinggi | The Bash, Bridestory | Meningkatkan kepercayaan dan akuntabilitas | Hanya untuk transaksi selesai; moderasi |

### 7.1 Fitur baru yang sebaiknya tidak diprioritaskan dahulu

- Paket listing berbayar, ranking berbayar, atau Pay Per Click sebelum marketplace memiliki volume transaksi yang memadai.
- Instant Book untuk semua penyedia tanpa data ketersediaan yang dapat dipercaya.
- Modul enterprise seperti CRM marketing kompleks, event app, matchmaking, dan analitik ROI tingkat lanjut.
- Pembukuan akuntansi penuh sebelum ledger transaksi, refund, dan payout dasar stabil.
- Pembayaran cicilan yang kompleks sebelum sistem mampu menangani satu pembayaran penuh dan refund secara akurat.

---

## 8. Rekomendasi Akhir

### 8.1 Rekomendasi frontend

**Arah:** bangun frontend berbasis komponen dan design system yang konsisten untuk empat role, dengan pengalaman responsif dan aksesibel.

Rekomendasi:

1. Pisahkan area aplikasi berdasarkan role: Klien, Vendor, Pemilik Venue, dan Admin.
2. Gunakan design tokens untuk warna, tipografi, spacing, radius, elevation, status, dan breakpoint.
3. Buat komponen bersama:
   - Status badge.
   - Data table/responsive table.
   - Form field dan validasi.
   - Modal/dialog.
   - Empty/loading/error/success state.
   - Upload bukti dan preview dokumen.
   - Timeline status transaksi.
   - Countdown SLA.
   - Ringkasan tagihan dan payout.
4. Terapkan WCAG 2.2 AA: kontras, navigasi keyboard, fokus terlihat, label form, alt text, struktur heading, dan target sentuh.
5. Pada halaman pembayaran, pisahkan subtotal penyedia, komisi, biaya kebersihan, kode unik, dan total transfer. Kode unik harus mudah dibaca dan disalin.
6. Pada halaman pencairan, tampilkan nilai hak penyedia, rekening tujuan yang telah diverifikasi, status, tanggal proses, dan bukti transfer.
7. Gunakan progressive disclosure: tampilkan informasi penting terlebih dahulu dan detail lanjutan ketika diperlukan.
8. Uji tampilan minimal pada 1440 px, 768 px, dan 375 px, serta uji keyboard dan screen reader.

**Prioritas frontend:** perbaiki blocker audit terlebih dahulu sebelum menambah banyak modul baru.

### 8.2 Rekomendasi backend

**Arah:** gunakan backend modular dengan workflow status yang eksplisit, otorisasi ketat, transaksi database, dan audit trail.

Modul yang disarankan:

1. Authentication dan Role-Based Access Control.
2. Verifikasi vendor/venue dan manajemen dokumen.
3. Master data kategori, vendor, venue, layanan, peralatan, dan harga.
4. Event/Acara dan budget.
5. Availability dan reservation/penahanan slot.
6. Order management dan approval Admin.
7. Objection/keberatan dan price change request.
8. Invoice, invoice item, payment, dan receipt.
9. Cancellation dan refund.
10. Rundown, layout, dan checklist operasional.
11. Completion dan payout.
12. Review, rating, dan feedback.
13. Notification dan scheduled jobs.
14. Activity audit log dan reporting.

**Aturan teknis penting:**

- Terapkan validasi dan otorisasi pada setiap endpoint.
- Gunakan database transaction dan row-level locking atau mekanisme locking yang setara ketika memeriksa lalu menahan slot venue/peralatan.
- Terapkan idempotency pada proses pembayaran, refund, dan pencairan agar permintaan ulang tidak menggandakan transaksi.
- Simpan snapshot harga, komisi, biaya, rekening tujuan, kebijakan refund, dan rincian item pada saat transaksi.
- Gunakan state machine untuk acara, pesanan, tagihan, pembayaran, keberatan, refund, dan payout. Tolak perpindahan status yang tidak sah.
- Scheduled job menangani kedaluwarsa tagihan dan pengingat SLA; job harus aman dijalankan ulang.
- Catat aktor, aksi, objek, waktu, nilai sebelum/sesudah, alasan, dan correlation/reference ID pada audit log.
- Jangan menyimpan data kartu pembayaran sendiri jika menggunakan payment gateway; gunakan tokenisasi/hosted checkout dari penyedia pembayaran.
- Gunakan penyimpanan file privat untuk bukti transfer, dokumen legal, dan bukti refund/payout; akses melalui otorisasi.
- Pisahkan notifikasi dari proses transaksi inti melalui queue agar kegagalan email/push tidak membatalkan transaksi.

**Catatan stack:** dokumen perubahan Pesta Kita menyebut rancangan scheduled job Laravel. Jika proyek memang menggunakan Laravel, pendekatan modular dapat diterapkan melalui service/action, policy, event/listener, queue, scheduler, dan migration. Jika stack aktual berbeda, prinsip arsitekturnya tetap berlaku dan implementasi disesuaikan.

### 8.3 Rekomendasi database

**Arah:** gunakan database relasional sebagai sumber kebenaran transaksi karena hubungan acara, pesanan, tagihan, pembayaran, refund, dan pencairan membutuhkan konsistensi.

Jika proyek menggunakan Laravel, MySQL atau PostgreSQL layak dipertimbangkan. Pilihan final harus mengikuti infrastruktur dan kemampuan tim; yang paling penting adalah transaksi ACID, constraint, index, dan locking yang benar.

Kelompok tabel yang disarankan:

| Kelompok | Tabel inti (contoh) | Catatan |
|---|---|---|
| Identitas | `users`, `roles`/role field, `provider_profiles`, `provider_documents` | Status verifikasi dan pemilik data |
| Katalog | `categories`, `vendors`, `venues`, `services`, `equipment`, `media` | Pisahkan profil penyedia dari item yang dijual |
| Harga | `price_change_requests`, `price_histories` | Riwayat harga dan persetujuan |
| Acara | `events`, `event_budgets`, `event_layouts`, `rundown_items`, `event_checklists` | Acara menjadi induk proses |
| Ketersediaan | `availability_rules`, `availability_blocks`, `reservations` | Mendukung kalender dan penahanan slot |
| Pesanan | `orders`, `order_items`, `order_status_histories`, `objections` | Hindari satu tabel order yang terlalu polimorfik tanpa constraint |
| Tagihan | `invoices`, `invoice_items`, `invoice_versions` | Snapshot rincian transaksi |
| Pembayaran | `payments`, `payment_proofs`, `company_bank_accounts`, `payment_reconciliations` | Simpan rekening tujuan dan status validasi |
| Pembatalan | `cancellations`, `refunds`, `refund_items` | Mendukung refund seluruh acara dan kelak parsial |
| Pencairan | `provider_payouts`, `payout_items`, `provider_bank_accounts` | Payout per penyedia/pesanan dan bukti |
| Evaluasi | `reviews`, `review_targets`, `review_responses` | Review hanya terkait acara selesai |
| Sistem | `notifications`, `activity_logs`, `system_settings`, `idempotency_keys` | Audit, konfigurasi, dan keamanan transaksi |

**Prinsip desain data:**

1. Gunakan foreign key untuk relasi yang jelas.
2. Hindari mengandalkan `item_id` polimorfik tanpa validasi tipe; pertimbangkan tabel item yang lebih eksplisit atau pola katalog terpadu.
3. Simpan uang dalam tipe `DECIMAL`, bukan floating point.
4. Simpan waktu dalam format konsisten dan tentukan timezone aplikasi.
5. Gunakan unique constraint untuk nomor invoice, kode referensi pembayaran, dan kombinasi slot yang harus unik.
6. Simpan snapshot nilai transaksi agar perubahan harga katalog tidak mengubah tagihan lama.
7. Gunakan soft delete hanya untuk data master yang memerlukannya; data finansial dan audit sebaiknya dipertahankan sesuai kebijakan retensi.
8. Indeks kolom pencarian yang sering dipakai: status, tanggal acara, provider, venue, invoice number, dan reference ID.
9. Pertimbangkan ledger transaksi yang tidak diubah secara langsung untuk mencatat debit/kredit, komisi, refund, kompensasi, dan payout.
10. Jangan menyimpan nomor rekening atau dokumen sensitif tanpa kontrol akses, enkripsi yang sesuai, dan kebijakan retensi.

### 8.4 Rekomendasi keseluruhan sistem

Arsitektur yang disarankan untuk tahap awal adalah **modular monolith**, bukan microservices. Model ini lebih sederhana untuk tim dan skala awal, tetapi batas modulnya jelas sehingga dapat dipisah ketika beban dan kebutuhan integrasi meningkat.

```text
Frontend Web (Klien / Vendor / Venue / Admin)
                  |
             API / Backend
                  |
   ---------------------------------
   | Identity & Access             |
   | Catalog & Provider            |
   | Event & Budget                 |
   | Availability & Booking        |
   | Billing & Payment             |
   | Cancellation & Refund         |
   | Rundown & Operations          |
   | Payout & Review               |
   | Notification & Audit          |
   ---------------------------------
                  |
       Relational Database
                  |
   File/Object Storage + Queue
                  |
  Payment Gateway (fase lanjutan)
  Email/WhatsApp Provider (sesuai kebutuhan)
  Calendar Integration (fase lanjutan)
```

**Urutan implementasi yang disarankan:**

| Tahap | Fokus | Keluaran/indikator selesai |
|---|---|---|
| 0 | Stabilitas UI/UX dan aksesibilitas | Blocker focus, target sentuh, kontras, tabel mobile, dan state dasar diperbaiki |
| 1 | Auth, role, verifikasi, master data | Empat role dan pengelolaan vendor/venue berjalan dengan otorisasi |
| 2 | Acara, katalog, ketersediaan, pesanan | Klien dapat membuat acara; slot tidak dapat dipesan ganda |
| 3 | Approval, keberatan, perubahan harga | Workflow Admin dan penyedia dapat ditelusuri |
| 4 | Tagihan, transfer, validasi, nota | Tagihan gabungan dan bukti pembayaran dapat diverifikasi dengan audit trail |
| 5 | Pembatalan dan refund | Perhitungan refund konsisten, kebijakan tersimpan sebagai snapshot |
| 6 | Rundown, layout, checklist | Koordinasi acara sesuai hak akses |
| 7 | Selesai, payout, evaluasi | Pencairan tercatat dan review hanya setelah acara selesai |
| 8 | Integrasi dan otomasi | Payment gateway, inbox, kalender, payout otomatis, dan notifikasi lanjutan |
| 9 | Analitik dan skala | Dashboard performa, laporan operasional, serta optimasi kapasitas |

### 8.5 Matriks keputusan akhir

| Area | Keputusan |
|---|---|
| Model bisnis | Pertahankan managed marketplace dengan Admin sebagai pengendali utama untuk MVP |
| Katalog | Tingkatkan mengikuti The Bash dan Bridestory |
| Booking jasa | Ambil pola Request Quote/Custom Quotation, tetapi approval final tetap di Admin |
| Booking venue | Ambil kalender, aturan ruang, kapasitas, dan kebijakan pembatalan Tagvenue/Peerspace |
| Pembayaran | Pertahankan transfer manual untuk MVP, tetapi bangun abstraksi payment agar gateway dapat ditambahkan |
| Komisi | Pertahankan 10% ditambahkan ke tagihan klien sesuai Model A |
| Refund | Pertahankan refund berjenjang sebagai kebijakan default; simpan snapshot dan dukung audit |
| Pencairan | Pertahankan pencairan setelah acara selesai; tambah ledger dan status payout transparan |
| Operasional acara | Pertahankan rundown/layout; tambah checklist dan template secara bertahap |
| Evaluasi | Pertahankan rating setelah acara selesai; gunakan ulasan terverifikasi |
| Arsitektur | Modular monolith dengan API, queue, scheduler, dan database relasional |
| Database | MySQL/PostgreSQL dengan transaksi, constraint, locking, dan audit trail |
| Prioritas kualitas | Aksesibilitas, keamanan, konsistensi finansial, dan ketersediaan lebih dahulu daripada fitur tambahan |

---

## 9. Kesimpulan

Pesta Kita tidak perlu berubah menjadi salinan The Bash, Tagvenue, Bridestory, Peerspace, atau Cvent. Nilai utama Pesta Kita justru terletak pada layanan acara yang dikelola secara terpusat, satu tagihan per acara, kendali kualitas oleh Admin, dan koordinasi beberapa penyedia dalam satu acara.

Kelima referensi memberikan pelajaran yang saling melengkapi:

- The Bash: discovery, quote, booking protection, pengganti vendor, dan review terverifikasi.
- Tagvenue: venue booking, custom offer, pembayaran, refund, dan payout.
- Bridestory: katalog, profil vendor, inbox, statistik, promosi, dan pencairan.
- Peerspace: kalender venue, instant booking bersyarat, aturan ruang, dan payout.
- Cvent: pengelolaan acara dari perencanaan hingga evaluasi dan analitik.

Arah terbaik adalah **mempertahankan aturan bisnis inti Pesta Kita sambil mengadopsi pola fitur referensi secara selektif**. Dalam jangka pendek, fokus utama bukan menambah sebanyak mungkin fitur, melainkan memastikan bahwa katalog, ketersediaan, persetujuan, tagihan, pembayaran, refund, dan pencairan berjalan konsisten, aman, responsif, serta dapat diaudit.

Setelah fondasi tersebut stabil, Pesta Kita dapat berkembang menuju platform pengelolaan acara yang lebih lengkap melalui inbox internal, kalender sinkron, pengganti vendor terstruktur, payment gateway, payout otomatis, checklist operasional, dan analitik performa.

---

## 10. Daftar Sumber

### Dokumen internal Pesta Kita

1. *Perancangan Event Management System (EMS) Berbasis Web untuk Acara Ulang Tahun*, 2026. Dokumen yang diberikan pada percakapan ini.
2. *Laporan Audit UI/UX Independen & Aksesibilitas WCAG 2.2 AA — Portal Penyedia & Admin Pesta Kita*, 10 November 2026.
3. *Rancangan Perubahan Pesta Kita (v3)*, 10 November 2026.

### Sumber resmi platform referensi

**The Bash**
- FAQ: https://www.thebash.com/help
- Event Protection Policy: https://www.thebash.com/event-protection
- Event Services: https://www.thebash.com/event-services

**Tagvenue**
- Online Payments Guide: https://support.tagvenue.com/hc/en-gb/articles/39155719573917-Online-Payments-Guide-Everything-you-need-to-know-to-use-the-Online-Payment-Method
- Cancellation policies for venues: https://support.tagvenue.com/hc/en-gb/articles/360018097078-What-are-the-cancellation-policies-I-can-choose-for-my-Venue
- How payments work: https://support.tagvenue.com/hc/en-gb/articles/360020170998-How-do-payments-work-on-Tagvenue
- Deposit or instalment payment: https://support.tagvenue.com/hc/en-gb/articles/360019839578-Does-Tagvenue-allow-me-to-submit-a-deposit-or-instalment-payment

**Bridestory**
- Syarat dan Ketentuan Vendor Bridestory: https://helpdesk.bridestory.com/hc/id/articles/360047138893-Syarat-dan-Ketentuan-Vendor-Bridestory
- Bridestory for Business — Pricing: https://business.bridestory.com/id/home/pricing
- Syarat dan Ketentuan Bridestory Store: https://helpdesk.bridestory.com/hc/id/articles/360046476454-Syarat-dan-Ketentuan-Bridestory-Store-bagi-Vendor-Pernikahan

**Peerspace**
- Host tools: https://www.peerspace.com/host
- Instant Book: https://support.peerspace.com/en/articles/10119406-what-is-instant-book
- Cancellation and refund policy: https://support.peerspace.com/en/articles/10119166-what-is-the-cancellation-and-refund-policy
- Host payout: https://support.peerspace.com/en/articles/10119435-how-do-i-get-paid-for-a-booking

**Cvent**
- Cvent Platform: https://www.cvent.com/en/platform
- Cvent for Planners: https://www.cvent.com/en/platform/planners
- Event Management Software Features: https://www.cvent.com/en/event-management-software/features
- Products: https://www.cvent.com/en/products

---

## Catatan Metodologi dan Validitas

- Aturan bisnis platform referensi dalam dokumen ini diringkas dari halaman resmi yang tersedia pada saat penelusuran. Ketentuan dapat berubah menurut negara, kategori, metode pembayaran, paket, dan tanggal transaksi.
- Angka komisi atau payout tidak boleh dianggap sebagai angka universal untuk seluruh layanan platform. Angka tersebut hanya dicantumkan ketika sumber resmi menyebutkannya untuk skema tertentu.
- Tidak ditemukannya suatu fitur pada sumber yang ditinjau tidak berarti platform pasti tidak memilikinya; hal tersebut hanya berarti fitur tersebut tidak dijadikan dasar perbandingan dalam analisis ini.
- Rekomendasi teknologi merupakan analisis teknis, bukan klaim bahwa stack tertentu sudah digunakan pada aplikasi berjalan.
- Sebelum implementasi, validasi kembali aturan hukum, pajak, perlindungan konsumen, privasi, pembayaran, dan refund yang berlaku di Indonesia.
