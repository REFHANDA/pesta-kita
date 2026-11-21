<?php

namespace Tests\Feature\Console;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderSlaReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderAndInvoiceCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('commission_percent', 10);
        Setting::set('payment_deadline_hours', 48);
        Setting::set('objection_window_hours', 24);
        Setting::set('unique_code_enabled', true);
    }

    public function test_issue_invoices_command_issues_invoices_for_eligible_events(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Diajukan,
        ]);

        OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(25),
            'objection_deadline' => now()->subHour(),
            'provider_price' => 1_000_000,
            'commission' => 100_000,
        ]);

        $this->artisan('invoices:issue')
            ->expectsOutputToContain('Total 1 tagihan diterbitkan.')
            ->assertSuccessful();

        $this->assertDatabaseHas('invoices', [
            'event_id' => $event->id,
            'status' => 'belum_dibayar',
        ]);
    }

    public function test_remind_order_sla_command_sends_notifications_to_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        // Pesanan 1: mendekati SLA (2 jam lagi)
        $orderWarning = OrderItem::factory()->create([
            'status' => OrderItemStatus::MenungguReview,
            'review_due_at' => now()->addHours(2),
        ]);

        // Pesanan 2: sudah melewati SLA (1 jam lalu)
        $orderOverdue = OrderItem::factory()->create([
            'status' => OrderItemStatus::MenungguReview,
            'review_due_at' => now()->subHour(),
        ]);

        $this->artisan('orders:remind-sla')
            ->expectsOutputToContain('Notifikasi dikirimkan untuk 2 pesanan.')
            ->assertSuccessful();

        Notification::assertSentTo($admin, OrderSlaReminderNotification::class, 2);
    }
}
