<?php

namespace Tests\Feature\Console;

use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PaymentValidationSlaReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindPaymentValidationSlaCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('admin_review_sla_hours', 24);
    }

    public function test_remind_payment_validation_sla_sends_notification_to_admins(): void
    {
        Notification::fake();

        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $invoice = Invoice::factory()->create(['event_id' => $event->id]);

        // Pembayaran 1: Diunggah 21 jam lalu (mendekati SLA 24 jam)
        $paymentApproaching = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::MenungguValidasi,
            'created_at' => now()->subHours(21),
        ]);

        // Pembayaran 2: Diunggah 26 jam lalu (sudah lewat SLA 24 jam)
        $paymentOverdue = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::MenungguValidasi,
            'created_at' => now()->subHours(26),
        ]);

        // Pembayaran 3: Baru diunggah 2 jam lalu (belum mendekati SLA)
        $paymentRecent = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::MenungguValidasi,
            'created_at' => now()->subHours(2),
        ]);

        // Pembayaran 4: Sudah valid (tidak perlu diingatkan)
        $paymentValid = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'status' => PaymentStatus::Valid,
            'created_at' => now()->subHours(30),
        ]);

        $this->artisan('payments:remind-sla')
            ->expectsOutputToContain('Selesai. Notifikasi SLA validasi pembayaran dikirimkan untuk 2 bukti transfer.')
            ->assertSuccessful();

        Notification::assertSentTo($admin1, PaymentValidationSlaReminderNotification::class, function ($notif) use ($paymentApproaching) {
            return $notif->payment->id === $paymentApproaching->id && $notif->isOverdue === false;
        });

        Notification::assertSentTo($admin1, PaymentValidationSlaReminderNotification::class, function ($notif) use ($paymentOverdue) {
            return $notif->payment->id === $paymentOverdue->id && $notif->isOverdue === true;
        });

        Notification::assertSentTo($admin2, PaymentValidationSlaReminderNotification::class);
    }
}
