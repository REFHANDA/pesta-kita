<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRundownReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_add_a_rundown_segment_to_an_owned_confirmed_event(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
        ]);

        $response = $this->actingAs($client)->post(route('klien.rundown.store', $event), [
            'title' => 'Registrasi tamu',
            'starts_at' => '10:00',
            'duration_minutes' => 30,
            'notes' => 'Buka meja penerima tamu.',
        ]);

        $response->assertRedirect(route('klien.rundown', $event));
        $this->assertDatabaseHas('rundown_items', [
            'event_id' => $event->id,
            'created_by' => $client->id,
            'order_item_id' => null,
            'title' => 'Registrasi tamu',
            'starts_at' => '10:00',
            'duration_minutes' => 30,
        ]);
    }

    public function test_client_cannot_view_another_clients_rundown(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create();

        $this->actingAs($client)->get(route('klien.rundown', $event))
            ->assertNotFound();
    }

    public function test_vendor_can_add_only_a_segment_for_its_own_locked_order(): void
    {
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile()->firstOrFail();
        $event = Event::factory()->create(['status' => EventStatus::Terkonfirmasi]);
        $catalogItem = CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catalogItem->id,
            'status' => OrderItemStatus::Mengikat,
        ]);

        $response = $this->actingAs($vendor)->post(route('vendor.rundown.store', $event), [
            'order_item_id' => $orderItem->id,
            'title' => 'Persiapan prasmanan',
            'starts_at' => '11:00',
            'duration_minutes' => 45,
        ]);

        $response->assertRedirect(route('vendor.rundown', $event));
        $this->assertDatabaseHas('rundown_items', [
            'event_id' => $event->id,
            'order_item_id' => $orderItem->id,
            'created_by' => $vendor->id,
            'title' => 'Persiapan prasmanan',
        ]);
    }

    public function test_admin_can_close_a_paid_event_after_its_event_date(): void
    {
        $this->travelTo('2030-01-02 10:00:00');
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create([
            'status' => EventStatus::Terkonfirmasi,
            'event_date' => '2030-01-01',
        ]);
        $event->invoices()->create([
            'version' => 1,
            'unique_code' => 321,
            'active_code' => null,
            'provider_subtotal' => 1_000_000,
            'commission_total' => 100_000,
            'cleaning_total' => 0,
            'total_amount' => 1_100_321,
            'status' => 'lunas',
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.acara.selesai', $event));

        $response->assertRedirect(route('admin.acara.detail', $event));
        $this->assertSame(EventStatus::Selesai, $event->fresh()->status);
        $this->assertSame('2030-01-02 10:00:00', $event->fresh()->closed_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'event_marked_complete',
            'subject_id' => $event->id,
        ]);
    }

    public function test_completed_paid_event_can_be_reviewed_and_provider_sees_only_aggregate(): void
    {
        $this->travelTo('2030-01-02 10:00:00');
        $client = User::factory()->klien()->create(['name' => 'Klien Rahasia']);
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile()->firstOrFail();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Selesai,
            'event_date' => '2030-01-01',
        ]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'status' => OrderItemStatus::Mengikat,
            'final_price' => 1_000_000,
        ]);
        $event->invoices()->create([
            'version' => 1,
            'unique_code' => 321,
            'active_code' => null,
            'provider_subtotal' => 1_000_000,
            'commission_total' => 100_000,
            'cleaning_total' => 0,
            'total_amount' => 1_100_321,
            'status' => 'lunas',
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($client)->post(route('klien.ulasan.store', [$event, $orderItem]), [
            'rating' => 4,
            'comment' => 'Layanan tepat waktu dan komunikatif.',
        ]);
        $response->assertRedirect(route('klien.acara.detail', $event));
        $review = EventReview::query()->sole();

        $this->assertSame(EventStatus::Dinilai, $event->fresh()->status);
        $this->assertSame(4, $review->rating);

        $this->actingAs($vendor)->get(route('vendor.ulasan'))
            ->assertOk()
            ->assertSee('4,0 / 5')
            ->assertSee('1 ulasan')
            ->assertDontSee('Klien Rahasia')
            ->assertDontSee('Layanan tepat waktu dan komunikatif.');
    }
}
