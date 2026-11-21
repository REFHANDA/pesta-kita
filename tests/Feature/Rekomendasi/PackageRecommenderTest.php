<?php

namespace Tests\Feature\Rekomendasi;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Evaluation;
use App\Models\EvaluationItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venue;
use App\Services\PackageRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rekomendasi paket Tahap D1: alokasi, filter ketersediaan/harga/verifikasi,
 * urutan rating, ringkasan, dan tanpa penulisan database.
 */
class PackageRecommenderTest extends TestCase
{
    use RefreshDatabase;

    protected PackageRecommender $recommender;

    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recommender = app(PackageRecommender::class);
        $this->date = now()->addDays(30)->format('Y-m-d');
    }

    protected function draftEvent(int $budget = 100_000_000, int $guests = 200): Event
    {
        $client = User::factory()->klien()->create();

        return Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Draft,
            'budget' => $budget,
            'guest_count' => $guests,
            'event_date' => $this->date,
        ]);
    }

    protected function verifiedVendor(): User
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        return $vendor;
    }

    protected function service(User $vendor, string $category, int $price, string $name = 'Paket'): CatalogItem
    {
        return CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'kind' => CatalogKind::Layanan,
            'category' => $category,
            'catalog_price' => $price,
            'status' => CatalogItemStatus::Aktif,
        ])->setAttribute('name', $name) ?? CatalogItem::query()->latest('id')->first();
    }

    public function test_alokasi_bulat_dan_selisih_masuk_cadangan(): void
    {
        $this->assertSame([
            'venue' => 35_000_000,
            'catering' => 30_000_000,
            'dekorasi' => 15_000_000,
            'hiburan' => 10_000_000,
            'peralatan' => 5_000_000,
            'cadangan' => 5_000_000,
        ], $this->recommender->allocations(100_000_000));

        $allocations = $this->recommender->allocations(100_000_001);
        $this->assertSame(100_000_001, array_sum($allocations));
        $this->assertSame(5_000_001, $allocations['cadangan']);
        $this->assertIsInt($allocations['cadangan']);
    }

    public function test_pengaturan_alokasi_langsung_mengubah_hasil(): void
    {
        Setting::set('budget_allocation', [
            'venue' => 40,
            'catering' => 20,
            'dekorasi' => 10,
            'hiburan' => 10,
            'peralatan' => 10,
            'cadangan' => 10,
        ]);

        $allocations = $this->recommender->allocations(100_000_000);

        $this->assertSame(40_000_000, $allocations['venue']);
        $this->assertSame(20_000_000, $allocations['catering']);
        $this->assertSame(10_000_000, $allocations['cadangan']);
    }

    public function test_filter_kapasitas_harga_dan_ketersediaan_venue(): void
    {
        $event = $this->draftEvent(100_000_000, 200);
        $venueUser = User::factory()->venue()->create();
        $venueUser->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        $makeVenue = function (string $name, int $capacity, int $price, bool $verified = true) use ($venueUser) {
            return Venue::factory()->create([
                'venue_profile_id' => $venueUser->venueProfile->id,
                'name' => $name,
                'capacity' => $capacity,
                'rental_price' => $price,
                'verification_status' => $verified ? VerificationStatus::Terverifikasi : VerificationStatus::Menunggu,
            ]);
        };

        $tooSmall = $makeVenue('Terlalu kecil', 100, 10_000_000);
        $tooSmall->rooms()->create(['name' => 'A', 'capacity' => 100]);

        $tooExpensive = $makeVenue('Terlalu mahal', 500, 90_000_000);
        $tooExpensive->rooms()->create(['name' => 'B', 'capacity' => 500]);

        $unverified = $makeVenue('Belum verifikasi', 500, 10_000_000, false);
        $unverified->rooms()->create(['name' => 'C', 'capacity' => 500]);

        $booked = $makeVenue('Sudah dipesan', 500, 10_000_000);
        $bookedRoom = $booked->rooms()->create(['name' => 'D', 'capacity' => 500]);
        OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueUser->venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $bookedRoom->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'usage_date' => $this->date,
        ]);

        $ok = $makeVenue('Venue Cocok', 400, 20_000_000);
        $okRoom = $ok->rooms()->create(['name' => 'E', 'capacity' => 300]);

        $result = $this->recommender->recommend($event);
        $venueCategory = collect($result['categories'])->firstWhere('key', 'venue');
        $names = array_column($venueCategory['candidates'], 'name');

        $this->assertSame(['Venue Cocok'], $names);
        $this->assertSame($okRoom->id, $venueCategory['candidates'][0]['subject_id']);
        $this->assertSame('venue_room', $venueCategory['candidates'][0]['subject_type']);
        $this->assertSame(20_000_000, $venueCategory['candidates'][0]['price']);
    }

    public function test_filter_layanan_dan_peralatan_berdasarkan_pemetaan_kategori(): void
    {
        $event = $this->draftEvent(100_000_000, 200);
        $vendor = $this->verifiedVendor();

        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Katering Nusantara',
            'kind' => CatalogKind::Layanan,
            'category' => 'Katering & F&B',
            'catalog_price' => 25_000_000,
            'status' => CatalogItemStatus::Aktif,
        ]);
        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Dekorasi Rustic',
            'kind' => CatalogKind::Layanan,
            'category' => 'Dekorasi',
            'catalog_price' => 12_000_000,
            'status' => CatalogItemStatus::Aktif,
        ]);
        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Kursi Tiffany',
            'kind' => CatalogKind::Peralatan,
            'category' => 'Peralatan Pesta',
            'catalog_price' => 4_000_000,
            'status' => CatalogItemStatus::Aktif,
            'max_per_day' => 10,
        ]);
        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Fotografi',
            'kind' => CatalogKind::Layanan,
            'category' => 'Fotografi',
            'catalog_price' => 5_000_000,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $result = $this->recommender->recommend($event);
        $byKey = collect($result['categories'])->keyBy('key');

        $this->assertSame(['Katering Nusantara'], array_column($byKey['catering']['candidates'], 'name'));
        $this->assertSame(['Dekorasi Rustic'], array_column($byKey['dekorasi']['candidates'], 'name'));
        $this->assertSame(['Kursi Tiffany'], array_column($byKey['peralatan']['candidates'], 'name'));
        $this->assertSame([], $byKey['hiburan']['candidates']);
        $this->assertSame('Belum ada pilihan yang cocok untuk alokasi ini', $byKey['hiburan']['message']);
    }

    public function test_urutan_rating_lalu_harga_dan_maksimal_tiga(): void
    {
        $event = $this->draftEvent(100_000_000, 200);
        $vendorA = $this->verifiedVendor();
        $vendorB = $this->verifiedVendor();
        $vendorC = $this->verifiedVendor();

        $items = [];
        foreach ([['A', $vendorA, 10_000_000], ['B', $vendorB, 5_000_000], ['C', $vendorC, 1_000_000]] as [$label, $vendor, $price]) {
            $items[$label] = CatalogItem::factory()->create([
                'vendor_profile_id' => $vendor->vendorProfile->id,
                'name' => 'Paket '.$label,
                'kind' => CatalogKind::Layanan,
                'category' => 'Katering',
                'catalog_price' => $price,
                'status' => CatalogItemStatus::Aktif,
            ]);
        }

        // Rating: A 4.0, B 4.0 (harga lebih murah menang), C tanpa rating.
        foreach ([[4], [4]] as $index => $ratings) {
            $evaluation = Evaluation::factory()->create(['event_id' => Event::factory()->create()->id]);
            $target = $index === 0 ? $vendorA->vendorProfile->id : $vendorB->vendorProfile->id;
            EvaluationItem::factory()->create([
                'evaluation_id' => $evaluation->id,
                'target_type' => 'vendor',
                'target_id' => $target,
                'rating' => $ratings[0],
            ]);
        }

        $result = $this->recommender->recommend($event);
        $catering = collect($result['categories'])->firstWhere('key', 'catering');

        $this->assertSame(['Paket B', 'Paket A', 'Paket C'], array_column($catering['candidates'], 'name'));
        $this->assertCount(3, $catering['candidates']);
    }

    public function test_ringkasan_tidak_melebihi_anggaran_dan_tanpa_float(): void
    {
        $event = $this->draftEvent(50_000_000, 100);
        $vendor = $this->verifiedVendor();

        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Katering Hemat',
            'kind' => CatalogKind::Layanan,
            'category' => 'Catering',
            'catalog_price' => 10_000_000,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $result = $this->recommender->recommend($event);
        $summary = $result['summary'];

        $this->assertLessThanOrEqual(50_000_000, $summary['total']);
        $this->assertSame(50_000_000 - $summary['total'], $summary['remaining']);
        $this->assertIsInt($summary['total']);
        $this->assertIsInt($summary['remaining']);

        foreach ($summary['items'] as $item) {
            $this->assertIsInt($item['price']);
        }
    }

    public function test_halaman_rekomendasi_hanya_pemilik_dan_hanya_draft_tanpa_menulis_database(): void
    {
        $event = $this->draftEvent(100_000_000, 100);
        $owner = $event->client;
        $other = User::factory()->klien()->create();

        $this->actingAs($owner)->get(route('klien.acara.rekomendasi', $event))
            ->assertRedirect(route('klien.acara.detail', $event).'#rekomendasi');

        $orderItemsBefore = OrderItem::count();

        $this->actingAs($owner)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Rekomendasi otomatis berdasarkan anggaran, jumlah tamu, tanggal, dan aturan alokasi. Estimasi sudah termasuk komisi layanan dan biaya kebersihan venue; angka final tertera pada tagihan dan dapat berbeda bila pengaturan komisi berubah.')
            ->assertSee('Dana cadangan');

        $this->assertSame($orderItemsBefore, OrderItem::count());

        $this->actingAs($other)->get(route('klien.acara.rekomendasi', $event))->assertForbidden();

        // Acara non-draft tidak menampilkan rekomendasi.
        $event->update(['status' => EventStatus::Terkonfirmasi]);
        $this->actingAs($owner)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertDontSee('Rekomendasi otomatis berdasarkan anggaran');
    }

    public function test_harga_rekomendasi_sama_dengan_harga_saat_item_ditambahkan(): void
    {
        $event = $this->draftEvent(100_000_000, 100);
        $vendor = $this->verifiedVendor();

        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Katering Acuan',
            'kind' => CatalogKind::Layanan,
            'category' => 'Catering',
            'catalog_price' => 12_345_000,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $result = $this->recommender->recommend($event);
        $candidate = collect($result['categories'])->firstWhere('key', 'catering')['candidates'][0];

        // F2: `price` tetap harga penyedia (sama dengan provider_price);
        // `estimate` adalah angka yang dipakai filter/urutan/ringkasan.
        $this->assertSame(12_345_000, $candidate['price']);
        $this->assertSame(1_234_500, $candidate['commission']);
        $this->assertSame(0, $candidate['cleaning_fee']);
        $this->assertSame(13_579_500, $candidate['estimate']);

        $this->actingAs($event->client)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
        ])->assertRedirect();

        $orderItem = OrderItem::where('event_id', $event->id)->firstOrFail();
        $this->assertSame($candidate['price'], $orderItem->provider_price);
    }

    public function test_data_tidak_lengkap_menampilkan_empty_state_jujur(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Draft,
            'budget' => 0,
            'guest_count' => 0,
            'event_date' => $this->date,
        ]);

        $result = $this->recommender->recommend($event);

        $this->assertFalse($result['ready']);
        $this->assertContains('anggaran acara', $result['missing']);

        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Rekomendasi belum dapat dibuat');
    }
}
