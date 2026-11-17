# Laporan Implementasi BE-4 — Wiring Request Quote Pesta Kita

Tanggal: 2026-11-17 · Status: **Selesai — terverifikasi** · Tahap: BE-4

Acuan: `docs/BACKEND_IMPLEMENTATION_PLAN.md`, `docs/BACKEND_API_CONTRACT.md`, `docs/BACKEND_BUSINESS_RULES.md`, dan halaman Tahap 5.4. Lingkup dibatasi pada penyambungan alur permintaan penawaran Klien → Vendor/Venue → Admin; BE-5 dan seterusnya tidak dikerjakan.

## 1. Hasil

Delapan view quote kini menggunakan backend: daftar/pengajuan/detail Klien, daftar/detail Vendor, daftar/detail Pemilik Venue, dan antrean/detail Admin. Pengajuan disimpan ke tabel domain quote yang sudah tersedia; tidak ada migration atau dependency baru. Route POST eksplisit ditempatkan sebelum catch-all portal.

## 2. Perilaku dan aturan yang dijaga

- Klien hanya dapat meminta penawaran untuk item Vendor dengan mode perlu penawaran atau ruangan venue yang saat ini publik/terverifikasi.
- Harga katalog dibaca dari database dan disimpan sebagai snapshot saat quote dibuat. `event_id` opsional dan hanya dapat menunjuk ke acara draft milik Klien; BE-4 tidak membuat acara dan tidak menambahkan item ke pesanan.
- Vendor dan Pemilik Venue hanya melihat quote untuk item/ruangan miliknya dan dapat mengajukan proposal satu kali saat status masih `diajukan`. Nominal wajib bilangan bulat rupiah; catatan opsional. Proposal tidak mengubah harga katalog.
- Nama Klien dan nama acara tidak ditampilkan dalam portal penyedia. Catatan pengguna dirender melalui escaping Blade.
- Admin hanya dapat memutuskan quote berstatus `diusulkan_penyedia`. Harga persetujuan diambil dari proposal terakhir di database, bukan dari browser. Alasan penolakan wajib.
- Keputusan dibuat di dalam transaksi dengan penguncian baris, menyimpan `QuoteDecision`, memperbarui status quote, dan menulis `AuditLog`.
- Satu keputusan final berlaku per quote. Keputusan kedua atau pengajuan ulang setelah ditolak tidak tersedia pada skema/workflow sekarang. Tidak ada notifikasi yang ditambahkan.

## 3. Route aktif

| Role | Method dan path | Fungsi |
|---|---|---|
| Klien | `GET /klien/permintaan-penawaran` | Daftar quote sendiri dan form dengan pilihan subjek publik/acara draft |
| Klien | `POST /klien/permintaan-penawaran` | Validasi, snapshot harga katalog, simpan quote |
| Klien | `GET /klien/permintaan-penawaran-detail/{quoteRequest}` | Detail quote milik Klien |
| Vendor | `GET /vendor/permintaan-penawaran` | Daftar quote untuk item milik Vendor |
| Vendor | `GET /vendor/permintaan-penawaran-detail/{quoteRequest}` | Detail quote |
| Vendor | `POST /vendor/permintaan-penawaran-detail/{quoteRequest}` | Ajukan proposal harga |
| Venue | `GET /venue/permintaan-penawaran` | Daftar quote untuk ruangan milik Pemilik Venue |
| Venue | `GET /venue/permintaan-penawaran-detail/{quoteRequest}` | Detail quote |
| Venue | `POST /venue/permintaan-penawaran-detail/{quoteRequest}` | Ajukan proposal harga |
| Admin | `GET /admin/permintaan-penawaran` | Antrean dengan filter status |
| Admin | `GET /admin/permintaan-penawaran-detail/{quoteRequest}` | Detail proposal dan keputusan |
| Admin | `POST /admin/permintaan-penawaran/{quoteRequest}/approve` | Setujui proposal terbaru |
| Admin | `POST /admin/permintaan-penawaran/{quoteRequest}/reject` | Tolak dengan alasan |

Semua route berada di grup autentikasi dan role masing-masing. Admin juga mempertahankan GET halaman detail tanpa ID sebagai empty state kompatibilitas.

## 4. File utama

- Controller: `app/Http/Controllers/Klien/QuoteRequestController.php`, `Vendor/QuoteRequestController.php`, `Venue/QuoteRequestController.php`, dan `Admin/QuoteRequestController.php`.
- Form Request baru: `app/Http/Requests/Client/StoreQuoteRequest.php`, `Vendor/StoreQuoteProposalRequest.php`, `Venue/StoreQuoteProposalRequest.php`, dan `Admin/DecideQuoteRequest.php`.
- Route: `routes/web.php`.
- View quote: `resources/views/{klien,vendor,venue,admin}/permintaan-penawaran*.blade.php`.
- Integrasi terkait: detail katalog Klien/Vendor/Venue, kalender Klien, dan detail acara Klien menyediakan tautan/form yang relevan; crash enum-key pada detail acara juga diperbaiki.
- Tes feature baru: `tests/Feature/Portal/{Client,Vendor,Venue,Admin}QuoteRequestControllerTest.php`.

## 5. Pengujian

- Tes quote dan regresi portal terarah: **40 tests / 454 assertions — lulus**, mencakup pengajuan/pemilik/validasi, tamu, proposal penyedia, keputusan Admin, audit, serta halaman portal terkait.
- Perintah: `php artisan test --compact tests/Feature/Portal/ClientQuoteRequestControllerTest.php tests/Feature/Portal/VendorQuoteRequestControllerTest.php tests/Feature/Portal/VenueQuoteRequestControllerTest.php tests/Feature/Portal/AdminQuoteRequestControllerTest.php tests/Feature/Portal/EventQuoteCatalogWiringTest.php tests/Feature/ProjectPortalPagesTest.php`.
- Suite penuh: `php artisan test --compact` — **289 tests / 1536 assertions lulus**.
- Blade: `php artisan view:cache` berhasil untuk seluruh template; `php artisan view:clear` dijalankan setelahnya.
- Route: `php artisan route:list --path=permintaan-penawaran --except-vendor` menampilkan **13 route** quote.
- Pint `--dirty --format agent` telah dijalankan; `git diff --check` lulus setelah pembaruan dokumentasi.

## 6. Batasan dan tahap berikutnya

- BE-4 tidak mengimplementasikan pembuatan/pengajuan acara, pesanan, notifikasi, tagihan, pembayaran, refund, pencairan, atau proses usulan ulang setelah keputusan final.
- Penolakan quote final untuk satu request karena skema saat ini menyimpan satu keputusan final; perubahan menjadi siklus revisi memerlukan keputusan produk dan desain skema/workflow.
- Berikutnya adalah BE-5 (acara dan persetujuan pesanan). Jangan mulai sebelum ada instruksi pengguna; keputusan B7 tetap perlu dipastikan untuk aturan masa keberatan.
