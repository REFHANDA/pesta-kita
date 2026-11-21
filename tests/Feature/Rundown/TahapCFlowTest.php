<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\EvaluationItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RundownItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lintas fitur Tahap C7: alur penuh rundown -> selesai -> evaluasi -> rating,
 * snapshot uang tidak berubah, dan acara dibatalkan tidak punya rundown/evaluasi.
 */
class TahapCFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_alur_penuh_dari_rundown_sampai_rating(): void
    {
        $client = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();
        $admin = User::factory()->admin()->create();

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
            'event_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $vendorOrder = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'provider_price' => 1_000_000,
            'commission' => 100_000,
        ]);

        OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venue->venueProfile->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'provider_price' => 2_000_000,
            'commission' => 200_000,
        ]);

        $invoice = Invoice::factory()->lunas()->create([
            'event_id' => $event->id,
            'subtotal_provider' => 3_000_000,
            'total_commission' => 300_000,
            'cleaning_fee' => 0,
            'grand_total' => 3_300_000,
            'unique_code' => 12,
            'transfer_total' => 3_300_012,
        ]);

        // 1. Klien menyusun rundown.
        $this->actingAs($client)->post(route('klien.acara.rundown.store', $event), [
            'start_time' => '09:00',
            'end_time' => '09:30',
            'activity' => 'Registrasi tamu',
        ])->assertRedirect();

        // 2. Vendor menambah item miliknya.
        $this->actingAs($vendor)->post(route('vendor.rundown.store', $event), [
            'start_time' => '10:00',
            'activity' => 'Persiapan katering',
            'order_item_id' => $vendorOrder->id,
        ])->assertRedirect();

        // 3. Venue dan Admin melihat rundown.
        $this->actingAs($venue)->get(route('venue.rundown.show', $event))->assertOk()->assertSee('Persiapan katering');
        $this->actingAs($admin)->get(route('admin.acara.detail', $event))->assertOk()->assertSee('Registrasi tamu');

        // 4. Admin menandai selesai -> rundown terkunci.
        $this->actingAs($admin)->post(route('admin.acara.selesai', $event))->assertRedirect();

        $event->refresh();
        $this->assertSame(EventStatus::Selesai, $event->status);

        $this->actingAs($client)->post(route('klien.acara.rundown.store', $event), [
            'start_time' => '11:00',
            'activity' => 'Percobaan setelah selesai',
        ])->assertForbidden();

        // 5. Klien mengisi evaluasi.
        $snapshot = [
            'invoices' => Invoice::orderBy('id')->get()->toArray(),
            'payments' => Payment::orderBy('id')->get()->toArray(),
            'disbursements' => Disbursement::orderBy('id')->get()->toArray(),
            'cancellations' => Cancellation::orderBy('id')->get()->toArray(),
        ];

        $this->actingAs($client)->post(route('klien.acara.evaluasi.store', $event), [
            'ratings' => [
                'venue' => ['rating' => 5],
                'vendor:'.$vendor->vendorProfile->id => ['rating' => 4, 'comment' => 'Rapi'],
                'admin' => ['rating' => 5],
            ],
        ])->assertRedirect();

        $this->assertSame(EventStatus::Dievaluasi, $event->fresh()->status);
        $this->assertSame(1, Evaluation::count());
        $this->assertSame(3, EvaluationItem::count());

        // 6. Uang identik setelah seluruh alur C.
        $this->assertSame($snapshot['invoices'], Invoice::orderBy('id')->get()->toArray());
        $this->assertSame($snapshot['payments'], Payment::orderBy('id')->get()->toArray());
        $this->assertSame($snapshot['disbursements'], Disbursement::orderBy('id')->get()->toArray());
        $this->assertSame($snapshot['cancellations'], Cancellation::orderBy('id')->get()->toArray());

        // 7. Rating tampil di Admin dan penyedia.
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('5,0');
        $this->actingAs($vendor)->get('/vendor')->assertOk()->assertSee('4,0')->assertSee('Rapi');
        $this->actingAs($venue)->get('/venue')->assertOk()->assertSee('5,0');
    }

    public function test_acara_dibatalkan_tidak_punya_rundown_atau_evaluasi(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Dibatalkan,
            'event_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $this->actingAs($client)->post(route('klien.acara.rundown.store', $event), [
            'start_time' => '09:00',
            'activity' => 'Percobaan',
        ])->assertForbidden();

        $this->actingAs($client)->post(route('klien.acara.evaluasi.store', $event), [
            'ratings' => ['admin' => ['rating' => 5]],
        ])->assertRedirect();

        $this->assertSame(0, RundownItem::count());
        $this->assertSame(0, Evaluation::count());
    }
}
