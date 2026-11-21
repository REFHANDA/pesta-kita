<?php

namespace Tests\Feature\Console;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InvoiceExpiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpireInvoicesCommandTest extends TestCase
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

    public function test_invoices_expire_command_expires_overdue_unpaid_invoices_and_cancels_orders(): void
    {
        Notification::fake();

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        $itemApproved = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Disetujui,
            'hold_key' => 'venue:1:2026-12-01',
            'provider_price' => 5_000_000,
        ]);

        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
            'due_at' => now()->subHour(), // Lewat batas waktu bayar
        ]);

        $this->artisan('invoices:expire')
            ->expectsOutputToContain("Tagihan {$invoice->invoice_number} telah kedaluwarsa.")
            ->assertSuccessful();

        // Status tagihan -> kedaluwarsa
        $this->assertEquals(InvoiceStatus::Kedaluwarsa, $invoice->fresh()->status);

        // Status acara -> dibatalkan
        $this->assertEquals(EventStatus::Dibatalkan, $event->fresh()->status);

        // Status item pesanan -> dibatalkan dan hold_key dikosongkan
        $this->assertEquals(OrderItemStatus::Dibatalkan, $itemApproved->fresh()->status);
        $this->assertNull($itemApproved->fresh()->hold_key);

        // Audit log ditulis
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invoice_expired',
            'subject_id' => $invoice->id,
        ]);

        // Klien menerima notifikasi tagihan kedaluwarsa
        Notification::assertSentTo($klien, InvoiceExpiredNotification::class);
    }

    public function test_invoices_expire_command_does_not_expire_active_or_paid_invoices(): void
    {
        Notification::fake();

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        // Tagihan 1: belum lewat batas waktu (due_at masih 10 jam ke depan)
        $invoiceActive = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
            'due_at' => now()->addHours(10),
        ]);

        // Tagihan 2: sudah lunas walau due_at kemarin
        $invoicePaid = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::Lunas,
            'due_at' => now()->subDay(),
            'paid_at' => now()->subDays(2),
        ]);

        $this->artisan('invoices:expire')
            ->expectsOutputToContain('Total 0 tagihan kedaluwarsa diproses.')
            ->assertSuccessful();

        $this->assertEquals(InvoiceStatus::BelumDibayar, $invoiceActive->fresh()->status);
        $this->assertEquals(InvoiceStatus::Lunas, $invoicePaid->fresh()->status);

        Notification::assertNothingSent();
    }

    public function test_invoices_expire_command_does_not_expire_invoices_awaiting_validation(): void
    {
        Notification::fake();

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::MenungguPembayaran,
        ]);

        // Tagihan yang sudah lewat batas due_at, tapi bukti pembayaran sudah diunggah (menunggu_validasi)
        $invoiceAwaiting = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::MenungguValidasi,
            'due_at' => now()->subHours(2),
        ]);

        $this->artisan('invoices:expire')
            ->expectsOutputToContain('Total 0 tagihan kedaluwarsa diproses.')
            ->assertSuccessful();

        $this->assertEquals(InvoiceStatus::MenungguValidasi, $invoiceAwaiting->fresh()->status);
        $this->assertEquals(EventStatus::MenungguPembayaran, $event->fresh()->status);

        Notification::assertNothingSent();
    }
}
