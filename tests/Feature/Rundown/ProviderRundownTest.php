<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\RundownItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rundown di portal Vendor, Venue, dan Admin (Tahap C3).
 */
class ProviderRundownTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{vendor: User, venue: User, client: User, event: Event, vendorOrder: OrderItem, venueOrder: OrderItem}
     */
    protected function fixture(EventStatus $status = EventStatus::Terkonfirmasi): array
    {
        $client = User::factory()->klien()->create(['name' => 'Klien Rahasia']);
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => $status,
            'event_date' => $status === EventStatus::Selesai ? now()->subDay()->format('Y-m-d') : now()->addDays(9)->format('Y-m-d'),
        ]);

        $itemStatus = match ($status) {
            EventStatus::Selesai, EventStatus::Dievaluasi => OrderItemStatus::Selesai,
            EventStatus::Terkonfirmasi => OrderItemStatus::Terkonfirmasi,
            default => OrderItemStatus::MenungguReview,
        };

        $vendorOrder = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'status' => $itemStatus,
            'provider_price' => 1_500_000,
        ]);

        $venueOrder = OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venue->venueProfile->id,
            'status' => $itemStatus,
            'provider_price' => 2_000_000,
        ]);

        return compact('client', 'vendor', 'venue', 'event', 'vendorOrder', 'venueOrder');
    }

    public function test_vendor_melihat_daftar_acara_dan_menambah_item_miliknya(): void
    {
        $f = $this->fixture();

        $this->actingAs($f['vendor'])->get(route('vendor.rundown'))
            ->assertOk()
            ->assertSee('Acara #'.$f['event']->id);

        $this->actingAs($f['vendor'])->get(route('vendor.rundown.show', $f['event']))
            ->assertOk()
            ->assertSee('Rundown Acara #'.$f['event']->id)
            ->assertDontSee('Klien Rahasia');

        $this->actingAs($f['vendor'])->post(route('vendor.rundown.store', $f['event']), [
            'start_time' => '08:00',
            'end_time' => '09:00',
            'activity' => 'Persiapan katering',
            'order_item_id' => $f['vendorOrder']->id,
        ])->assertRedirect(route('vendor.rundown.show', $f['event']));

        $this->assertDatabaseHas('rundown_items', [
            'event_id' => $f['event']->id,
            'created_by' => $f['vendor']->id,
            'order_item_id' => $f['vendorOrder']->id,
        ]);
    }

    public function test_vendor_tidak_dapat_mengubah_item_klien(): void
    {
        $f = $this->fixture();

        $clientItem = RundownItem::factory()->create([
            'event_id' => $f['event']->id,
            'created_by' => $f['client']->id,
            'activity' => 'Pembukaan',
        ]);

        $this->actingAs($f['vendor'])
            ->put(route('vendor.rundown.update', [$f['event'], $clientItem]), [
                'start_time' => '10:00',
                'activity' => 'Diubah vendor',
                'order_item_id' => $f['vendorOrder']->id,
            ])
            ->assertForbidden();

        $this->actingAs($f['vendor'])
            ->delete(route('vendor.rundown.destroy', [$f['event'], $clientItem]))
            ->assertForbidden();

        $this->assertSame('Pembukaan', $clientItem->fresh()->activity);
    }

    public function test_vendor_tidak_melihat_acara_tanpa_pesanan_miliknya(): void
    {
        $f = $this->fixture();

        $otherVendor = User::factory()->vendor()->create();

        $this->actingAs($otherVendor)->get(route('vendor.rundown'))
            ->assertOk()
            ->assertDontSee('Acara #'.$f['event']->id);

        $this->actingAs($otherVendor)->get(route('vendor.rundown.show', $f['event']))->assertForbidden();
    }

    public function test_vendor_tidak_melihat_acara_dibatalkan_atau_dialihkan(): void
    {
        $f = $this->fixture();

        $f['vendorOrder']->update(['status' => OrderItemStatus::Dibatalkan]);

        $this->actingAs($f['vendor'])->get(route('vendor.rundown'))
            ->assertOk()
            ->assertDontSee('Acara #'.$f['event']->id);

        $this->actingAs($f['vendor'])->get(route('vendor.rundown.show', $f['event']))->assertForbidden();
    }

    public function test_venue_hanya_bisa_melihat_tanpa_route_tulis(): void
    {
        $f = $this->fixture();

        $writeRoutes = collect(Route::getRoutes())->filter(
            fn ($route) => str_starts_with($route->uri(), 'venue/rundown')
                && array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []
        );
        $this->assertCount(0, $writeRoutes, 'Venue tidak boleh memiliki route tulis rundown.');

        RundownItem::factory()->create([
            'event_id' => $f['event']->id,
            'created_by' => $f['client']->id,
            'activity' => 'Pembukaan',
        ]);

        $this->actingAs($f['venue'])->get(route('venue.rundown'))
            ->assertOk()
            ->assertSee('Acara #'.$f['event']->id);

        $this->actingAs($f['venue'])->get(route('venue.rundown.show', $f['event']))
            ->assertOk()
            ->assertSee('Pembukaan')
            ->assertDontSee('Klien Rahasia');

        $this->actingAs($f['venue'])
            ->post(route('vendor.rundown.store', $f['event']), [
                'start_time' => '08:00',
                'activity' => 'Percobaan',
                'order_item_id' => $f['venueOrder']->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_melihat_rundown_tanpa_aksi_tulis(): void
    {
        $f = $this->fixture();

        RundownItem::factory()->create([
            'event_id' => $f['event']->id,
            'created_by' => $f['client']->id,
            'activity' => 'Pembukaan',
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.acara.detail', $f['event']))
            ->assertOk()
            ->assertSee('Pembukaan')
            ->assertDontSee(route('vendor.rundown.store', $f['event']), false);
    }

    public function test_vendor_terkunci_setelah_acara_selesai(): void
    {
        $f = $this->fixture(EventStatus::Selesai);

        $this->actingAs($f['vendor'])->get(route('vendor.rundown.show', $f['event']))
            ->assertOk()
            ->assertSee('Rundown dikunci karena acara sudah selesai.')
            ->assertDontSee(route('vendor.rundown.store', $f['event']), false);

        $this->actingAs($f['vendor'])->post(route('vendor.rundown.store', $f['event']), [
            'start_time' => '08:00',
            'activity' => 'Percobaan',
            'order_item_id' => $f['vendorOrder']->id,
        ])->assertForbidden();
    }
}
