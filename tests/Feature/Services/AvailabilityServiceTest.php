<?php

namespace Tests\Feature\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
    }

    public function test_equipment_stock_is_calculated_correctly_with_holding_orders(): void
    {
        $vendorProfile = VendorProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $item = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'max_per_day' => 10,
        ]);

        $date = '2026-12-15';

        // 1. Awalnya stok penuh: 10
        $this->assertEquals(10, $this->service->getAvailableStock($item, $date));
        $this->assertTrue($this->service->isCatalogItemAvailable($item, $date, 10));

        // 2. Pesanan berstatus menunggu_review (menahan 4 unit)
        OrderItem::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'usage_date' => $date,
            'quantity' => 4,
            'status' => OrderItemStatus::MenungguReview,
        ]);

        $this->assertEquals(6, $this->service->getAvailableStock($item, $date));

        // 3. Pesanan berstatus disetujui (menahan 3 unit)
        OrderItem::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'usage_date' => $date,
            'quantity' => 3,
            'status' => OrderItemStatus::Disetujui,
        ]);

        $this->assertEquals(3, $this->service->getAvailableStock($item, $date));

        // 4. Pesanan lain berstatus ditolak / dibatalkan / draft TIDAK mengurangi stok
        OrderItem::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'usage_date' => $date,
            'quantity' => 5,
            'status' => OrderItemStatus::Ditolak,
        ]);
        OrderItem::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'usage_date' => $date,
            'quantity' => 2,
            'status' => OrderItemStatus::Dibatalkan,
        ]);
        OrderItem::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'usage_date' => $date,
            'quantity' => 2,
            'status' => OrderItemStatus::Draft,
        ]);

        // Sisa stok tetap 3
        $this->assertEquals(3, $this->service->getAvailableStock($item, $date));
        $this->assertTrue($this->service->isCatalogItemAvailable($item, $date, 3));
        $this->assertFalse($this->service->isCatalogItemAvailable($item, $date, 4));
    }

    public function test_venue_one_date_one_holding_order_rule(): void
    {
        $venueProfile = VenueProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $room1 = VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Ballroom Utama']);
        $room2 = VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Garden Area']);

        $date = '2026-12-20';

        // Sebelum ada pesanan, kedua ruangan tersedia
        $this->assertTrue($this->service->isVenueRoomAvailable($room1, $date));
        $this->assertTrue($this->service->isVenueRoomAvailable($room2, $date));

        // Buat pesanan menahan pada room1
        $holdKey = $this->service->makeVenueHoldKey((int) $venue->id, $date);
        $orderItem = OrderItem::factory()->untukVenueRoom()->create([
            'provider_id' => $venueProfile->id,
            'subject_id' => $room1->id,
            'usage_date' => $date,
            'status' => OrderItemStatus::MenungguReview,
            'hold_key' => $holdKey,
        ]);

        // Seluruh venue terkunci pada tanggal tersebut: room1 & room2 tidak tersedia
        $this->assertFalse($this->service->isVenueRoomAvailable($room1, $date));
        $this->assertFalse($this->service->isVenueRoomAvailable($room2, $date));

        // Pesanan pada tanggal lain tetap tersedia
        $this->assertTrue($this->service->isVenueRoomAvailable($room1, '2026-12-21'));

        // Jika pesanan ditolak / dibatalkan dan hold dilepas, slot kembali terbuka
        $orderItem->update([
            'status' => OrderItemStatus::Ditolak,
            'hold_key' => null,
        ]);

        $this->assertTrue($this->service->isVenueRoomAvailable($room1, $date));
        $this->assertTrue($this->service->isVenueRoomAvailable($room2, $date));
    }

    public function test_manual_unavailable_day_blocks_booking(): void
    {
        $vendorProfile = VendorProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $item = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'max_per_day' => 20,
        ]);

        $date = '2026-12-25';

        AvailabilityDay::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'date' => $date,
            'status' => AvailabilityStatus::TidakTersedia,
        ]);

        $this->assertEquals(0, $this->service->getAvailableStock($item, $date));
        $this->assertFalse($this->service->isCatalogItemAvailable($item, $date, 1));
    }

    public function test_two_clients_competing_for_last_venue_slot_only_one_succeeds(): void
    {
        // Siapkan Admin untuk menerima notifikasi
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

        $eventDate = now()->addDays(30)->format('Y-m-d');

        // Klien A
        $clientA = User::factory()->klien()->create();
        $eventA = Event::factory()->create([
            'client_id' => $clientA->id,
            'event_date' => $eventDate,
            'status' => EventStatus::Draft,
        ]);
        $orderItemA = OrderItem::factory()->untukVenueRoom()->draft()->create([
            'event_id' => $eventA->id,
            'subject_id' => $room->id,
            'provider_id' => $venueProfile->id,
        ]);

        // Klien B
        $clientB = User::factory()->klien()->create();
        $eventB = Event::factory()->create([
            'client_id' => $clientB->id,
            'event_date' => $eventDate,
            'status' => EventStatus::Draft,
        ]);
        $orderItemB = OrderItem::factory()->untukVenueRoom()->draft()->create([
            'event_id' => $eventB->id,
            'subject_id' => $room->id,
            'provider_id' => $venueProfile->id,
        ]);

        // Klien A mengajukan acara lebih dulu -> BERHASIL
        $this->service->validateAndSubmitEvent($eventA);

        $eventA->refresh();
        $orderItemA->refresh();
        $this->assertEquals(EventStatus::Diajukan, $eventA->status);
        $this->assertEquals(OrderItemStatus::MenungguReview, $orderItemA->status);
        $this->assertEquals($this->service->makeVenueHoldKey((int) $venue->id, $eventDate), $orderItemA->hold_key);

        // Klien B mencoba mengajukan acara untuk venue dan tanggal yang sama -> GAGAL
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Venue '{$venue->name}' tidak tersedia pada tanggal {$eventDate}.");

        $this->service->validateAndSubmitEvent($eventB);
    }
}
