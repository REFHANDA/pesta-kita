<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\EventCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KlienEventCancellationBeforePaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Event, 2: OrderItem, 3: OrderItem}
     */
    private function submittedEvent(EventStatus $status = EventStatus::Diajukan): array
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id, 'status' => $status]);
        $venueItem = OrderItem::factory()->untukVenueRoom()->disetujui()->create([
            'event_id' => $event->id,
            'hold_key' => 'venue:1:'.$event->event_date->toDateString(),
        ]);
        $vendorItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'hold_key' => 'item:1:'.$event->event_date->toDateString(),
        ]);

        return [$klien, $event, $venueItem, $vendorItem];
    }

    public function test_batal_dari_diajukan_melepas_slot_tanpa_refund(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        [$klien, $event, $venueItem, $vendorItem] = $this->submittedEvent();

        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])
            ->assertRedirect(route('klien.acara.detail', $event))
            ->assertSessionHas('success');

        $this->assertSame(EventStatus::Dibatalkan, $event->fresh()->status);
        foreach ([$venueItem, $vendorItem] as $item) {
            $this->assertSame(OrderItemStatus::Dibatalkan, $item->fresh()->status);
            $this->assertNull($item->fresh()->hold_key);
        }
        $this->assertSame(0, Cancellation::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'event_cancelled_before_payment', 'subject_id' => $event->id, 'actor_id' => $klien->id, 'from_status' => 'diajukan', 'to_status' => 'dibatalkan']);

        Notification::assertSentTo($admin, EventCancelledNotification::class);
        $venueUser = $venueItem->provider->user;
        Notification::assertSentTo($venueUser, EventCancelledNotification::class, function (EventCancelledNotification $n) use ($venueUser, $klien): bool {
            $payload = json_encode($n->toArray($venueUser));

            return ! str_contains($payload, $klien->name) && str_contains($payload, 'Acara #');
        });
        Notification::assertNotSentTo($vendorItem->provider->user, EventCancelledNotification::class);
    }

    public function test_batal_dari_menunggu_pembayaran_membatalkan_tagihan(): void
    {
        Notification::fake();
        [$klien, $event, $venueItem] = $this->submittedEvent(EventStatus::MenungguPembayaran);
        $invoice = Invoice::factory()->create(['event_id' => $event->id, 'status' => InvoiceStatus::BelumDibayar]);

        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertSessionHas('success');

        $this->assertSame(EventStatus::Dibatalkan, $event->fresh()->status);
        $this->assertSame(InvoiceStatus::Dibatalkan, $invoice->fresh()->status);
        $this->assertNull($venueItem->fresh()->hold_key);
        $this->assertSame(0, Cancellation::count());

        $this->actingAs($klien)->get(route('klien.acara.dibatalkan'))->assertOk()->assertSee($event->name);
    }

    public function test_diblokir_saat_bukti_sedang_divalidasi(): void
    {
        [$klien, $event, $venueItem] = $this->submittedEvent(EventStatus::MenungguPembayaran);
        $invoice = Invoice::factory()->create(['event_id' => $event->id, 'status' => InvoiceStatus::MenungguValidasi]);

        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])
            ->assertSessionHas('error', 'Bukti pembayaran Anda sedang divalidasi Admin. Setelah divalidasi, ajukan pembatalan.');

        $this->assertSame(EventStatus::MenungguPembayaran, $event->fresh()->status);
        $this->assertSame(InvoiceStatus::MenungguValidasi, $invoice->fresh()->status);
        $this->assertNotNull($venueItem->fresh()->hold_key);
        $this->assertSame(0, AuditLog::where('action', 'event_cancelled_before_payment')->count());
    }

    public function test_ditolak_saat_terkonfirmasi_dan_tombol_tidak_tampil(): void
    {
        [$klien, $event] = $this->submittedEvent(EventStatus::Terkonfirmasi);
        Invoice::factory()->lunas()->create(['event_id' => $event->id]);

        $this->actingAs($klien)->get(route('klien.acara.detail', $event))->assertOk()->assertDontSee('Ya, batalkan acara');
        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertStatus(422);
        $this->assertSame(EventStatus::Terkonfirmasi, $event->fresh()->status);
    }

    public function test_tombol_tampil_untuk_acara_diajukan(): void
    {
        [$klien, $event] = $this->submittedEvent();

        $this->actingAs($klien)->get(route('klien.acara.detail', $event))->assertOk()->assertSee('Ya, batalkan acara');
    }

    public function test_hanya_pemilik_dan_wajib_konfirmasi(): void
    {
        [, $event] = $this->submittedEvent();
        $other = User::factory()->klien()->create();

        $this->actingAs($other)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertForbidden();
        $this->actingAs($event->client)->post(route('klien.acara.batalkan', $event), [])->assertSessionHasErrors('konfirmasi');

        $this->assertSame(EventStatus::Diajukan, $event->fresh()->status);
    }

    public function test_dua_kali_klik_tidak_menggandakan_efek(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        [$klien, $event] = $this->submittedEvent();

        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertSessionHas('success');
        $this->actingAs($klien)->post(route('klien.acara.batalkan', $event), ['konfirmasi' => '1'])->assertSessionHas('error', 'Acara ini sudah dibatalkan.');

        $this->assertSame(1, AuditLog::where('action', 'event_cancelled_before_payment')->count());
        Notification::assertCount(2);
    }
}
