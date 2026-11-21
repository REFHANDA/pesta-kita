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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evaluasi acara oleh Klien (Tahap C4).
 */
class KlienEvaluationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{client: User, event: Event, vendor: User, venue: User}
     */
    protected function finishedEvent(): array
    {
        $client = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Selesai,
            'event_date' => now()->subDay()->format('Y-m-d'),
        ]);

        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'status' => OrderItemStatus::Selesai,
            'provider_price' => 1_000_000,
        ]);

        OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venue->venueProfile->id,
            'status' => OrderItemStatus::Selesai,
            'provider_price' => 2_000_000,
        ]);

        return compact('client', 'event', 'vendor', 'venue');
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $fixture, int $adminRating = 4): array
    {
        return [
            'ratings' => [
                'venue' => ['rating' => 5, 'comment' => 'Ruangan bersih'],
                'vendor:'.$fixture['vendor']->vendorProfile->id => ['rating' => 3, 'comment' => 'Tepat waktu'],
                'admin' => ['rating' => $adminRating, 'comment' => null],
            ],
            'system_feedback' => 'Semoga makin mudah.',
        ];
    }

    public function test_klien_mengirim_evaluasi_lengkap_dan_status_menjadi_dievaluasi(): void
    {
        $f = $this->finishedEvent();

        $this->actingAs($f['client'])
            ->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f))
            ->assertRedirect(route('klien.acara.detail', $f['event']).'#evaluasi');

        $this->assertSame(EventStatus::Dievaluasi, $f['event']->fresh()->status);
        $this->assertSame(1, Evaluation::where('event_id', $f['event']->id)->count());
        $this->assertSame(3, EvaluationItem::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'event_evaluated', 'subject_id' => $f['event']->id]);

        $this->actingAs($f['client'])->get(route('klien.acara.detail', $f['event']))
            ->assertOk()
            ->assertSee('Ruangan bersih')
            ->assertDontSee(route('klien.acara.evaluasi.store', $f['event']), false);
    }

    public function test_evaluasi_kedua_kali_mendapat_409_tanpa_menggandakan(): void
    {
        $f = $this->finishedEvent();

        $this->actingAs($f['client'])->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f));
        $this->actingAs($f['client'])->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f, 1))
            ->assertStatus(409);

        $this->assertSame(1, Evaluation::where('event_id', $f['event']->id)->count());
        $this->assertSame(3, EvaluationItem::count());
    }

    public function test_subjek_tidak_lengkap_atau_asing_ditolak(): void
    {
        $f = $this->finishedEvent();
        $payload = $this->payload($f);
        unset($payload['ratings']['admin']);

        $this->actingAs($f['client'])
            ->post(route('klien.acara.evaluasi.store', $f['event']), $payload)
            ->assertSessionHasErrors('ratings');

        $payload = $this->payload($f);
        $payload['ratings']['vendor:999999'] = ['rating' => 5];

        $this->actingAs($f['client'])
            ->post(route('klien.acara.evaluasi.store', $f['event']), $payload)
            ->assertSessionHasErrors('ratings');

        $this->assertSame(0, Evaluation::count());
        $this->assertSame(EventStatus::Selesai, $f['event']->fresh()->status);
    }

    public function test_rating_di_luar_rentang_ditolak(): void
    {
        $f = $this->finishedEvent();
        $payload = $this->payload($f);
        $payload['ratings']['admin']['rating'] = 6;

        $this->actingAs($f['client'])
            ->post(route('klien.acara.evaluasi.store', $f['event']), $payload)
            ->assertSessionHasErrors('ratings.admin.rating');

        $this->assertSame(0, Evaluation::count());
    }

    public function test_hanya_pemilik_dan_hanya_status_selesai(): void
    {
        $f = $this->finishedEvent();
        $intruder = User::factory()->klien()->create();

        $this->actingAs($intruder)
            ->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f))
            ->assertForbidden();

        $f['event']->update(['status' => EventStatus::Terkonfirmasi]);

        $this->actingAs($f['client'])
            ->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f))
            ->assertRedirect();

        $this->assertSame(0, Evaluation::count());
    }

    public function test_evaluasi_tidak_mengubah_data_uang(): void
    {
        $f = $this->finishedEvent();

        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $f['vendor']->vendorProfile->id,
            'amount' => 1_000_000,
        ]);
        $invoice = Invoice::factory()->create(['event_id' => $f['event']->id]);

        $before = [
            'disbursements' => Disbursement::whereKey($disbursement->id)->first()?->toArray(),
            'invoices' => Invoice::whereKey($invoice->id)->first()?->toArray(),
            'payments' => Payment::count(),
            'cancellations' => Cancellation::count(),
        ];

        $this->actingAs($f['client'])->post(route('klien.acara.evaluasi.store', $f['event']), $this->payload($f));

        $this->assertSame($before['disbursements'], Disbursement::whereKey($disbursement->id)->first()?->toArray());
        $this->assertSame($before['invoices'], Invoice::whereKey($invoice->id)->first()?->toArray());
        $this->assertSame($before['payments'], Payment::count());
        $this->assertSame($before['cancellations'], Cancellation::count());
    }

    public function test_url_lama_evaluasi_404(): void
    {
        $client = User::factory()->klien()->create();

        $this->actingAs($client)->get('/klien/evaluasi')->assertNotFound();
    }
}
