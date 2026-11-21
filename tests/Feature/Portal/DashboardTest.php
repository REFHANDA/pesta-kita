<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring beranda portal: ringkasan bersumber dari database akun login,
 * bukan data contoh prototipe. Fitur yang backend-nya belum ada diberi
 * catatan jujur, bukan angka palsu.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_klien_dashboard_shows_real_account_and_events(): void
    {
        $user = User::factory()->klien()->create(['name' => 'Rina Asli']);

        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Acara Nyata Klien',
            'status' => EventStatus::Terkonfirmasi,
        ]);
        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Acara Draft Klien',
            'status' => EventStatus::Draft,
        ]);
        Event::factory()->create(['name' => 'Acara Klien Lain']);

        $this->actingAs($user)->get('/klien')
            ->assertOk()
            ->assertSee('Rina Asli')
            ->assertSee('Acara Nyata Klien')
            ->assertSee('Acara Draft Klien')
            ->assertSee('Terkonfirmasi')
            ->assertDontSee('Acara Klien Lain')
            ->assertDontSee('Ulang Tahun ke-7 Nadia')
            ->assertDontSee('PK/2026/11/0007');
    }

    public function test_vendor_dashboard_shows_business_and_own_items(): void
    {
        $user = User::factory()->vendor()->create();
        $user->vendorProfile->update(['business_name' => 'Dapur Asli']);

        CatalogItem::factory()->create([
            'vendor_profile_id' => $user->vendorProfile->id,
            'name' => 'Layanan Asli Vendor',
        ]);
        CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $user->vendorProfile->id,
            'name' => 'Peralatan Asli Vendor',
        ]);
        CatalogItem::factory()->create(['name' => 'Item Vendor Lain']);

        $this->actingAs($user)->get('/vendor')
            ->assertOk()
            ->assertSee('Dapur Asli')
            ->assertSee('Layanan Asli Vendor')
            ->assertSee('Peralatan Asli Vendor')
            ->assertSee('menunggu review Admin')
            ->assertDontSee('Item Vendor Lain')
            ->assertDontSee('Dapur Nusantara')
            ->assertDontSee('Rp 4.250.000');
    }

    public function test_venue_dashboard_shows_venues_and_rooms(): void
    {
        $user = User::factory()->venue()->create();
        $user->venueProfile->update(['business_name' => 'Graha Asli']);

        $venue = Venue::factory()->terverifikasi()->create([
            'venue_profile_id' => $user->venueProfile->id,
            'name' => 'Venue Asli Pemilik',
        ]);
        $venue->rooms()->create(['name' => 'Ballroom Utama', 'capacity' => 200]);
        Venue::factory()->create(['name' => 'Venue Pemilik Lain']);

        $this->actingAs($user)->get('/venue')
            ->assertOk()
            ->assertSee('Graha Asli')
            ->assertSee('Venue Asli Pemilik')
            ->assertDontSee('Venue Pemilik Lain')
            ->assertDontSee('Graha Melati')
            ->assertDontSee('Rp 2.720.000');
    }

    public function test_admin_dashboard_shows_real_counts_and_verification_queue(): void
    {
        User::factory()->klien()->count(2)->create();
        VendorProfile::factory()->menunggu()->create();
        VenueProfile::factory()->menunggu()->create();
        VendorProfile::factory()->terverifikasi()->create();
        Event::factory()->create();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Penyedia menunggu verifikasi')
            ->assertSeeText('2 menunggu')
            ->assertSee(route('admin.penyedia.index'))
            ->assertDontSee('Rp 18.450.000')
            ->assertDontSee('Tagihan lunas');
    }

    public function test_dashboards_reject_guests_and_wrong_roles(): void
    {
        foreach (['/klien', '/vendor', '/venue', '/admin'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $klien = User::factory()->klien()->create();
        $this->actingAs($klien)->get('/admin')->assertForbidden();
        $this->actingAs($klien)->get('/vendor')->assertForbidden();
        $this->actingAs($klien)->get('/venue')->assertForbidden();

        $vendor = User::factory()->vendor()->create();
        $this->actingAs($vendor)->get('/klien')->assertForbidden();
        $this->actingAs($vendor)->get('/admin')->assertForbidden();
    }

    public function test_vendor_dashboard_shows_rejected_verification_note(): void
    {
        $user = User::factory()->vendor()->create();
        $user->vendorProfile->update([
            'verification_status' => VerificationStatus::Ditolak,
            'verification_note' => 'KTP buram, unggah ulang.',
        ]);

        $this->actingAs($user)->get('/vendor')
            ->assertOk()
            ->assertSee('KTP buram, unggah ulang.')
            ->assertSee('Perbaiki verifikasi');
    }

    public function test_layout_topbar_shows_real_identity_and_logout_form(): void
    {
        $user = User::factory()->klien()->create([
            'name' => 'Klien Topbar',
            'email' => 'topbar@example.test',
        ]);

        $this->actingAs($user)->get('/klien')
            ->assertOk()
            ->assertSee('Klien Topbar')
            ->assertSee('topbar@example.test')
            ->assertSee('<form method="POST" action="'.url('/logout').'">', false)
            ->assertDontSee('Rina Putri')
            ->assertDontSee('klien@pestakita.test');
    }

    public function test_portal_login_pages_link_to_real_registration(): void
    {
        $this->get('/klien/login')
            ->assertOk()
            ->assertSee(url('/register'))
            ->assertDontSee(url('/klien/register'));

        $this->get('/vendor/login')
            ->assertOk()
            ->assertSee(url('/register'))
            ->assertDontSee(url('/vendor/registrasi-penyedia'));
    }

    public function test_halaman_registrasi_lama_portal_sudah_dihapus(): void
    {
        $this->actingAs(User::factory()->klien()->create())->get('/klien/register')->assertNotFound();
        $this->actingAs(User::factory()->vendor()->create())->get('/vendor/registrasi-penyedia')->assertNotFound();
    }
}
