<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_locks_the_server_price_and_starts_a_24_hour_objection_window(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->diajukan()->create();
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'catalog_price_snapshot' => 3_000_000,
            'proposed_price_snapshot' => 2_650_000,
        ]);

        $this->actingAs($admin)->post(
            route('admin.pesanan.decide', [$orderItem, 'approve']),
            ['final_price' => 1, 'status' => OrderItemStatus::Ditolak->value],
        )->assertRedirect(route('admin.pesanan.detail', $orderItem));

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'final_price' => 2_650_000,
            'status' => OrderItemStatus::MasaKeberatan->value,
            'objection_deadline' => '2030-01-02 10:00:00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'order_item_approved',
            'subject_type' => 'order_item',
            'subject_id' => $orderItem->id,
            'from_status' => OrderItemStatus::MenungguAdmin->value,
            'to_status' => OrderItemStatus::MasaKeberatan->value,
        ]);
        $this->assertSame(EventStatus::Diajukan, $event->fresh()->status);
    }

    public function test_admin_rejection_requires_a_reason_and_does_not_set_a_final_price(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->diajukan()->create();
        $orderItem = OrderItem::factory()->create(['event_id' => $event->id]);
        $url = route('admin.pesanan.decide', [$orderItem, 'reject']);

        $invalid = $this->actingAs($admin)
            ->from(route('admin.pesanan.detail', $orderItem))
            ->post($url, []);

        $invalid->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => OrderItemStatus::MenungguAdmin->value,
            'final_price' => null,
        ]);

        $this->actingAs($admin)->post($url, ['reason' => 'Tidak sesuai kebutuhan acara.'])
            ->assertRedirect(route('admin.pesanan.detail', $orderItem));

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => OrderItemStatus::Ditolak->value,
            'final_price' => null,
            'rejection_reason' => 'Tidak sesuai kebutuhan acara.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order_item_rejected',
            'subject_id' => $orderItem->id,
            'reason' => 'Tidak sesuai kebutuhan acara.',
        ]);
    }

    public function test_admin_cannot_decide_the_same_order_item_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->diajukan()->create();
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::MasaKeberatan,
            'objection_deadline' => now()->addDay(),
            'final_price' => 3_000_000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.decide', [$orderItem, 'reject']), ['reason' => 'Ditolak.'])
            ->assertSessionHasErrors('order_item');

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => OrderItemStatus::MasaKeberatan->value,
            'final_price' => 3_000_000,
        ]);
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_non_admin_cannot_review_an_order_item(): void
    {
        $vendor = User::factory()->vendor()->create();
        $orderItem = OrderItem::factory()->create();

        $this->actingAs($vendor)
            ->post(route('admin.pesanan.decide', [$orderItem, 'approve']), [])
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => OrderItemStatus::MenungguAdmin->value,
        ]);
    }
}
