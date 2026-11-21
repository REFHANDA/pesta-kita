<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Notifications\OrderObjectionSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProviderOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('objection_window_hours', 24);
        Setting::set('commission_percent', 10);
    }

    public function test_vendor_sees_event_as_anonymous_event_number_without_client_name(): void
    {
        $vendorUser = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);

        $klien = User::factory()->klien()->create(['name' => 'Sari Rahasia Cantik']);
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'name' => 'Ulang Tahun Rahasia',
            'status' => EventStatus::Diajukan,
        ]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'provider_price' => 2_500_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now(),
            'objection_deadline' => now()->addHours(24),
        ]);

        $response = $this->actingAs($vendorUser)->get(route('vendor.pesanan'));
        $response->assertOk();
        $response->assertSee("Acara #{$event->id}");
        $response->assertDontSee('Sari Rahasia Cantik'); // Aturan §1.1

        $detailResponse = $this->actingAs($vendorUser)->get(route('vendor.pesanan.show', $item));
        $detailResponse->assertOk();
        $detailResponse->assertSee("Acara #{$event->id}");
        $detailResponse->assertDontSee('Sari Rahasia Cantik');
        $detailResponse->assertSee('2.500.000'); // Hak Anda penuh
    }

    public function test_vendor_cannot_view_another_vendors_order(): void
    {
        $vendorUser1 = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile1 = VendorProfile::factory()->create(['user_id' => $vendorUser1->id]);

        $vendorUser2 = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile2 = VendorProfile::factory()->create(['user_id' => $vendorUser2->id]);

        $item = OrderItem::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile2->id,
        ]);

        $response = $this->actingAs($vendorUser1)->get(route('vendor.pesanan.show', $item));
        $response->assertForbidden();
    }

    public function test_vendor_can_submit_objection_during_objection_window(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $vendorUser = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);

        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);
        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(2),
            'objection_deadline' => now()->addHours(22),
        ]);

        $response = $this->actingAs($vendorUser)->post(route('vendor.pesanan.keberatan.store', $item), [
            'category' => 'jadwal_bentrok',
            'reason' => 'Ada pesanan offline skala besar yang sudah dijadwalkan.',
        ]);

        $response->assertRedirect(route('vendor.pesanan.show', $item));
        $response->assertSessionHas('success');

        // Status pesanan TIDAK boleh berubah otomatis
        $item->refresh();
        $this->assertEquals(OrderItemStatus::Disetujui, $item->status);

        // Record keberatan terbentuk
        $objection = OrderObjection::where('order_item_id', $item->id)->first();
        $this->assertNotNull($objection);
        $this->assertEquals(OrderObjectionStatus::Diajukan, $objection->status);
        $this->assertEquals(OrderObjectionCategory::JadwalBentrok, $objection->category);

        Notification::assertSentTo($admin, OrderObjectionSubmittedNotification::class);
    }

    public function test_vendor_cannot_submit_regular_objection_after_deadline_expired(): void
    {
        $vendorUser = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);

        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);
        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(25),
            'objection_deadline' => now()->subHour(), // Lewat batas
        ]);

        $response = $this->actingAs($vendorUser)->post(route('vendor.pesanan.keberatan.store', $item), [
            'category' => 'jadwal_bentrok',
            'reason' => 'Terlambat mengajukan keberatan.',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('order_objections', ['order_item_id' => $item->id]);
    }

    public function test_venue_owner_can_view_orders_and_submit_objection(): void
    {
        $venueUser = User::factory()->create(['role' => Role::Venue]);
        $venueProfile = VenueProfile::factory()->create(['user_id' => $venueUser->id]);
        $venue = Venue::factory()->create(['venue_profile_id' => $venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);
        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(1),
            'objection_deadline' => now()->addHours(23),
        ]);

        $response = $this->actingAs($venueUser)->get(route('venue.pesanan'));
        $response->assertOk();
        $response->assertSee("Acara #{$event->id}");

        $objectionResponse = $this->actingAs($venueUser)->post(route('venue.pesanan.keberatan.store', $item), [
            'category' => 'alat_rusak',
            'reason' => 'Pendingin ruangan ballroom utama sedang perbaikan total.',
        ]);

        $objectionResponse->assertRedirect(route('venue.pesanan.show', $item));
        $this->assertDatabaseHas('order_objections', [
            'order_item_id' => $item->id,
            'category' => 'alat_rusak',
            'status' => 'diajukan',
        ]);
    }
}
