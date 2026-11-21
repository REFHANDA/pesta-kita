<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Requests\Rundown\SaveRundownItemRequest;
use App\Models\Disbursement;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\RundownItem;
use App\Models\User;
use App\Services\RundownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Matriks akses rundown Tahap C1 dan aturan validasi/status acara.
 */
class RundownRulesTest extends TestCase
{
    use RefreshDatabase;

    protected RundownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RundownService::class);
    }

    /**
     * Bangun aktor dan acara untuk tiap status dengan pesanan penyedia.
     *
     * @return array{actors: array<string, User>, events: array<string, Event>, vendorItem: OrderItem, venueItem: OrderItem}
     */
    protected function matrixFixture(): array
    {
        $client = User::factory()->klien()->create();
        $otherClient = User::factory()->klien()->create();

        $vendorUser = User::factory()->vendor()->create();
        $vendorProfile = $vendorUser->vendorProfile;
        $otherVendorUser = User::factory()->vendor()->create();

        $venueUser = User::factory()->venue()->create();
        $venueProfile = $venueUser->venueProfile;

        $admin = User::factory()->admin()->create();

        $statusMap = [
            'diajukan' => [EventStatus::Diajukan, OrderItemStatus::MenungguReview],
            'terkonfirmasi' => [EventStatus::Terkonfirmasi, OrderItemStatus::Terkonfirmasi],
            'selesai' => [EventStatus::Selesai, OrderItemStatus::Selesai],
            'dievaluasi' => [EventStatus::Dievaluasi, OrderItemStatus::Selesai],
            'dibatalkan' => [EventStatus::Dibatalkan, OrderItemStatus::Dibatalkan],
        ];

        $events = [];
        $vendorItem = null;
        $venueItem = null;

        foreach ($statusMap as $key => [$eventStatus, $itemStatus]) {
            $event = Event::factory()->create([
                'client_id' => $client->id,
                'status' => $eventStatus,
                'event_date' => now()->subDays(2)->format('Y-m-d'),
            ]);

            $vendorOrder = OrderItem::factory()->create([
                'event_id' => $event->id,
                'provider_type' => 'vendor_profile',
                'provider_id' => $vendorProfile->id,
                'status' => $itemStatus,
                'provider_price' => 1_000_000,
            ]);

            $venueOrder = OrderItem::factory()->untukVenueRoom()->create([
                'event_id' => $event->id,
                'provider_type' => 'venue_profile',
                'provider_id' => $venueProfile->id,
                'status' => $itemStatus,
                'provider_price' => 1_000_000,
            ]);

            $events[$key] = $event;
            $vendorItem = $key === 'terkonfirmasi' ? $vendorOrder : $vendorItem;
            $venueItem = $key === 'terkonfirmasi' ? $venueOrder : $venueItem;
        }

        return [
            'actors' => [
                'Klien pemilik' => $client,
                'Klien lain' => $otherClient,
                'Vendor pemilik pesanan' => $vendorUser,
                'Vendor lain' => $otherVendorUser,
                'Venue pemilik pesanan' => $venueUser,
                'Admin' => $admin,
            ],
            'events' => $events,
            'vendorItem' => $vendorItem,
            'venueItem' => $venueItem,
        ];
    }

    public function test_matriks_akses_rundown_sesuai_aturan_tahap_c(): void
    {
        $fixture = $this->matrixFixture();
        $statuses = ['diajukan', 'terkonfirmasi', 'selesai', 'dievaluasi', 'dibatalkan'];

        $expected = [
            'Klien pemilik' => ['-', 'lihat / tulis', 'lihat', 'lihat', '-'],
            'Klien lain' => ['-', '-', '-', '-', '-'],
            'Vendor pemilik pesanan' => ['-', 'lihat / tulis', 'lihat', 'lihat', '-'],
            'Vendor lain' => ['-', '-', '-', '-', '-'],
            'Venue pemilik pesanan' => ['-', 'lihat', 'lihat', 'lihat', '-'],
            'Admin' => ['lihat', 'lihat', 'lihat', 'lihat', 'lihat'],
        ];

        $rows = [];
        foreach ($fixture['actors'] as $label => $user) {
            $cells = [];
            foreach ($statuses as $status) {
                $event = $fixture['events'][$status];
                $view = $this->service->canView($user, $event);
                $write = $this->service->canWrite($user, $event);
                $cells[] = $view ? ($write ? 'lihat / tulis' : 'lihat') : '-';
            }
            $rows[$label] = $cells;
        }

        $lines = ['MATRIKS AKSES RUNDOWN (baris=aktor, kolom=status acara)'];
        $lines[] = str_pad('Aktor', 24).implode(' | ', array_map(fn ($s) => str_pad($s, 14), $statuses));
        foreach ($rows as $label => $cells) {
            $lines[] = str_pad($label, 24).implode(' | ', array_map(fn ($c) => str_pad($c, 14), $cells));
        }
        fwrite(STDERR, "\n".implode("\n", $lines)."\n\n");

        $this->assertSame($expected, $rows);
    }

    public function test_validasi_format_jam_dan_kegiatan(): void
    {
        $rules = (new SaveRundownItemRequest)->rules();

        $this->assertTrue(Validator::make([
            'start_time' => '09:30',
            'end_time' => '10:15',
            'activity' => 'Registrasi tamu',
        ], $rules)->passes());

        $this->assertTrue(Validator::make([
            'start_time' => '9:30',
            'activity' => 'Registrasi tamu',
        ], $rules)->fails());

        $this->assertTrue(Validator::make([
            'start_time' => '24:00',
            'activity' => 'Registrasi tamu',
        ], $rules)->fails());

        $this->assertTrue(Validator::make([
            'start_time' => '10:00',
            'activity' => str_repeat('a', 151),
        ], $rules)->fails());
    }

    public function test_service_menolak_jam_selesai_sebelum_atau_sama_dengan_jam_mulai(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
        ]);

        foreach ([['10:00', '09:59'], ['10:00', '10:00']] as [$start, $end]) {
            try {
                $this->service->add($event, $client, [
                    'start_time' => $start,
                    'end_time' => $end,
                    'activity' => 'Registrasi tamu',
                ]);
                $this->fail('Jam selesai tidak boleh sebelum atau sama dengan jam mulai.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('end_time', $exception->errors());
            }
        }

        $this->assertSame(0, RundownItem::where('event_id', $event->id)->count());
    }

    public function test_batas_seratus_item_per_acara(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
        ]);

        RundownItem::factory()->count(100)->create([
            'event_id' => $event->id,
            'created_by' => $client->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->add($event, $client, [
            'start_time' => '20:00',
            'activity' => 'Item ke-101',
        ]);
    }

    public function test_vendor_hanya_boleh_mengubah_item_miliknya(): void
    {
        $fixture = $this->matrixFixture();
        $event = $fixture['events']['terkonfirmasi'];
        $vendor = $fixture['actors']['Vendor pemilik pesanan'];
        $client = $fixture['actors']['Klien pemilik'];

        $clientItem = RundownItem::factory()->create([
            'event_id' => $event->id,
            'created_by' => $client->id,
            'order_item_id' => null,
        ]);

        $vendorItem = $this->service->add($event, $vendor, [
            'start_time' => '08:00',
            'activity' => 'Persiapan katering',
            'order_item_id' => $fixture['vendorItem']->id,
        ]);

        $otherVendor = $fixture['actors']['Vendor lain'];

        $this->assertTrue($this->service->canModifyItem($vendor, $vendorItem));
        $this->assertFalse($this->service->canModifyItem($vendor, $clientItem));
        $this->assertFalse($this->service->canModifyItem($otherVendor, $vendorItem));

        $this->expectException(ValidationException::class);
        $this->service->update($clientItem, $vendor, [
            'start_time' => '09:00',
            'activity' => 'Diubah vendor lain',
        ]);
    }

    public function test_vendor_tidak_boleh_menautkan_pesanan_vendor_lain(): void
    {
        $fixture = $this->matrixFixture();
        $event = $fixture['events']['terkonfirmasi'];
        $vendor = $fixture['actors']['Vendor pemilik pesanan'];
        $venue = $fixture['actors']['Venue pemilik pesanan'];

        $this->expectException(ValidationException::class);

        $this->service->add($event, $vendor, [
            'start_time' => '08:00',
            'activity' => 'Menautkan pesanan venue',
            'order_item_id' => $fixture['venueItem']->id,
        ]);

        $this->assertFalse($this->service->canWrite($venue, $event));
    }

    public function test_vendor_wajib_menautkan_pesanan_sendiri(): void
    {
        $fixture = $this->matrixFixture();
        $event = $fixture['events']['terkonfirmasi'];
        $vendor = $fixture['actors']['Vendor pemilik pesanan'];

        $this->expectException(ValidationException::class);
        $this->service->add($event, $vendor, [
            'start_time' => '08:00',
            'activity' => 'Tanpa pesanan',
        ]);
    }

    public function test_tulis_ditolak_di_luar_status_terkonfirmasi(): void
    {
        $fixture = $this->matrixFixture();
        $client = $fixture['actors']['Klien pemilik'];

        foreach (['diajukan', 'selesai', 'dievaluasi', 'dibatalkan'] as $status) {
            $event = $fixture['events'][$status];

            try {
                $this->service->add($event, $client, [
                    'start_time' => '10:00',
                    'activity' => 'Percobaan menulis',
                ]);
                $this->fail("Tulis rundown seharusnya ditolak pada status {$status}.");
            } catch (ValidationException) {
                $this->assertSame(0, RundownItem::where('event_id', $event->id)->count());
            }
        }
    }

    public function test_klien_lain_tidak_dapat_melihat_atau_menulis(): void
    {
        $fixture = $this->matrixFixture();
        $event = $fixture['events']['terkonfirmasi'];
        $intruder = $fixture['actors']['Klien lain'];

        $this->assertFalse($this->service->canView($intruder, $event));
        $this->assertFalse($this->service->canWrite($intruder, $event));

        $this->expectException(ValidationException::class);
        $this->service->add($event, $intruder, [
            'start_time' => '10:00',
            'activity' => 'Percobaan penyusupan',
        ]);
    }

    public function test_urutan_item_berdasarkan_jam_lalu_id(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
        ]);

        $late = RundownItem::factory()->create(['event_id' => $event->id, 'created_by' => $client->id, 'start_time' => '18:00']);
        $earlyB = RundownItem::factory()->create(['event_id' => $event->id, 'created_by' => $client->id, 'start_time' => '09:00']);
        $earlyA = RundownItem::factory()->create(['event_id' => $event->id, 'created_by' => $client->id, 'start_time' => '09:00']);

        $ids = $this->service->items($event)->pluck('id')->all();

        $this->assertSame([$earlyB->id, $earlyA->id, $late->id], $ids);
    }

    public function test_perubahan_rundown_tercatat_di_audit_log_dan_bukan_data_uang(): void
    {
        $fixture = $this->matrixFixture();
        $event = $fixture['events']['terkonfirmasi'];
        $client = $fixture['actors']['Klien pemilik'];

        $item = $this->service->add($event, $client, [
            'start_time' => '10:00',
            'activity' => 'Pembukaan',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'rundown_item_added', 'subject_id' => $event->id]);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Disbursement::count());

        $this->service->delete($item, $client);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rundown_item_deleted', 'subject_id' => $event->id]);
    }
}
