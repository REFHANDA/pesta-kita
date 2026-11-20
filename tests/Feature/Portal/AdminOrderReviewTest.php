<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\OrderItemReviewedNotification;
use App\Notifications\OrderObjectionDecidedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOrderReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('commission_percent', 10);
        Setting::set('objection_window_hours', 24);
        Setting::set('admin_review_sla_hours', 24);
        Setting::set('payment_deadline_hours', 48);
        Setting::set('unique_code_enabled', true);
    }

    public function test_admin_can_view_order_review_list_and_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();

        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'name' => 'Ulang Tahun Nadia',
            'status' => EventStatus::Diajukan,
        ]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::MenungguReview,
            'catalog_price_snapshot' => 3_000_000,
            'provider_price' => 3_000_000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan'));
        $response->assertOk();
        $response->assertSee('Ulang Tahun Nadia');

        $detailResponse = $this->actingAs($admin)->get(route('admin.pesanan.show', $item));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Setujui pesanan');
        $detailResponse->assertSee('Tolak pesanan');
    }

    public function test_admin_can_approve_order_item_locking_price_and_calculating_commission(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $vendorUser = User::factory()->create(['role' => Role::Vendor]);
        $vendorProfile = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);
        $klien = User::factory()->klien()->create();

        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Diajukan,
        ]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'status' => OrderItemStatus::MenungguReview,
            'catalog_price_snapshot' => 5_000_000,
            'provider_price' => 5_000_000,
            'commission' => 0,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pesanan.approve', $item), [
            'admin_note' => 'Disetujui untuk acara.',
        ]);

        $response->assertRedirect(route('admin.pesanan.show', $item));
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals(OrderItemStatus::Disetujui, $item->status);
        $this->assertEquals(5_000_000, $item->provider_price);
        $this->assertEquals(500_000, $item->commission); // 10%
        $this->assertNotNull($item->approved_at);
        $this->assertNotNull($item->objection_deadline);
        $this->assertEquals($admin->id, $item->reviewed_by);

        // Jejak audit wajib ditulis
        $auditLog = AuditLog::where('subject_type', $item->getMorphClass())
            ->where('subject_id', $item->id)
            ->where('action', 'order_item_approved')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($admin->id, $auditLog->actor_id);
        $this->assertEquals('menunggu_review', $auditLog->from_status);
        $this->assertEquals('disetujui', $auditLog->to_status);

        Notification::assertSentTo($klien, OrderItemReviewedNotification::class);
        Notification::assertSentTo($vendorUser, OrderItemReviewedNotification::class);
    }

    public function test_admin_can_reject_order_item_with_required_reason_and_releases_hold(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id, 'status' => EventStatus::Diajukan]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::MenungguReview,
            'hold_key' => 'venue:10:2026-12-12',
        ]);

        // Alasan wajib
        $failResponse = $this->actingAs($admin)->post(route('admin.pesanan.reject', $item), [
            'rejection_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('rejection_reason');

        $response = $this->actingAs($admin)->post(route('admin.pesanan.reject', $item), [
            'rejection_reason' => 'Vendor tidak sanggup melayani tanggal tersebut.',
        ]);

        $response->assertRedirect(route('admin.pesanan.show', $item));
        $item->refresh();

        $this->assertEquals(OrderItemStatus::Ditolak, $item->status);
        $this->assertEquals('Vendor tidak sanggup melayani tanggal tersebut.', $item->rejection_reason);
        $this->assertNull($item->hold_key); // hold dilepas

        $auditLog = AuditLog::where('action', 'order_item_rejected')->first();
        $this->assertNotNull($auditLog);
    }

    public function test_admin_can_reassign_order_item_to_another_provider(): void
    {
        $admin = User::factory()->admin()->create();
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id, 'status' => EventStatus::Diajukan]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::MenungguReview,
        ]);

        $newCatalogItem = CatalogItem::factory()->create([
            'name' => 'Katering Pengganti',
            'catalog_price' => 3_500_000,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pesanan.reassign', $item), [
            'replacement_subject_type' => 'catalog_item',
            'replacement_subject_id' => $newCatalogItem->id,
            'reason' => 'Vendor awal jadwal bentrok, dialihkan ke penyedia setara.',
        ]);

        $response->assertRedirect(route('admin.pesanan.show', $item));

        $item->refresh();
        $this->assertEquals(OrderItemStatus::Dialihkan, $item->status);
        $this->assertNotNull($item->replaced_by_order_item_id);

        $replacementItem = OrderItem::find($item->replaced_by_order_item_id);
        $this->assertNotNull($replacementItem);
        $this->assertEquals(OrderItemStatus::MenungguReview, $replacementItem->status);
        $this->assertEquals(3_500_000, $replacementItem->provider_price);
    }

    public function test_admin_can_decide_provider_objection(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $vendorUser = User::factory()->create(['role' => Role::Vendor]);
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id, 'status' => EventStatus::Diajukan]);

        $item = OrderItem::factory()->disetujui()->create([
            'event_id' => $event->id,
        ]);

        $objection = OrderObjection::create([
            'order_item_id' => $item->id,
            'provider_user_id' => $vendorUser->id,
            'category' => OrderObjectionCategory::AlatRusak,
            'reason' => 'Peralatan utama sound system terbakar.',
            'status' => OrderObjectionStatus::Diajukan,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.keberatan.decide', $objection), [
            'decision' => 'terima',
            'decision_reason' => 'Keberatan valid, pesanan dibatalkan agar klien dapat memilih pengganti.',
            'action_type' => 'tolak',
        ]);

        $response->assertRedirect();

        $objection->refresh();
        $this->assertEquals(OrderObjectionStatus::Diterima, $objection->status);
        $this->assertEquals('terima', $objection->admin_decision);

        $item->refresh();
        $this->assertEquals(OrderItemStatus::Ditolak, $item->status);

        Notification::assertSentTo($vendorUser, OrderObjectionDecidedNotification::class);
    }
}
