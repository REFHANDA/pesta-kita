# Design System Pesta Kita

## Dasar bersama

`public/assets/css/design-tokens.css` adalah sumber token bersama. Semua layout Admin, Klien, Vendor, Venue, serta layout publik/autentikasi memuatnya sebelum `admin.css`. Alias lama seperti `--brand`, `--ink`, `--content-max`, `--pad-card`, dan `--gap-section` dipertahankan agar stylesheet dan view yang sudah ada tidak perlu dimigrasikan sekaligus.

Nilai warna, font, jarak, radius, lapisan, transisi, dan bayangan mengikuti tampilan yang sudah ada. Padding kartu menjadi 16 px dan jarak antarbagian 20 px pada lebar sampai 760 px. Nilai badge sukses dan peringatan yang berbeda dari kelas status umum tetap dipisahkan supaya tampilan lama tidak berubah.

## Komponen yang sudah tersedia

Komponen bersama tetap berada di `admin.css`, yang sudah dipakai oleh semua layout portal:

- Tombol: `.btn` (utama), `.btn-o` (sekunder/outline), `.btn-bad` (bahaya outline), `.btn-danger` (bahaya dengan latar), `.btn-sm` (ukuran ringkas).
- Isian: `.input` untuk `input`, `select`, dan `textarea`; `.field` mengelompokkan label dan kontrol.
- Status: `.badge` dengan `.b-ok`, `.b-warn`, `.b-bad`, `.b-info`, `.b-mute`, atau `.b-brand`.
- Struktur/data: `.card`, `.card-h`, `.card-b`, `.tabs`, `.table-wrap`, dan `.pager`.
- Aksi/umpan balik: `.menu`, `.menu-list`, elemen native `<dialog>` dengan gaya `.dlg`, serta `.toast`.

Gunakan kelas yang sesuai sebelum menambah varian baru. Token `--pk-*` dapat digunakan untuk komponen baru; alias lama tetap dipakai selama stylesheet portal yang ada belum dimigrasikan.

### Contoh markup Blade yang sudah digunakan

Tombol utama dan outline; tautan navigasi tetap menggunakan `<a>`:

```blade
<a class="btn btn-o" href="{{ url('/admin/admin-venue-form') }}">Kelola data venue</a>
<button class="btn btn-bad" type="button">Tolak pengajuan</button>
```

Isian pencarian bernama aksesibel dan field tanggal dengan label:

```blade
<input class="input" type="search" aria-label="Cari penyedia">

<div class="field">
    <label for="d1">Tanggal transfer</label>
    <input class="input" id="d1" type="date">
</div>
```

Badge status:

```blade
<span class="badge b-warn">Menunggu</span>
<span class="badge b-ok">Disetujui</span>
```

Card dan tabel lebar memakai wrapper yang dapat difokuskan dan memiliki nama:

```blade
<section class="card">
    <div class="card-h"><h2>Antrean verifikasi</h2></div>
    <div class="table-wrap" role="region" tabindex="0"
         aria-label="Antrean verifikasi. Gulir horizontal untuk melihat seluruh kolom">
        <table class="admin-table t-wide">
            <thead>
                <tr><th scope="col">Penyedia</th><th scope="col">Status</th></tr>
            </thead>
            <tbody>
                <tr><td>Graha Melati Management</td><td><span class="badge b-warn">Menunggu</span></td></tr>
            </tbody>
        </table>
    </div>
</section>
```

Jika pagination pada layar sempit perlu digulir, beri nama pada daftarnya dan jadikan area gulir dapat difokuskan dengan `tabindex="0"`. Tautan halaman tetap menjadi kontrol navigasi.

Dialog native yang sudah digunakan pada alur Klien:

```blade
<dialog id="dlg" aria-labelledby="dlg-t">
    <h2 id="dlg-t">Ajukan pembatalan acara?</h2>
    <p>Admin akan meninjau pengajuan Anda.</p>
    <div class="actions">
        <button class="btn btn-o" type="button">Kembali</button>
        <button class="btn btn-bad" type="button">Ajukan pembatalan</button>
    </div>
</dialog>
```

Toast tidak memiliki markup Blade bersama; `aksi.js` dan `app-ui.js` membuatnya saat runtime dengan semantik status/`aria-live`. Pertahankan mekanisme tersebut dan jangan menambahkan toast statis yang dapat bertentangan dengan pesan yang sudah ada.

Saat ini `.btn-sm` berukuran 36 px pada desktop dan dinaikkan menjadi 44 px pada breakpoint sentuh. Status error field memakai `aria-invalid`/`.field-error`. Karena form dan aksi bisnis belum mengirim request ke server, script bersama menampilkan pemberitahuan pratinjau bahwa data tidak dikirim/disimpan; aksi pratinjau bukan state sukses. Belum tersedia pola bersama yang mengikat loading, empty, atau error daftar ke respons backend, jadi jangan menampilkan state tersebut sebagai hasil request nyata.

## Responsivitas dan fokus

Breakpoint komponen dan halaman tetap menggunakan media query yang sudah ada, termasuk 1100, 1024, 900, 760, 700, 560, 480, 420, dan 360 px serta beberapa breakpoint khusus halaman. Nilai tersebut tidak diganti massal karena aturan media query CSS tidak dapat memakai custom property sebagai ambang.

Token layer mengatur menu, topbar, overlay, sidebar, notifikasi, dan preview bar. Dialog native memakai top layer browser. Fokus terlihat mengikuti aturan yang sudah digunakan oleh komponen portal, dan transisi tetap menghormati aturan `prefers-reduced-motion` yang ada.

## Kontras yang diperiksa

Rasio pasangan teks/latar dari nilai CSS saat ini:

| Pasangan representatif | Rasio |
|---|---:|
| Tombol utama, teks putih / brand | 9.90:1 |
| Tombol outline, teks utama / putih | 17.24:1 |
| Tombol bahaya outline (`.btn-bad`), teks bahaya / putih | 6.57:1 |
| Tombol bahaya berlatar (`.btn-danger`), teks bahaya / latar merah muda | 5.94:1 |
| Status sukses / badge sukses | 8.57:1 / 4.89:1 |
| Status peringatan / badge peringatan | 8.15:1 / 4.55:1 |
| Status info / bahaya / netral / brand | 8.24:1 / 8.20:1 / 11.74:1 / 12.37:1 |
| Teks redup / putih | 6.86:1 |
| Ring fokus / latar halaman; border input / putih | 3.24:1; 3.73:1 |

Pemeriksaan ini meliputi pasangan warna komponen representatif saja. Ini bukan audit WCAG seluruh halaman; fokus, warna state interaktif, kombinasi latar lain, dan konten halaman perlu diuji terpisah.
