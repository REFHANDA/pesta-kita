<?php

namespace Tests\Feature\Portal;

use App\Enums\CatalogItemStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Notifications\NewOrderSubmittedNotification;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KlienEventBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_klien_can_add_catalog_item_to_draft_event_without_holding_slot(): void
    {
        $klien = User::factory()->klien()->create();
        $eventDate = now()->addDays(20)->format('Y-m-d');
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'event_date' => $eventDate,
            'status' => EventStatus::Draft,
        ]);

        $vendorProfile = VendorProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $item = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'catalog_price' => 250_000,
            'max_per_day' => 10,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $response = $this->actingAs($klien)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'quantity' => 4,
        ]);

        $response->assertRedirect(route('klien.acara.detail', $event));

        $orderItem = OrderItem::where('event_id', $event->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals(OrderItemStatus::Draft, $orderItem->status);
        $this->assertEquals(4, $orderItem->quantity);
        $this->assertEquals(250_000, $orderItem->catalog_price_snapshot);
        $this->assertEquals(250_000, $orderItem->provider_price);
        $this->assertNull($orderItem->hold_key);

        // Belum menahan slot: sisa stok tetap 10
        $availabilityService = app(AvailabilityService::class);
        $this->assertEquals(10, $availabilityService->getAvailableStock($item, $eventDate));
    }

    public function test_klien_can_add_venue_room_to_draft_event(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Draft,
        ]);

        $venueProfile = VenueProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
            'rental_price' => 5_000_000,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $response = $this->actingAs($klien)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
        ]);

        $response->assertRedirect(route('klien.acara.detail', $event));

        $orderItem = OrderItem::where('event_id', $event->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals(OrderItemStatus::Draft, $orderItem->status);
        $this->assertEquals(5_000_000, $orderItem->catalog_price_snapshot);
        $this->assertNull($orderItem->hold_key);
    }

    public function test_klien_can_remove_item_from_draft_event(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Draft,
        ]);

        $orderItem = OrderItem::factory()->draft()->create([
            'event_id' => $event->id,
        ]);

        $response = $this->actingAs($klien)->delete(route('klien.acara.pesanan.destroy', [$event, $orderItem]));

        $response->assertRedirect(route('klien.acara.detail', $event));
        $this->assertDatabaseMissing('order_items', ['id' => $orderItem->id]);
    }

    public function test_klien_submits_event_successfully_locks_availability_and_notifies_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => Role::Admin,
            'status' => UserStatus::Aktif,
        ]);

        $klien = User::factory()->klien()->create();
        $eventDate = now()->addDays(15)->format('Y-m-d');
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'event_date' => $eventDate,
            'status' => EventStatus::Draft,
        ]);

        $venueProfile = VenueProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
            'rental_price' => 7_500_000,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $orderItem = OrderItem::factory()->untukVenueRoom()->draft()->create([
            'event_id' => $event->id,
            'provider_id' => $venueProfile->id,
            'subject_id' => $room->id,
        ]);

        $response = $this->actingAs($klien)->post(route('klien.acara.ajukan', $event));

        $response->assertRedirect(route('klien.acara.detail', $event));
        $response->assertSessionHas('success');

        $event->refresh();
        $orderItem->refresh();

        $this->assertEquals(EventStatus::Diajukan, $event->status);
        $this->assertEquals(OrderItemStatus::MenungguReview, $orderItem->status);
        $this->assertEquals($eventDate, $orderItem->usage_date->format('Y-m-d'));
        $this->assertEquals(7_500_000, $orderItem->provider_price);
        $this->assertNotNull($orderItem->review_due_at);
        $this->assertEquals("venue:{$venue->id}:{$eventDate}", $orderItem->hold_key);

        Notification::assertSentTo($admin, NewOrderSubmittedNotification::class);
    }

    public function test_two_clients_competing_via_http_only_one_succeeds(): void
    {
        User::factory()->create([
            'role' => Role::Admin,
            'status' => UserStatus::Aktif,
        ]);

        $venueProfile = VenueProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
            'rental_price' => 10_000_000,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $date = now()->addDays(20)->format('Y-m-d');

        $client1 = User::factory()->klien()->create();
        $event1 = Event::factory()->create([
            'client_id' => $client1->id,
            'event_date' => $date,
            'status' => EventStatus::Draft,
        ]);
        OrderItem::factory()->untukVenueRoom()->draft()->create([
            'event_id' => $event1->id,
            'provider_id' => $venueProfile->id,
            'subject_id' => $room->id,
        ]);

        $client2 = User::factory()->klien()->create();
        $event2 = Event::factory()->create([
            'client_id' => $client2->id,
            'event_date' => $date,
            'status' => EventStatus::Draft,
        ]);
        OrderItem::factory()->untukVenueRoom()->draft()->create([
            'event_id' => $event2->id,
            'provider_id' => $venueProfile->id,
            'subject_id' => $room->id,
        ]);

        // Klien 1 mengajukan acara -> Berhasil
        $response1 = $this->actingAs($client1)->post(route('klien.acara.ajukan', $event1));
        $response1->assertRedirect(route('klien.acara.detail', $event1));
        $response1->assertSessionHas('success');

        $event1->refresh();
        $this->assertEquals(EventStatus::Diajukan, $event1->status);

        // Klien 2 mengajukan acara untuk venue dan tanggal yang sama -> Gagal & sesi ada error
        $response2 = $this->actingAs($client2)->post(route('klien.acara.ajukan', $event2));
        $response2->assertRedirect(route('klien.acara.detail', $event2));
        $response2->assertSessionHas('error');

        $event2->refresh();
        $this->assertEquals(EventStatus::Draft, $event2->status);
    }
}
