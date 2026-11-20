<?php

namespace Database\Seeders;

use App\Enums\AvailabilityStatus;
use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PricingMode;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\CompanyBankAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * BE-1: akun demo empat portal untuk lingkungan local.
 * BE-2: data domain non-finansial kecil yang saling terhubung (katalog,
 * venue, ruangan, fasilitas, ketersediaan, acara draft, dan penawaran).
 * Tidak boleh dijalankan di production. Kata sandi seluruh akun demo:
 * "password" (hanya untuk pengembangan lokal, bukan kredensial produksi).
 * Idempoten: seluruh data memakai firstOrCreate sehingga aman diulang.
 */
class LocalDemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('LocalDemoSeeder dilewati: hanya untuk environment local.');

            return;
        }

        User::firstOrCreate(
            ['email' => 'admin@pestakita.test'],
            [
                'name' => 'Admin Pesta Kita',
                'password' => 'password',
                'role' => Role::Admin,
                'phone' => null,
                'status' => UserStatus::Aktif,
            ],
        );

        $klien = User::firstOrCreate(
            ['email' => 'klien@pestakita.test'],
            [
                'name' => 'Rina Putri',
                'password' => 'password',
                'role' => Role::Klien,
                'phone' => '081234567890',
                'status' => UserStatus::Aktif,
            ],
        );
        $klien->clientProfile()->firstOrCreate([]);

        $vendor = User::firstOrCreate(
            ['email' => 'vendor@pestakita.test'],
            [
                'name' => 'Sari Wulandari',
                'password' => 'password',
                'role' => Role::Vendor,
                'phone' => '081234567891',
                'status' => UserStatus::Aktif,
            ],
        );
        $vendor->vendorProfile()->firstOrCreate([], [
            'business_name' => 'Dapur Nusantara',
            'category' => 'Katering & F&B',
            'address' => 'Jl. Ijen 8, Malang',
            'pic_name' => 'Sari Wulandari',
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);
        $vendor->vendorProfile->update([
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);

        $venue = User::firstOrCreate(
            ['email' => 'venue@pestakita.test'],
            [
                'name' => 'Budi Santoso',
                'password' => 'password',
                'role' => Role::Venue,
                'phone' => '081234567892',
                'status' => UserStatus::Aktif,
            ],
        );
        $venue->venueProfile()->firstOrCreate([], [
            'business_name' => 'Graha Melati',
            'category' => 'Venue / Gedung pertemuan',
            'address' => 'Jl. Ijen 8, Malang',
            'pic_name' => 'Budi Santoso',
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);
        $venue->venueProfile->update([
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);

        // ---- BE-2: data domain non-finansial demo (idempoten) ----
        $vendorProfile = $vendor->vendorProfile;
        $venueProfile = $venue->venueProfile;

        // Katalog Vendor: satu layanan dan satu peralatan (B15 satu tabel).
        $katering = $vendorProfile->catalogItems()->firstOrCreate(
            ['name' => 'Paket Katering Nusantara'],
            [
                'kind' => CatalogKind::Layanan,
                'category' => 'Katering & F&B',
                'description' => 'Paket katering prasmanan untuk 300 tamu.',
                'catalog_price' => 15_000_000,
                'pricing_mode' => PricingMode::Tetap,
                'max_per_day' => null,
                'status' => CatalogItemStatus::Aktif,
                'policies' => 'DP dan pelunasan mengikuti ketentuan platform.',
            ],
        );
        $kursi = $vendorProfile->catalogItems()->firstOrCreate(
            ['name' => 'Sewa Kursi Tiffany'],
            [
                'kind' => CatalogKind::Peralatan,
                'category' => 'Peralatan Pesta',
                'description' => 'Kursi Tiffany putih, harga per unit per hari.',
                'catalog_price' => 15_000,
                'pricing_mode' => PricingMode::Tetap,
                'max_per_day' => 500,
                'status' => CatalogItemStatus::Aktif,
                'policies' => 'Pengiriman dan penjemputan termasuk area Malang.',
            ],
        );

        // Venue beserta ruangan dan fasilitas.
        $graha = $venueProfile->venues()->firstOrCreate(
            ['name' => 'Graha Melati'],
            [
                'kind' => 'Ballroom',
                'capacity' => 500,
                'address' => 'Jl. Ijen 8, Malang',
                'description' => 'Gedung pertemuan dengan ballroom dan aula.',
                'rules' => 'Tidak diperbolehkan membawa kembang api.',
                'cancellation_policy' => 'Pembatalan H-30 dikenakan biaya 50%.',
                'rental_price' => 25_000_000,
                'cleaning_fee' => 500_000,
                'duration_hours' => 8,
                'deposit_amount' => null,
                'verification_status' => VerificationStatus::Terverifikasi,
                'verified_at' => now(),
            ],
        );
        $graha->update([
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);
        $ballroom = $graha->rooms()->firstOrCreate(
            ['name' => 'Ballroom Utama'],
            ['capacity' => 400],
        );
        $graha->rooms()->firstOrCreate(
            ['name' => 'Aula Serbaguna'],
            ['capacity' => 150],
        );
        foreach (['Sound System', 'Proyektor', 'Area Parkir', 'Pendingin Ruangan'] as $fasilitas) {
            $graha->facilities()->firstOrCreate(['name' => $fasilitas]);
        }

        // Ketersediaan manual ruangan (B11: tanpa status booked).
        foreach (['2026-12-12', '2026-12-13', '2026-12-14'] as $tanggal) {
            $ballroom->availabilityDays()->firstOrCreate(
                ['date' => $tanggal],
                ['status' => AvailabilityStatus::Tersedia],
            );
        }

        // Acara diajukan milik Klien dengan pesanan menunggu_review.
        $acara = $klien->events()->firstOrCreate(
            ['name' => 'Resepsi Pernikahan Rina & Dimas'],
            [
                'event_date' => '2026-12-12',
                'guest_count' => 300,
                'budget' => 50_000_000,
                'theme' => 'Elegan',
                'description' => 'Resepsi pernikahan dengan nuansa elegan.',
                'status' => EventStatus::Diajukan,
            ],
        );
        $acara->update([
            'status' => EventStatus::Diajukan,
        ]);

        $acara->orderItems()->updateOrCreate(
            ['subject_type' => 'catalog_item', 'subject_id' => $katering->id],
            [
                'provider_type' => 'vendor_profile',
                'provider_id' => $vendorProfile->id,
                'usage_date' => '2026-12-12',
                'quantity' => 1,
                'catalog_price_snapshot' => 15_000_000,
                'provider_price' => 15_000_000,
                'commission' => 1_500_000,
                'status' => OrderItemStatus::MenungguReview,
                'review_due_at' => now()->addHours(24),
            ],
        );

        $acara->orderItems()->updateOrCreate(
            ['subject_type' => 'catalog_item', 'subject_id' => $kursi->id],
            [
                'provider_type' => 'vendor_profile',
                'provider_id' => $vendorProfile->id,
                'usage_date' => '2026-12-12',
                'quantity' => 100,
                'catalog_price_snapshot' => 15_000,
                'provider_price' => 15_000,
                'commission' => (int) round((15_000 * 100 * 10) / 100, 0, PHP_ROUND_HALF_UP),
                'status' => OrderItemStatus::MenungguReview,
                'review_due_at' => now()->addHours(24),
            ],
        );

        $acara->orderItems()->updateOrCreate(
            ['subject_type' => 'venue_room', 'subject_id' => $ballroom->id],
            [
                'provider_type' => 'venue_profile',
                'provider_id' => $venueProfile->id,
                'usage_date' => '2026-12-12',
                'quantity' => 1,
                'catalog_price_snapshot' => 25_000_000,
                'provider_price' => 25_000_000,
                'commission' => 2_500_000,
                'status' => OrderItemStatus::MenungguReview,
                'review_due_at' => now()->addHours(24),
                'hold_key' => 'venue:'.$graha->id.':2026-12-12',
            ],
        );

        // Parameter pengaturan bawaan sistem EMS (§1.12)
        Setting::set('commission_percent', 10);
        Setting::set('payment_deadline_hours', 48);
        Setting::set('objection_window_hours', 24);
        Setting::set('admin_review_sla_hours', 24);
        Setting::set('unique_code_enabled', true);
        Setting::set('refund_tiers', [
            ['min_days' => 30, 'percent' => 90],
            ['min_days' => 14, 'percent' => 70],
            ['min_days' => 7,  'percent' => 50],
            ['min_days' => 3,  'percent' => 25],
            ['min_days' => 0,  'percent' => 0],
        ]);
        Setting::set('budget_allocation', [
            'venue' => 35,
            'catering' => 30,
            'dekorasi' => 15,
            'hiburan' => 10,
            'peralatan' => 5,
            'cadangan' => 5,
        ]);

        // Rekening bank perusahaan untuk pembayaran klien (Fase 5)
        CompanyBankAccount::firstOrCreate(
            ['account_number' => '1234567890'],
            [
                'bank_name' => 'BCA',
                'account_name' => 'PT Pesta Kita Indonesia',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        CompanyBankAccount::firstOrCreate(
            ['account_number' => '9876543210'],
            [
                'bank_name' => 'Bank Mandiri',
                'account_name' => 'PT Pesta Kita Indonesia',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $this->command?->info('Akun demo local siap: admin/klien/vendor/venue@pestakita.test (sandi: password). Data domain BE-2 (katalog, venue, acara, settings) ikut disiapkan.');
    }
}
