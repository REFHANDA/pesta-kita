# Pesta Kita — Admin UI Preview

Paket ini berisi 30 halaman antarmuka Admin Pesta Kita berbasis HTML dan CSS.

## Pembaruan pemeriksaan
- Menyamakan lebar konten, jarak antarkomponen, padding kartu, ukuran kartu statistik, dan perilaku tabel.
- Memperbaiki tata letak pada breakpoint desktop, tablet, dan ponsel.
- Banner dan keterangan sementara sudah dihapus.
- Merapikan istilah pada area bukti transfer, katalog, keputusan, dan daftar aktivitas.
- Memeriksa seluruh 30 halaman: tautan internal HTML tidak putus, tidak ada ID duplikat, dan setiap halaman memiliki elemen main.

## Menjalankan
Ekstrak ZIP, lalu buka `admin-preview/index.html` melalui browser.

## Catatan
Ini adalah prototype statis HTML/CSS. Data yang tampil masih berupa data demonstrasi untuk mengisi tampilan; pencarian, penyimpanan form, perubahan status, notifikasi, dan transaksi belum terhubung ke backend Laravel/database.


## Tahap 1 — Perbaikan aksesibilitas (v11)
- Nama aksesibel tautan profil pada seluruh halaman.
- Checkbox pemicu drawer dikeluarkan dari urutan fokus keyboard.
- Drawer tertutup disembunyikan dari interaksi pointer dan accessibility tree pada breakpoint mobile.
- Label aksesibel ditambahkan pada seluruh dropdown dan input tanggal yang belum memiliki nama.
- Seluruh header kolom tabel diberi scope="col"; tabel tanpa caption/nama diberi caption tersembunyi.
- Sidebar diberi nama landmark dan empat lompatan heading diperbaiki.

## Tahap 2 — Perbaikan responsivitas (v12)
- Pencarian global ditampilkan kembali pada layar ponsel dan ditempatkan pada baris kedua topbar agar tidak bertumpuk dengan tombol navigasi/profil.
- Area tab status dan pagination dibuat dapat digulir horizontal dengan ukuran target sentuh yang tetap memadai.
- Area tabel diberi landmark region, nama aksesibel, fokus keyboard, serta scrollbar yang lebih mudah terlihat pada perangkat sentuh.
- Tabel dua kolom pada rincian tagihan dibuat menyesuaikan lebar layar sehingga nominal komponen dan total tagihan tetap terlihat tanpa menggulir tabel ke samping.
- Penyesuaian breakpoint mencakup layar 560 px dan 360 px.

## Validasi Tahap 2
- 30/30 halaman HTML berhasil diparse.
- 20 area tabel memiliki nama aksesibel, landmark region, dan dapat difokuskan dengan keyboard.
- Pencarian global tersedia pada 30/30 halaman.
- Kurung kurawal CSS seimbang.
- Pemeriksaan visual lintas browser/perangkat nyata belum dilakukan; lakukan uji lanjutan sebelum rilis produksi.
