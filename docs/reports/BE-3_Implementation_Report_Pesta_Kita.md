# Laporan Implementasi BE-3 — Pesta Kita (Gate 4B)

Tanggal: 2026-11-16 · Status: **Selesai — terverifikasi** · Tahap: BE-3 (Gate 4B)

Acuan: `docs/reports/BE-3_Preparation_Report_Pesta_Kita.md` (Gate 4A, §17 urutan kerja, §18 rekomendasi keputusan), persetujuan pengguna atas Gate 4A + §18 butir 1–10, dan instruksi pelaksanaan Gate 4B. Kondisi awal: BE-1 + BE-2 terimplementasi (96 tes / 704 assertions), MySQL `pesta_kita` 23 migration Ran, seluruh perubahan BE-1/BE-2/frontend belum di-commit dan dipertahankan.

## 1. Status

Selesai — terverifikasi. Seluruh lingkup inti A–G dilaksanakan bertahap sesuai §17 dengan pengujian di setiap bagian; regresi penuh, verifikasi keamanan, dan pemeriksaan kualitas lulus. Tidak ada commit/push/merge/PR; tidak ada perintah destruktif; `.env`, credential, dependency, dan konfigurasi autentikasi tidak diubah; Laravel Boost dipertahankan.

## 2. Lingkup

Dikerjakan (inti A–G):

- A. Skema additive: metadata media + kolom keputusan verifikasi profil (reviewer, alasan, waktu pengajuan).
- B. Disk `private` dengan `serve=false`; policy media/profil/katalog/venue/ruangan.
- C. MediaController: unggah foto (profil/item/venue), unggah dokumen verifikasi, hapus, stream berotorisasi.
- D. CRUD katalog vendor (layanan & peralatan, satu struktur `kind` — B15), toggle status, foto item.
- E. CRUD venue + ruangan + fasilitas; `cleaning_fee` hanya Admin.
- F. Workflow verifikasi: pengajuan penyedia (dokumen wajib per role), antrean/detail Admin, setujui/tolak dengan alasan, audit log.
- G. Endpoint visibilitas Klien: katalog vendor/peralatan/venue, detail item/venue, profil publik penyedia; filter server.
- Wiring Blade terbatas: 20 view terhubung ke backend, tanpa redesign.

Sengaja tidak dikerjakan (sesuai keputusan):

- Kalender ketersediaan dan pengajuan harga → **BE-3b**.
- Tambah item/venue ke acara draft, pesanan, tagihan, pembayaran, refund, pencairan → **BE-4/BE-5+**.
- Stok peralatan, UI rekening bank, notifikasi, rundown, ulasan → di luar lingkup BE-3.
- Halaman frontend di luar 20 view wiring tetap prototipe.

## 3. Keputusan yang diterapkan (Gate 4B butir 1–10)

| Butir | Keputusan                                                                                | Implementasi                                                                                                                                                                                                                                                     |
| ----- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1     | Lingkup inti A–G; kalender & pengajuan harga ke BE-3b; tambah item ke draft ke BE-4/BE-5 | Tidak ada endpoint kalender/harga; tombol "Tambahkan ke acara" pada detail Klien diganti catatan penundaan BE-4/BE-5; ketersediaan berlabel "Perlu konfirmasi".                                                                                                  |
| 2     | Tanpa persetujuan Admin per item; visibilitas mengikuti verifikasi penyedia              | `CatalogItem` tidak punya alur approve per item; perubahan harga langsung tersimpan; scope `publikUntukKlien` = item aktif + vendor terverifikasi. Teks UI "menunggu persetujuan Admin per item" dihapus.                                                        |
| 3     | Venue mengikuti verifikasi profil saat approve; Admin dapat menonaktifkan individual     | `ProviderVerificationController@approve` menyelaraskan status venue ke terverifikasi kecuali yang berstatus nonaktif (dinonaktifkan Admin); `Admin\VenueController` dapat mengubah venue ke terverifikasi/nonaktif; scope venue menuntut keduanya terverifikasi. |
| 4     | Kolom reviewer, alasan, waktu pengajuan via migration additive                           | Migration `2026_11_16_200002`: `verification_note` (text nullable), `reviewed_by` (FK users nullOnDelete), `verification_submitted_at` (timestamp nullable) pada `vendor_profiles` & `venue_profiles`.                                                           |
| 5     | Frontend hanya wiring + penyesuaian diperlukan; tanpa redesign                           | 20 view memakai kelas CSS dan struktur yang sudah ada; tanpa file CSS/JS baru; penyesuaian terbatas pada penggantian data contoh dengan data nyata, form nyata, dan catatan penundaan fitur.                                                                     |
| 6     | Disk `private` dengan `serve=false`                                                      | `config/filesystems.php`: disk `private` root `storage/app/private`, `serve=false`; disk `local` juga `serve=false` (menutup route framework `storage/{path}`). Tanpa symlink `public/storage`.                                                                  |
| 7     | Bukti rekening bukan persyaratan B14                                                     | `VerificationDocType` hanya ktp/nib/npwp/bukti_venue; halaman verifikasi kedua portal menyatakan eksplisit "Bukti rekening bukan persyaratan verifikasi".                                                                                                        |
| 8     | `cleaning_fee` hanya Admin                                                               | Form venue penyedia menampilkan `cleaning_fee` read-only; `StoreVenueRequest`/`UpdateVenueRequest` tidak menerimanya; hanya `AdminUpdateVenueRequest` + `Admin\VenueController@update` yang dapat mengubah.                                                      |
| 9     | Perbarui dokumentasi B12/B14                                                             | `docs/BACKEND_BUSINESS_RULES.md`: baris B12/B14 bagian B ditandai DISETUJUI, rincian di bagian E, persetujuan Gate 4B di bagian F baru.                                                                                                                          |
| 10    | Satu dokumen aktif per jenis; ganti setelah unggah baru sukses                           | `MediaController@storeVerificationDocument` menyimpan dokumen baru dalam transaksi; dokumen lama jenis sama dihapus (file + baris) hanya setelah penyimpanan baru berhasil; gagal validasi/unggah tidak menyentuh dokumen lama.                                  |

Aturan terkunci yang dijaga: T2 (keputusan verifikasi tidak pernah mengubah status akun penyedia), A7 (kontak pribadi tidak dirender ke Klien — diuji `assertDontSee`), A4/A5 (komisi 10% ditampilkan sebagai estimasi terpisah, bukan harga final), B4 (cleaning_fee terpisah dari rental_price), B15 (satu tabel katalog).

## 4. Perubahan file

### 4.1 Migration baru (2, additive, reversible)

| File                                                                                            | Isi                                                                                                                                                                                      |
| ----------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `database/migrations/2026_11_16_200001_add_kind_and_metadata_to_media_table.php`                | `media` + `kind` (default foto), `doc_type` nullable, `disk` (default private), `mime_type`, `size`, `original_name`; index `(owner_type, owner_id, kind)`. `down()` drop index + kolom. |
| `database/migrations/2026_11_16_200002_add_verification_review_fields_to_provider_profiles.php` | `vendor_profiles`/`venue_profiles` + `verification_note`, `reviewed_by` (FK users nullOnDelete), `verification_submitted_at`. `down()` dropConstrainedForeignId + kolom.                 |

Diterapkan pada MySQL `pesta_kita` sebagai batch `[3]`; `migrate:status` → **25 Ran** (7 BE-1 `[1]` + 16 BE-2 `[2]` utuh). Tidak ada migration lama yang diubah.

### 4.2 Enum baru (3)

- `app/Enums/VerificationStatus.php` — menunggu/terverifikasi/ditolak/nonaktif.
- `app/Enums/MediaKind.php` — foto/dokumen_verifikasi.
- `app/Enums/VerificationDocType.php` — ktp/nib/npwp/bukti_venue + `label()`.

### 4.3 Controller baru (8)

| Controller                                                      | Tanggung jawab                                                                                                                                                                                                                                                                       |
| --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `app/Http/Controllers/MediaController.php`                      | Stream berotorisasi (`show`), hapus (`destroy`), unggah foto profil/item/venue, unggah dokumen verifikasi (satu aktif per jenis, ganti setelah sukses).                                                                                                                              |
| `app/Http/Controllers/VerificationController.php`               | Halaman verifikasi penyedia (dokumen per jenis, status, catatan), pengajuan verifikasi (wajib dokumen lengkap per role).                                                                                                                                                             |
| `app/Http/Controllers/Vendor/CatalogItemController.php`         | Daftar layanan/peralatan, form create/edit, store/update, toggle aktif/nonaktif, soft delete.                                                                                                                                                                                        |
| `app/Http/Controllers/Venue/VenueController.php`                | Daftar venue, form create/edit (ruangan pertama + fasilitas saat create), store/update, soft delete.                                                                                                                                                                                 |
| `app/Http/Controllers/Venue/VenueRoomController.php`            | Tambah/ubah/hapus ruangan venue.                                                                                                                                                                                                                                                     |
| `app/Http/Controllers/Admin/ProviderVerificationController.php` | Antrean menunggu (vendor+venue urut waktu pengajuan), detail mitra (profil, dokumen, subjek), approve (transaksi terkunci: profil terverifikasi, foto publik, venue mengikuti profil kecuali nonaktif individual, reviewer/waktu/audit log), tolak (wajib alasan, akun tetap aktif). |
| `app/Http/Controllers/Admin/VenueController.php`                | Form Admin ubah `cleaning_fee` + status venue individual (terverifikasi/nonaktif).                                                                                                                                                                                                   |
| `app/Http/Controllers/Klien/CatalogController.php`              | Katalog vendor/peralatan/venue + filter server (q/category/max_price; q/kind/max_price), detail item/venue (404 bila tidak publik), profil publik penyedia terverifikasi beserta item/venue tayang; foto hanya yang publik.                                                          |

### 4.4 Form Request baru (12)

`app/Http/Requests/Media/{StorePhotoRequest,StoreVerificationDocumentRequest}.php`, `Vendor/{StoreCatalogItemRequest,UpdateCatalogItemRequest,ToggleCatalogItemStatusRequest}.php`, `Venue/{StoreVenueRequest,UpdateVenueRequest,StoreVenueRoomRequest,UpdateVenueRoomRequest}.php`, `Admin/{AdminUpdateVenueRequest,RejectVerificationRequest}.php`, `Verification/SubmitVerificationRequest.php`.

Aturan kunci: foto `mimes:jpg,jpeg,png,webp` + `max:5120` + validasi `getimagesize`; dokumen `mimes:pdf,jpg,jpeg,png` + `max:5120` + cek MIME nyata, `doc_type` dibatasi per role; harga integer rupiah ≥ 0; `pricing_mode` tetap/perlu_penawaran; reject `reason` wajib max 1000; toggle status aktif/nonaktif.

### 4.5 Policy baru (5) + registrasi

`app/Policies/{CatalogItemPolicy,MediaPolicy,ProviderProfilePolicy,VenuePolicy,VenueRoomPolicy}.php` — kepemilikan untuk penyedia, review/decide untuk Admin, baca media sesuai visibility/peran (dokumen verifikasi: pemilik + Admin; foto publik: semua peran login). Terdaftar di `app/Providers/AppServiceProvider.php`.

### 4.6 Model & konfigurasi yang disesuaikan

- `app/Models/Media.php` — cast kind/doc_type/visibility, scope foto/dokumenVerifikasi, metadata.
- `app/Models/CatalogItem.php` — cast status/pricing_mode/kind, scope `publikUntukKlien`.
- `app/Models/Venue.php` — scope `publikUntukKlien` (venue + profil terverifikasi).
- `app/Models/VendorProfile.php`, `app/Models/VenueProfile.php` — fillable/cast kolom verifikasi baru, relasi `reviewer`.
- `config/filesystems.php` — disk `private` baru; `local` dan `private` `serve=false`.
- `routes/web.php` — route BE-3 eksplisit sebelum catch-all portal (lihat §5).
- Factory: state pendukung tes BE-3 (`MediaFactory::publik/dokumen`, `CatalogItemFactory::nonaktif`, `VenueFactory::terverifikasi`, `VendorProfileFactory`/`VenueProfileFactory::menunggu/terverifikasi`).

### 4.7 Tes baru (7 file + 1 trait)

| File                                                      | Cakupan                                                                                                                                                                                                                              |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `tests/Feature/Database/Be3SchemaAdditionsTest.php`       | Kolom baru media/profil, default, FK reviewer nullOnDelete.                                                                                                                                                                          |
| `tests/Feature/Authorization/Be3PolicyTest.php`           | Matriks izin policy lintas peran/pemilik.                                                                                                                                                                                            |
| `tests/Feature/Media/MediaManagementTest.php`             | Unggah/hapus/stream foto & dokumen, batas 10 foto, MIME palsu ditolak, satu dokumen aktif per jenis + ganti setelah sukses, akses privat lintas peran ditolak, route `storage/{path}` tertutup.                                      |
| `tests/Feature/Vendor/CatalogCrudTest.php`                | CRUD layanan/peralatan, validasi harga/pricing_mode, toggle, soft delete, otorisasi pemilik.                                                                                                                                         |
| `tests/Feature/Venue/VenueCrudTest.php`                   | CRUD venue + ruangan + fasilitas, cleaning_fee tidak dapat diisi penyedia, otorisasi.                                                                                                                                                |
| `tests/Feature/Verification/VerificationWorkflowTest.php` | Pengajuan (dokumen wajib per role), approve/tolak Admin, alasan wajib, venue mengikuti profil kecuali nonaktif individual, foto publik saat approve, akun tetap aktif (T2), pengajuan ulang setelah ditolak, antrean & detail mitra. |
| `tests/Feature/Klien/CatalogVisibilityTest.php`           | Filter server, item/venue/profil tidak publik → 404, foto privat tidak tampil, kontak privat tidak bocor, role lain 403, guest redirect login.                                                                                       |
| `tests/Concerns/MakesUploadedFiles.php`                   | Trait biner gambar/PDF base64 (ekstensi GD tidak tersedia di lingkungan ini).                                                                                                                                                        |

Baseline BE-1/BE-2 (96 tes) tidak diubah melemah; `ProjectPortalPagesTest`/`ProjectPortalAccessibilityTest` tetap hijau terhadap view yang di-wiring.

### 4.8 Wiring Blade (20 view, tanpa redesign)

| Portal     | View                                                                                                                                                                       |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| vendor (6) | `layanan`, `peralatan`, `item-form`, `peralatan-form`, `verifikasi`, `profil` (bagian foto)                                                                                |
| venue (4)  | `data`, `form` (create/edit + ruangan + fasilitas + foto), `verifikasi`, `profil` (bagian foto)                                                                            |
| admin (3)  | `admin-penyedia` (antrean nyata + kelengkapan dokumen), `mitra-detail` (profil/dokumen/subjek + form setujui/tolak), `admin-venue-form` (cleaning_fee + status individual) |
| klien (7)  | `katalog-vendor`, `katalog-peralatan`, `katalog` (venue), `katalog-vendor-detail`, `katalog-venue-detail`, `penyedia-vendor`, `penyedia-venue`                             |

Konvensi wiring (mengikuti pola BE-1):

- Form nyata `method="POST"` + `@csrf` + `@method('PUT'/'DELETE')` ke route bernama; konfirmasi hapus memakai `onsubmit="return confirm(...)"` native.
- Atribut prototipe `data-form`/`data-confirm`/`data-tambah`/`data-only` tidak dipakai pada form wired — `aksi.js` mencegat atribut itu dan hanya menampilkan toast pratinjau tanpa mengirim ke server.
- Semua view tahan render tanpa data (catch-all `GET /{portal}/{page}` merender view tanpa variabel; diuji `ProjectPortalPagesTest`): null guard `$x ?? null` / `?? collect()`, empty-state dengan tautan kembali.
- Foto dibaca lewat `route('media.show', $media)`; harga `number_format(..., 0, ',', '.')`; estimasi Klien = harga + komisi 10% (`intdiv`) berlabel "Estimasi"; `Str::limit($x ?? '', N)` untuk deskripsi nullable.
- Badge status mengikuti `VerificationStatus`; data contoh/prototipe dan klaim menyesatkan dihapus; fitur tertunda diberi catatan jujur (BE-3b / BE-4/BE-5).
- Push JS pratinjau (`katalog-filter.js`, `profil-media.js`) dilepas dari view wired; filter berjalan di server.

### 4.9 Dokumentasi

- `docs/BACKEND_BUSINESS_RULES.md` — B12/B14 ditandai DISETUJUI (bagian B), rincian keputusan (bagian E), bagian F baru: persetujuan Gate 4B butir 1–10.
- `docs/BACKEND_AGENT_CONTEXT.md` — addendum bagian 11 (kondisi BE-3 terverifikasi); penunjuk header diperbarui.
- `docs/AI_AGENT_CONTEXT.md` — bagian 6.15 (hasil BE-3), baris riwayat 9.5, bagian 14 (status dokumentasi backend).
- `docs/reports/BE-3_Implementation_Report_Pesta_Kita.md` — laporan ini.

## 5. Route (terdaftar via `route:list`, 62 total; route BE-3 eksplisit mendahului catch-all)

| Method     | URI                                                                           | Nama                                                                       |
| ---------- | ----------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| GET        | `/media/{media}`                                                              | `media.show`                                                               |
| DELETE     | `/media/{media}`                                                              | `media.destroy`                                                            |
| POST       | `/profil/foto`                                                                | `profil.foto.store`                                                        |
| POST       | `/verifikasi/dokumen`                                                         | `verifikasi.dokumen.store`                                                 |
| POST       | `/verifikasi/ajukan`                                                          | `verifikasi.ajukan`                                                        |
| GET        | `/vendor/layanan` · `/vendor/peralatan`                                       | `vendor.layanan` · `vendor.peralatan`                                      |
| GET        | `/vendor/item-form` · `/vendor/item-form/{item}`                              | `vendor.katalog.create` · `vendor.katalog.edit`                            |
| GET        | `/vendor/peralatan-form` · `/vendor/peralatan-form/{item}`                    | `vendor.peralatan.create` · `vendor.peralatan.edit`                        |
| POST       | `/vendor/katalog`                                                             | `vendor.katalog.store`                                                     |
| PUT        | `/vendor/katalog/{item}`                                                      | `vendor.katalog.update`                                                    |
| POST       | `/vendor/katalog/{item}/status`                                               | `vendor.katalog.status`                                                    |
| DELETE     | `/vendor/katalog/{item}`                                                      | `vendor.katalog.destroy`                                                   |
| POST       | `/vendor/katalog/{item}/foto`                                                 | `vendor.katalog.foto.store`                                                |
| GET        | `/vendor/verifikasi` · `/venue/verifikasi`                                    | `vendor.verifikasi` · `venue.verifikasi`                                   |
| GET/POST   | `/venue/data`                                                                 | `venue.data` · `venue.data.store`                                          |
| PUT/DELETE | `/venue/data/{venue}`                                                         | `venue.data.update` · `venue.data.destroy`                                 |
| POST       | `/venue/data/{venue}/foto`                                                    | `venue.data.foto.store`                                                    |
| POST       | `/venue/data/{venue}/ruangan`                                                 | `venue.ruangan.store`                                                      |
| PUT/DELETE | `/venue/ruangan/{room}`                                                       | `venue.ruangan.update` · `venue.ruangan.destroy`                           |
| GET        | `/venue/form` · `/venue/form/{venue}`                                         | `venue.form.create` · `venue.form.edit`                                    |
| GET        | `/admin/admin-penyedia`                                                       | `admin.penyedia.index`                                                     |
| GET        | `/admin/penyedia/{type}/{id}`                                                 | `admin.penyedia.detail` (type: vendor_profile/venue_profile)               |
| POST       | `/admin/penyedia/{type}/{id}/verifikasi/setujui` · `.../tolak`                | `admin.penyedia.verifikasi.approve` · `admin.penyedia.verifikasi.tolak`    |
| GET        | `/admin/admin-venue-form/{venue}`                                             | `admin.venue.edit`                                                         |
| POST       | `/admin/venue/{venue}`                                                        | `admin.venue.update`                                                       |
| GET        | `/klien/katalog-vendor` · `/klien/katalog-peralatan` · `/klien/katalog`       | `klien.katalog.vendor` · `klien.katalog.peralatan` · `klien.katalog.venue` |
| GET        | `/klien/katalog-vendor-detail/{item}` · `/klien/katalog-venue-detail/{venue}` | `klien.katalog.vendor.detail` · `klien.katalog.venue.detail`               |
| GET        | `/klien/penyedia-vendor/{profile}` · `/klien/penyedia-venue/{profile}`        | `klien.penyedia.vendor` · `klien.penyedia.venue`                           |

Seluruh route berada di bawah middleware auth + role portal yang sudah ada dari BE-1 (guest → `/login`, role salah → 403).

## 6. Hasil pengujian (aktual, dijalankan 2026-11-16)

| Pemeriksaan                                  | Hasil                           |
| -------------------------------------------- | ------------------------------- |
| Baseline sebelum BE-3                        | 96 lulus (704 assertions)       |
| Checkpoint wiring vendor (6 view)            | 61 lulus (439 assertions)       |
| Checkpoint wiring venue (4 view)             | 39 lulus (358 assertions)       |
| Checkpoint wiring admin (3 view)             | 39 lulus (358 assertions)       |
| Checkpoint wiring klien (7 view)             | 16 lulus (268 assertions)       |
| **Regresi penuh `php artisan test` (akhir)** | **220 lulus (1139 assertions)** |
| Regresi penuh diulang setelah Pint           | 220 lulus (1139 assertions)     |

Setiap langkah §17 (skema → disk/policy → media → katalog vendor → venue → verifikasi → visibilitas Klien → wiring per portal) disertai tes terarah sebelum lanjut, sesuai instruksi pengguna. Tidak ada tes yang dihapus atau dilemahkan agar lulus.

## 7. Verifikasi keamanan

| Pemeriksaan                                                       | Hasil                                                                   |
| ----------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Disk `local` & `private` `serve=false` (`config/filesystems.php`) | Lulus — route framework `storage/{path}` tertutup                       |
| Symlink `public/storage`                                          | Tidak ada (`Test-Path public/storage` → False)                          |
| Stream media tanpa izin (guest/role lain/bukan pemilik)           | Ditolak — `MediaManagementTest` + `Be3PolicyTest`                       |
| Dokumen verifikasi tetap privat setelah approve                   | Lulus — hanya foto yang dipublikasikan                                  |
| Kontak privat penyedia di halaman Klien                           | Tidak bocor — `assertDontSee` phone/email                               |
| `cleaning_fee` dari form penyedia                                 | Ditolak/tidak diproses — hanya `AdminUpdateVenueRequest`                |
| Keputusan verifikasi mengubah status akun (T2)                    | Tidak — diuji eksplisit                                                 |
| Approve/tolak ganda atau balapan dengan submit                    | Ditolak — transaksi + `lockForUpdate` + cek status menunggu             |
| Unggah MIME palsu / melebihi batas                                | Ditolak validasi server (mimes + getimagesize/MIME nyata + max 5120 KB) |

## 8. Pemeriksaan kualitas & reversibility

| Pemeriksaan                                   | Hasil                                                                                                                                                                 |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `php artisan migrate` (MySQL lokal, additive) | 2 migration BE-3 batch `[3]`; `migrate:status` → 25 Ran; BE-1/BE-2 utuh                                                                                               |
| Reversibility (SQLite sementara, pasca-Pint)  | `migrate` penuh → rollback 200002 & 200001 → migrate ulang keduanya: DONE; file sementara dihapus; koneksi kembali ke MySQL; tanpa rollback pada database berisi data |
| `php artisan view:cache`                      | Seluruh Blade terkompilasi sukses (lalu `view:clear`)                                                                                                                 |
| `php artisan route:list`                      | 62 route; seluruh route BE-3 terdaftar sebelum catch-all                                                                                                              |
| Pint                                          | 38 isu gaya pada file BE-3 diperbaiki (line ending/EOF/import/brace); `pint --test app database routes tests` → PASS 142 file                                         |
| `git diff --check`                            | Lulus (tanpa whitespace error)                                                                                                                                        |
| Dependensi / `.env` / auth config             | Tidak diubah; Laravel Boost utuh                                                                                                                                      |
| Git                                           | Tanpa commit/push/merge/PR; perubahan BE-1/BE-2/frontend yang belum di-commit tetap ada                                                                               |

## 9. Masalah yang ditemukan dan diselesaikan selama implementasi

1. **`aksi.js` mencegat form prototipe** (`data-form`/`data-confirm` → preventDefault + toast palsu). Form wired tidak memakai atribut itu; konfirmasi destruktif memakai `confirm()` native. Tanpa ini, submission nyata akan terlihat sukses palsu tanpa sampai ke server.
2. **Catch-all portal merender view tanpa data** (`ProjectPortalPagesTest` mengakses setiap halaman tanpa parameter). Semua view wired diberi null guard + empty-state; halaman detail tanpa subjek menampilkan tautan kembali, bukan error.
3. **Bug kompilasi Blade `@endif@if(...)` bersebelahan**: direktif kedua tidak terkompilasi → parse error `unexpected endif`. Ditemukan pada `katalog-vendor-detail` dan `penyedia-venue` saat checkpoint klien; diperbaiki dengan memisah spasi, lalu seluruh view dipindai ulang (pola lain tidak ditemukan).
4. **`Str::limit(null)` TypeError** untuk deskripsi nullable — dicegah dengan `?? ''` di semua pemanggilan.
5. **Ekstensi GD tidak tersedia** di lingkungan ini — tes unggah memakai trait `MakesUploadedFiles` (biner base64), bukan manipulasi gambar.
6. **`@csrf` terlewat** pada satu form unggah dokumen (vendor/verifikasi) — ditemukan sendiri sebelum checkpoint, langsung diperbaiki.
7. Relasi Eloquent yang ter-cache basi setelah approve diselesaikan dengan `refresh()` pada controller (tes memverifikasi kondisi terkini).

Tidak ditemukan konflik antara rancangan dan kondisi kode aktual yang menuntut keputusan bisnis baru; seluruh penyimpangan kecil (butir 3–7 di atas) bersifat teknis dan diselesaikan dalam lingkup yang disetujui.

## 10. Sisa pekerjaan (di luar lingkup BE-3, menunggu instruksi)

- **BE-3b**: kalender ketersediaan backend (status manual penyedia; "dipesan" hanya dari pesanan terkonfirmasi — B11) dan alur pengajuan harga (`price_change_requests`: menunggu → disetujui/ditolak; harga lama berlaku selama menunggu — A11).
- **BE-4/BE-5**: tambah item/venue ke acara draft, alur pesanan/quote, persetujuan Admin per item, masa keberatan (B7).
- **BE-6+**: tagihan, pembayaran transfer manual, kode unik (B2), refund (B1), pencairan, snapshot kebijakan (A10).
- Stok peralatan (kolom belum ada; form menyebut "tahap berikutnya"), UI rekening bank penyedia, notifikasi, rundown, ulasan.
- Keputusan terbuka bagian B selain B4/B12/B14/B15/B18 (mis. B1 refund, B2 kode unik, B3 dasar komisi, B5 deposit, B6 jumlah venue) tetap menunggu jawaban pengguna.
- Halaman frontend di luar 20 view wiring masih prototipe; wiring lanjutan mengikuti tahap backend terkait.
- Seluruh perubahan (BE-1/BE-2/BE-3 + baseline frontend) belum di-commit — keputusan commit ada pada pengguna.

## 11. Langkah berikutnya yang disarankan

1. Tinjau laporan ini dan hasil `php artisan test` (220 lulus) serta halaman yang di-wiring pada environment lokal.
2. Putuskan jadwal BE-3b (kalender + pengajuan harga) atau lanjut BE-4 sesuai prioritas.
3. Pertimbangkan commit terpisah per tahap (BE-1, BE-2, BE-3) atas persetujuan pengguna untuk mengunci baseline.

Titik awal teknis BE-3b: tabel `availability_days` dan `price_change_requests` sudah ada sejak BE-2 (kolom nullable, tanpa logika); enum `AvailabilityStatus`/`PriceChangeStatus` sudah tersedia; route Kalender/pengajuan harga belum dibuat.
