<?php

namespace Tests\Feature\Portal;

use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_object_to_own_order_only_during_the_approved_window(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile()->firstOrFail();
        $event = Event::factory()->create(['name' => 'Acara Rahasia Klien']);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'objection_deadline' => now()->addHours(23),
        ]);

        $this->actingAs($vendor)->get(route('vendor.pesanan.detail', $orderItem))
            ->assertOk()
            ->assertDontSee('Acara Rahasia Klien')
            ->assertDontSee('Nama Klien');

        $this->actingAs($vendor)->post(route('vendor.keberatan.store', $orderItem), [
            'reason' => 'Jadwal layanan bertabrakan.',
        ])->assertRedirect(route('vendor.pesanan.detail', $orderItem));

        $this->assertDatabaseHas('order_objections', [
            'order_item_id' => $orderItem->id,
            'provider_user_id' => $vendor->id,
            'status' => 'diajukan',
        ]);
    }

    public function test_vendor_cannot_object_after_deadline_or_to_another_providers_order(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile()->firstOrFail();
        $otherProfile = $otherVendor->vendorProfile()->firstOrFail();
        $event = Event::factory()->create();
        $expiredOrder = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'objection_deadline' => now()->subSecond(),
        ]);
        $foreignOrder = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $otherProfile->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'objection_deadline' => now()->addHour(),
        ]);

        $this->actingAs($vendor)
            ->post(route('vendor.keberatan.store', $expiredOrder), ['reason' => 'Terlambat.'])
            ->assertSessionHasErrors('order_item');
        $this->post(route('vendor.keberatan.store', $foreignOrder), ['reason' => 'Bukan milik saya.'])
            ->assertNotFound();

        $this->assertDatabaseCount('order_objections', 0);
    }

    public function test_admin_resolution_is_audited_and_accepted_objection_removes_the_item(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $event = Event::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'objection_deadline' => now()->addHours(23),
            'final_price' => 1_500_000,
        ]);
        $objection = OrderObjection::factory()->create([
            'order_item_id' => $orderItem->id,
            'provider_user_id' => $vendor->id,
            'status' => 'diajukan',
        ]);

        $this->actingAs($admin)->post(
            route('admin.keberatan.decide', [$objection, 'approve']),
            ['reason' => 'Alasan penyedia tervalidasi.'],
        )->assertRedirect(route('admin.keberatan.detail', $objection));

        $this->assertDatabaseHas('order_objections', [
            'id' => $objection->id,
            'status' => 'diputuskan',
            'admin_decision' => 'diterima',
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => OrderItemStatus::Ditolak->value,
            'final_price' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'order_objection_accepted',
            'subject_type' => 'order_objection',
            'subject_id' => $objection->id,
        ]);
    }
}
