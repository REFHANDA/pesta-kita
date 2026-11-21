<?php

namespace Tests\Feature\Venue;

use App\Enums\VerificationStatus;
use App\Models\AvailabilityDay;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueFacility;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * BE-3 langkah 5: CRUD venue + ruangan/fasilitas, whitelist cleaning_fee
 * (hanya Admin), status venue individual oleh Admin, soft delete, dan
 * perlindungan ruangan yang sudah dipakai pesanan/penawaran.
 */
class VenueCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload venue valid dengan satu ruangan.
     *
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Graha Melati',
            'kind' => 'Ballroom',
            'capacity' => 500,
            'address' => 'Jl. Melati No. 1',
            'description' => 'Gedung serbaguna.',
            'rules' => 'Dilarang merokok.',
            'cancellation_policy' => 'DP hangus bila batal H-7.',
            'rental_price' => 25_000_000,
            'duration_hours' => 8,
            'deposit_amount' => null,
            'rooms' => [
                ['name' => 'Ballroom Utama', 'capacity' => 500],
            ],
            'facilities' => ['Parkir', 'Sound system'],
        ], $overrides);
    }

    // ---- Create ----

    public function test_pemilik_membuat_venue_dengan_ruangan_dan_fasilitas(): void
    {
        $owner = User::factory()->venue()->create();

        $this->actingAs($owner)->post('/venue/data', $this->payload())
            ->assertRedirect(route('venue.data'));

        $venue = Venue::first();
        $this->assertSame($owner->venueProfile->id, $venue->venue_profile_id);
        $this->assertSame(VerificationStatus::Menunggu, $venue->verification_status, 'Venue baru mengikuti verifikasi profil.');
        $this->assertNull($venue->cleaning_fee, 'B4: cleaning fee tidak diisi pemilik.');
        $this->assertSame(1, $venue->rooms()->count());
        $this->assertSame(2, $venue->facilities()->count());
    }

    public function test_cleaning_fee_dari_pemilik_diabaikan(): void
    {
        $owner = User::factory()->venue()->create();

        $this->actingAs($owner)->post('/venue/data', $this->payload([
            'cleaning_fee' => 999_999,
            'verification_status' => 'terverifikasi',
            'venue_profile_id' => 999,
        ]));

        $venue = Venue::first();
        $this->assertNull($venue->cleaning_fee, 'Keputusan butir 8: cleaning fee hanya Admin.');
        $this->assertSame(VerificationStatus::Menunggu, $venue->verification_status);
        $this->assertSame($owner->venueProfile->id, $venue->venue_profile_id);
    }

    public function test_validasi_venue_gagal_tanpa_ruangan_atau_harga_valid(): void
    {
        $owner = User::factory()->venue()->create();

        $this->actingAs($owner)->post('/venue/data', $this->payload([
            'rooms' => [],
            'rental_price' => -5,
        ]))->assertSessionHasErrors(['rooms', 'rental_price']);

        $this->actingAs($owner)->post('/venue/data', $this->payload([
            'rooms' => [['name' => 'A'], ['name' => 'A']],
        ]))->assertSessionHasErrors('rooms.0.name');

        $this->assertSame(0, Venue::count());
    }

    public function test_role_lain_ditolak_membuat_venue(): void
    {
        foreach ([User::factory()->klien()->create(), User::factory()->vendor()->create()] as $user) {
            $this->actingAs($user)->post('/venue/data', $this->payload())->assertForbidden();
        }
        $this->assertSame(0, Venue::count());
    }

    // ---- Read ----

    public function test_halaman_data_memuat_venue_milik_sendiri(): void
    {
        $owner = User::factory()->venue()->create();
        $mine = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        Venue::factory()->create(); // venue lain

        $this->actingAs($owner)->get('/venue/data')
            ->assertOk()
            ->assertViewHas('venues', fn ($venues) => $venues->pluck('id')->all() === [$mine->id]);
    }

    public function test_form_tambah_dan_ubah_venue(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->actingAs($owner)->get('/venue/form')->assertOk()->assertViewHas('venue', null);
        $this->actingAs($owner)->get("/venue/form/{$venue->id}")->assertOk()->assertViewHas('venue');
    }

    public function test_form_ubah_venue_lain_ditolak(): void
    {
        $intruder = User::factory()->venue()->create();
        $venue = Venue::factory()->create();

        $this->actingAs($intruder)->get("/venue/form/{$venue->id}")->assertForbidden();
    }

    // ---- Update ----

    public function test_pemilik_memperbarui_venue_dan_sinkron_fasilitas(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        VenueFacility::create(['venue_id' => $venue->id, 'name' => 'Lama']);

        $this->actingAs($owner)->put("/venue/data/{$venue->id}", $this->payload([
            'name' => 'Graha Melati Baru',
            'facilities' => ['Parkir', 'AC'],
        ]))->assertRedirect();

        $venue->refresh();
        $this->assertSame('Graha Melati Baru', $venue->name);
        $this->assertEqualsCanonicalizing(['Parkir', 'AC'], $venue->facilities()->pluck('name')->all());
    }

    public function test_update_pemilik_tidak_mengubah_cleaning_fee_atau_status(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $owner->venueProfile->id,
            'cleaning_fee' => 500_000,
        ]);

        $this->actingAs($owner)->put("/venue/data/{$venue->id}", $this->payload([
            'cleaning_fee' => 1,
            'verification_status' => 'terverifikasi',
        ]));

        $venue->refresh();
        $this->assertSame(500_000, $venue->cleaning_fee);
        $this->assertSame(VerificationStatus::Menunggu, $venue->verification_status);
    }

    public function test_pemilik_lain_ditolak_memperbarui_venue(): void
    {
        $intruder = User::factory()->venue()->create();
        $venue = Venue::factory()->create();

        $this->actingAs($intruder)->put("/venue/data/{$venue->id}", $this->payload())->assertForbidden();
    }

    // ---- Ruangan ----

    public function test_pemilik_menambah_dan_memperbarui_ruangan(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->actingAs($owner)->post("/venue/data/{$venue->id}/ruangan", [
            'name' => 'Ruang VIP', 'capacity' => 50,
        ])->assertRedirect();

        $room = $venue->rooms()->first();
        $this->assertSame('Ruang VIP', $room->name);

        $this->actingAs($owner)->put("/venue/ruangan/{$room->id}", [
            'name' => 'Ruang VIP Utama', 'capacity' => 60,
        ])->assertRedirect();
        $this->assertSame('Ruang VIP Utama', $room->fresh()->name);
    }

    public function test_nama_ruangan_unik_per_venue(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Utama']);

        $this->actingAs($owner)->post("/venue/data/{$venue->id}/ruangan", ['name' => 'Utama'])
            ->assertSessionHasErrors('name');
        $this->assertSame(1, $venue->rooms()->count());
    }

    public function test_ruangan_terpakai_pesanan_tidak_dapat_dihapus(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);
        OrderItem::factory()->create([
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $owner->venueProfile->id,
        ]);

        $this->actingAs($owner)->delete("/venue/ruangan/{$room->id}")
            ->assertSessionHasErrors('room');
        $this->assertDatabaseHas('venue_rooms', ['id' => $room->id]);
    }

    public function test_hapus_ruangan_membersihkan_ketersediaan_harian(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);
        AvailabilityDay::factory()->count(2)->create(['subject_type' => 'venue_room', 'subject_id' => $room->id]);

        $this->actingAs($owner)->delete("/venue/ruangan/{$room->id}")->assertRedirect();

        $this->assertDatabaseMissing('venue_rooms', ['id' => $room->id]);
        $this->assertSame(0, AvailabilityDay::where('subject_type', 'venue_room')->where('subject_id', $room->id)->count());
    }

    public function test_pemilik_lain_ditolak_mengelola_ruangan(): void
    {
        $intruder = User::factory()->venue()->create();
        $venue = Venue::factory()->create();
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($intruder)->post("/venue/data/{$venue->id}/ruangan", ['name' => 'X'])->assertForbidden();
        $this->actingAs($intruder)->put("/venue/ruangan/{$room->id}", ['name' => 'Y'])->assertForbidden();
        $this->actingAs($intruder)->delete("/venue/ruangan/{$room->id}")->assertForbidden();
    }

    // ---- Hapus venue ditiadakan ----

    public function test_route_hapus_venue_tidak_ada_lagi(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->assertFalse(Route::has('venue.data.destroy'));
        $this->actingAs($owner)->delete("/venue/data/{$venue->id}")->assertMethodNotAllowed();
        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'deleted_at' => null]);
    }

    // ---- Admin: cleaning fee & status individual ----

    public function test_admin_mengubah_cleaning_fee_dan_status_venue(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->actingAs($admin)->post("/admin/venue/{$venue->id}", [
            'cleaning_fee' => 750_000,
            'verification_status' => 'terverifikasi',
        ])->assertRedirect();

        $venue->refresh();
        $this->assertSame(750_000, $venue->cleaning_fee);
        $this->assertSame(VerificationStatus::Terverifikasi, $venue->verification_status);
        $this->assertNotNull($venue->verified_at);
        // Status profil penyedia tidak tersentuh (T2 & keputusan butir 3).
        $this->assertSame(VerificationStatus::Menunggu, $owner->venueProfile->fresh()->verification_status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'venue_admin_update',
            'subject_type' => 'venue',
            'subject_id' => $venue->id,
        ]);
    }

    public function test_admin_menonaktifkan_venue_individual(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->terverifikasi()->create();

        $this->actingAs($admin)->post("/admin/venue/{$venue->id}", [
            'cleaning_fee' => null,
            'verification_status' => 'nonaktif',
        ]);

        $this->assertSame(VerificationStatus::Nonaktif, $venue->fresh()->verification_status);
        $this->assertSame(0, Venue::publikUntukKlien()->count());
    }

    public function test_pemilik_tidak_bisa_memakai_endpoint_admin_venue(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->actingAs($owner)->post("/admin/venue/{$venue->id}", [
            'cleaning_fee' => 1,
            'verification_status' => 'terverifikasi',
        ])->assertForbidden();
    }

    public function test_status_venue_tidak_valid_ditolak_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create();

        $this->actingAs($admin)->post("/admin/venue/{$venue->id}", [
            'verification_status' => 'menunggu',
        ])->assertSessionHasErrors('verification_status');
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $this->post('/venue/data', $this->payload())->assertRedirect('/login');
        $this->get('/venue/data')->assertRedirect('/login');
    }
}
