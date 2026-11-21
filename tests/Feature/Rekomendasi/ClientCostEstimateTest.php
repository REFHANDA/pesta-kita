<?php

namespace Tests\Feature\Rekomendasi;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venue;
use App\Services\ClientCostEstimator;
use App\Services\InvoiceIssuer;
use App\Services\PackageRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap F2: rekomendasi memakai estimasi biaya Klien (harga penyedia +
 * komisi + kebersihan venue) dan angkanya identik dengan tagihan nyata.
 */
class ClientCostEstimateTest extends TestCase
{
    use RefreshDatabase;

    protected PackageRecommender $recommender;

    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recommender = app(PackageRecommender::class);
        $this->date = now()->addDays(30)->format('Y-m-d');
        Setting::set('commission_percent', 10);
    }

    protected function draftEvent(int $budget = 100_000_000, int $guests = 100): Event
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

    protected function cateringItem(User $vendor, int $price, string $name = 'Katering Acuan'): CatalogItem
    {
        return CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => $name,
            'kind' => CatalogKind::Layanan,
            'category' => 'Catering',
            'catalog_price' => $price,
            'status' => CatalogItemStatus::Aktif,
        ]);
    }

    /**
     * Alokasi seluruh anggaran ke satu kategori agar batas uji tepat.
     */
    protected function allocateAllToCatering(): void
    {
        Setting::set('budget_allocation', [
            'venue' => 0,
            'catering' => 100,
            'dekorasi' => 0,
            'hiburan' => 0,
            'peralatan' => 0,
            'cadangan' => 0,
        ]);
    }

    public function test_estimasi_komisi_sepuluh_persen_menentukan_lolos_tidaknya_kandidat(): void
    {
        $this->allocateAllToCatering();
        $vendor = $this->verifiedVendor();
        $this->cateringItem($vendor, 10_000_000);

        // Estimasi 11.000.000 > alokasi 10.500.000 → tersingkir.
        $sempit = $this->draftEvent(10_500_000);
        $hasilSempit = collect($this->recommender->recommend($sempit)['categories'])->firstWhere('key', 'catering');
        $this->assertSame([], $hasilSempit['candidates']);
        $this->assertSame('Belum ada pilihan yang cocok untuk alokasi ini', $hasilSempit['message']);

        // Estimasi 11.000.000 ≤ alokasi 11.000.000 → lolos.
        $pas = $this->draftEvent(11_000_000);
        $hasilPas = collect($this->recommender->recommend($pas)['categories'])->firstWhere('key', 'catering');
        $kandidat = $hasilPas['candidates'][0];

        $this->assertSame(10_000_000, $kandidat['price']);
        $this->assertSame(1_000_000, $kandidat['commission']);
        $this->assertSame(0, $kandidat['cleaning_fee']);
        $this->assertSame(11_000_000, $kandidat['estimate']);
    }

    public function test_pembulatan_komisi_setengah_ke_atas_tanpa_float(): void
    {
        $estimator = app(ClientCostEstimator::class);

        $this->assertSame(100_001, $estimator->commission(1_000_005));

        $item = $estimator->catalogItem(1_000_005);

        $this->assertSame(1_000_005, $item['price']);
        $this->assertSame(100_001, $item['commission']);
        $this->assertSame(1_100_006, $item['estimate']);
        $this->assertIsInt($item['estimate']);
    }

    public function test_venue_estimasi_termasuk_komisi_dan_kebersihan_tanpa_komisi_atas_kebersihan(): void
    {
        $estimator = app(ClientCostEstimator::class);

        $venue = $estimator->venue(20_000_000, 1_500_000);

        $this->assertSame(20_000_000, $venue['price']);
        $this->assertSame(2_000_000, $venue['commission']);
        $this->assertSame(1_500_000, $venue['cleaning_fee']);
        $this->assertSame(23_500_000, $venue['estimate']);

        $tanpaKebersihan = $estimator->venue(20_000_000, null);
        $this->assertSame(0, $tanpaKebersihan['cleaning_fee']);
        $this->assertSame(22_000_000, $tanpaKebersihan['estimate']);
    }

    public function test_kandidat_venue_memakai_estimasi_dari_harga_sewa_dan_biaya_kebersihan(): void
    {
        $event = $this->draftEvent(100_000_000, 100);
        $venueUser = User::factory()->venue()->create();
        $venueUser->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueUser->venueProfile->id,
            'capacity' => 500,
            'rental_price' => 20_000_000,
            'cleaning_fee' => 1_500_000,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $venue->rooms()->create(['name' => 'Aula', 'capacity' => 300]);

        $kategori = collect($this->recommender->recommend($event)['categories'])->firstWhere('key', 'venue');
        $kandidat = $kategori['candidates'][0];

        $this->assertSame(20_000_000, $kandidat['price']);
        $this->assertSame(2_000_000, $kandidat['commission']);
        $this->assertSame(1_500_000, $kandidat['cleaning_fee']);
        $this->assertSame(23_500_000, $kandidat['estimate']);
    }

    public function test_perubahan_commission_percent_langsung_mengubah_estimasi(): void
    {
        $this->allocateAllToCatering();
        $vendor = $this->verifiedVendor();
        $this->cateringItem($vendor, 10_000_000);
        $event = $this->draftEvent(50_000_000);

        $awal = collect($this->recommender->recommend($event)['categories'])->firstWhere('key', 'catering');
        $this->assertSame(11_000_000, $awal['candidates'][0]['estimate']);

        Setting::set('commission_percent', 15);

        $naik = collect($this->recommender->recommend($event)['categories'])->firstWhere('key', 'catering');
        $this->assertSame(1_500_000, $naik['candidates'][0]['commission']);
        $this->assertSame(11_500_000, $naik['candidates'][0]['estimate']);
    }

    public function test_ui_menampilkan_estimasi_sebagai_angka_utama_dan_rinciannya(): void
    {
        $event = $this->draftEvent(100_000_000, 100);
        $vendor = $this->verifiedVendor();
        $this->cateringItem($vendor, 10_000_000);

        $this->actingAs($event->client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Estimasi biaya')
            ->assertSee('Rp 11.000.000')
            ->assertSee('Harga penyedia Rp 10.000.000 + komisi layanan Rp 1.000.000');
    }

    public function test_ringkasan_memakai_estimasi_dan_bilangan_bulat(): void
    {
        $event = $this->draftEvent(50_000_000, 100);
        $vendor = $this->verifiedVendor();
        $this->cateringItem($vendor, 10_000_000, 'Katering Hemat');

        $hasil = $this->recommender->recommend($event);
        $summary = $hasil['summary'];

        $this->assertSame(11_000_000, $summary['total']);
        $this->assertSame(39_000_000, $summary['remaining']);
        $this->assertSame(11_000_000, $summary['items'][0]['estimate']);
        $this->assertLessThanOrEqual(50_000_000, $summary['total']);
        $this->assertIsInt($summary['total']);
        $this->assertIsInt($summary['remaining']);
    }

    public function test_total_estimasi_rekomendasi_sama_dengan_total_tagihan_tanpa_kode_unik(): void
    {
        $event = $this->draftEvent(100_000_000, 100);
        $client = $event->client;

        $venueUser = User::factory()->venue()->create();
        $venueUser->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueUser->venueProfile->id,
            'capacity' => 500,
            'rental_price' => 20_000_000,
            'cleaning_fee' => 1_500_000,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $room = $venue->rooms()->create(['name' => 'Aula', 'capacity' => 300]);

        $vendor = $this->verifiedVendor();
        $item = $this->cateringItem($vendor, 10_000_000);

        $rekomendasi = $this->recommender->recommend($event);
        $kandidatVenue = collect($rekomendasi['categories'])->firstWhere('key', 'venue')['candidates'][0];
        $kandidatCatering = collect($rekomendasi['categories'])->firstWhere('key', 'catering')['candidates'][0];

        $this->assertSame($room->id, $kandidatVenue['subject_id']);
        $this->assertSame(23_500_000, $kandidatVenue['estimate']);
        $this->assertSame(11_000_000, $kandidatCatering['estimate']);

        $totalEstimasi = $kandidatVenue['estimate'] + $kandidatCatering['estimate'];

        // Alur nyata: klien menambah item, mengajukan, Admin menyetujui.
        $this->actingAs($client)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
        ])->assertRedirect();

        $this->actingAs($client)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
        ])->assertRedirect();

        $this->actingAs($client)->post(route('klien.acara.ajukan', $event))->assertRedirect();

        $admin = User::factory()->admin()->create();
        foreach (OrderItem::where('event_id', $event->id)->get() as $orderItem) {
            $this->actingAs($admin)
                ->post(route('admin.pesanan.approve', $orderItem))
                ->assertRedirect();

            // Masa keberatan dilewati agar tagihan boleh terbit.
            $orderItem->update(['objection_deadline' => now()->subMinute()]);
        }

        $invoice = app(InvoiceIssuer::class)->issueForEvent($event->fresh());

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame($totalEstimasi, $invoice->grand_total);
        $this->assertSame(30_000_000, $invoice->subtotal_provider);
        $this->assertSame(3_000_000, $invoice->total_commission);
        $this->assertSame(1_500_000, $invoice->cleaning_fee);
        $this->assertSame($invoice->grand_total + $invoice->unique_code, $invoice->transfer_total);
    }
}
