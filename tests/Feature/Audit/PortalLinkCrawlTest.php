<?php

namespace Tests\Feature\Audit;

use App\Enums\CancellationStatus;
use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\Cancellation;
use App\Models\CatalogItem;
use App\Models\CompanyBankAccount;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RundownItem;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tahap F3: crawl tautan internal nyata per peran dari beranda sampai
 * kedalaman 2. Semua URL yang ditemukan harus berakhir pada respons 200 —
 * tidak ada 404/500 dan tidak ada redirect yang berakhir di luar 200.
 */
class PortalLinkCrawlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Batas jumlah URL per peran agar tes tetap cepat.
     */
    protected const MAX_URLS_PER_ROLE = 150;

    /**
     * Kedalaman maksimum tautan yang diikuti dari beranda.
     */
    protected const MAX_DEPTH = 2;

    protected string $date;

    /** @var array<string, User> */
    protected array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->date = now()->addDays(30)->format('Y-m-d');
        $this->buildRichFixture();
    }

    /**
     * Fixture kaya untuk semua peran: acara draft berisi item, acara
     * terkonfirmasi lunas dengan rundown, acara selesai + evaluasi,
     * pembatalan aktif dan selesai, pencairan belum cair dan cair,
     * notifikasi, serta venue dengan denah.
     */
    protected function buildRichFixture(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->klien()->create();

        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update([
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);

        $venueOwner = User::factory()->venue()->create();
        $venueOwner->venueProfile->update([
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);

        $this->users = compact('admin', 'client', 'vendor', 'venueOwner');

        $bank = CompanyBankAccount::factory()->create(['is_active' => true]);

        $catering = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Katering Uji',
            'kind' => CatalogKind::Layanan,
            'category' => 'Catering',
            'catalog_price' => 10_000_000,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueOwner->venueProfile->id,
            'name' => 'Graha Uji',
            'capacity' => 500,
            'rental_price' => 20_000_000,
            'cleaning_fee' => 1_500_000,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);

        $room = $venue->rooms()->create(['name' => 'Aula Utama', 'capacity' => 300]);

        // Venue dengan denah publik (tampil di detail venue Klien).
        $room->media()->create([
            'kind' => MediaKind::Denah,
            'path' => 'media/venue_room/'.$room->id.'/denah/denah.png',
            'disk' => 'private',
            'mime_type' => 'image/png',
            'size' => 1024,
            'original_name' => 'denah.png',
            'visibility' => MediaVisibility::Publik,
            'sort_order' => 0,
        ]);

        // 1) Acara draft dengan item.
        $draft = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Draft',
            'status' => EventStatus::Draft,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => $this->date,
        ]);

        $this->orderItem($draft, $catering, $vendor, OrderItemStatus::Draft, 10_000_000);
        $this->orderItem($draft, $room, $venueOwner, OrderItemStatus::Draft, 20_000_000);

        // 2) Acara diajukan: pesanan menunggu review Admin.
        $submitted = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Diajukan',
            'status' => EventStatus::Diajukan,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => $this->date,
        ]);

        $this->orderItem($submitted, $catering, $vendor, OrderItemStatus::MenungguReview, 10_000_000);

        // 3) Acara terkonfirmasi lunas dengan rundown dan pencairan belum cair.
        $confirmed = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Terkonfirmasi',
            'status' => EventStatus::Terkonfirmasi,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => $this->date,
        ]);

        $confirmedItem = $this->orderItem($confirmed, $catering, $vendor, OrderItemStatus::Terkonfirmasi, 10_000_000);
        $this->invoice($confirmed, $bank, InvoiceStatus::Lunas);

        $this->rundown($confirmed, $client, $confirmedItem);

        Disbursement::factory()->create([
            'order_item_id' => $confirmedItem->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'type' => DisbursementType::HakPesanan,
            'amount' => 10_000_000,
            'status' => DisbursementStatus::BelumCair,
        ]);

        // 4) Acara selesai + evaluasi dan pencairan cair.
        $completed = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Selesai',
            'status' => EventStatus::Dievaluasi,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $completedItem = $this->orderItem($completed, $catering, $vendor, OrderItemStatus::Terkonfirmasi, 10_000_000);
        $this->invoice($completed, $bank, InvoiceStatus::Lunas);
        $this->rundown($completed, $client, $completedItem);

        $evaluation = Evaluation::factory()->create(['event_id' => $completed->id]);
        $evaluation->items()->create([
            'target_type' => 'vendor',
            'target_id' => $vendor->vendorProfile->id,
            'rating' => 5,
            'comment' => 'Pelayanan baik.',
        ]);
        $evaluation->items()->create([
            'target_type' => 'venue',
            'target_id' => $venue->id,
            'rating' => 5,
            'comment' => 'Venue bersih.',
        ]);
        $evaluation->items()->create([
            'target_type' => 'admin',
            'target_id' => $admin->id,
            'rating' => 5,
            'comment' => 'Admin responsif.',
        ]);

        Disbursement::factory()->create([
            'order_item_id' => $completedItem->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'type' => DisbursementType::HakPesanan,
            'amount' => 10_000_000,
            'status' => DisbursementStatus::Cair,
            'paid_at' => now(),
            'recorded_by' => $admin->id,
        ]);

        // 5) Pembatalan aktif.
        $cancelling = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Pembatalan Aktif',
            'status' => EventStatus::Dibatalkan,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => $this->date,
        ]);

        $cancellingInvoice = $this->invoice($cancelling, $bank, InvoiceStatus::Lunas);

        Cancellation::factory()->create([
            'event_id' => $cancelling->id,
            'invoice_id' => $cancellingInvoice->id,
            'status' => CancellationStatus::Diajukan,
        ]);

        // 6) Pembatalan selesai + kompensasi penyedia cair.
        $cancelled = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Pembatalan Selesai',
            'status' => EventStatus::Dibatalkan,
            'budget' => 50_000_000,
            'guest_count' => 100,
            'event_date' => $this->date,
        ]);

        $cancelledItem = $this->orderItem($cancelled, $catering, $vendor, OrderItemStatus::Disetujui, 10_000_000);
        $cancelledInvoice = $this->invoice($cancelled, $bank, InvoiceStatus::Lunas);

        Cancellation::factory()->create([
            'event_id' => $cancelled->id,
            'invoice_id' => $cancelledInvoice->id,
            'status' => CancellationStatus::RefundSelesai,
            'refund_paid_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        Disbursement::factory()->create([
            'order_item_id' => $cancelledItem->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'type' => DisbursementType::Kompensasi,
            'amount' => 3_000_000,
            'status' => DisbursementStatus::Cair,
            'paid_at' => now(),
            'recorded_by' => $admin->id,
        ]);

        // 7) Notifikasi untuk tiap peran.
        foreach ([$client, $vendor, $venueOwner, $admin] as $user) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\EventCompletedNotification',
                'notifiable_type' => 'user',
                'notifiable_id' => $user->id,
                'data' => json_encode(['event_id' => $completed->id]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Buat satu baris pesanan untuk acara.
     */
    protected function orderItem(Event $event, object $subject, User $provider, OrderItemStatus $status, int $price): OrderItem
    {
        $isRoom = $subject instanceof VenueRoom;
        $locked = $status !== OrderItemStatus::Draft;

        return OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => $isRoom ? 'venue_profile' : 'vendor_profile',
            'provider_id' => $isRoom ? $provider->venueProfile->id : $provider->vendorProfile->id,
            'subject_type' => $isRoom ? 'venue_room' : 'catalog_item',
            'subject_id' => $subject->id,
            'usage_date' => $event->event_date,
            'catalog_price_snapshot' => $price,
            'provider_price' => $locked ? $price : null,
            'commission' => $locked ? intdiv($price * 10 + 50, 100) : 0,
            'status' => $status,
            'approved_at' => $status === OrderItemStatus::Draft || $status === OrderItemStatus::MenungguReview ? null : now(),
        ]);
    }

    /**
     * Tagihan lunas beserta pembayaran valid.
     */
    protected function invoice(Event $event, CompanyBankAccount $bank, InvoiceStatus $status): Invoice
    {
        $invoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Lunas ? now() : null,
        ]);

        if ($status === InvoiceStatus::Lunas) {
            Payment::factory()->valid()->create([
                'invoice_id' => $invoice->id,
                'company_bank_account_id' => $bank->id,
                'amount' => $invoice->transfer_total,
            ]);
        }

        return $invoice;
    }

    protected function rundown(Event $event, User $client, OrderItem $item): RundownItem
    {
        return RundownItem::factory()->create([
            'event_id' => $event->id,
            'created_by' => $client->id,
            'order_item_id' => $item->id,
        ]);
    }

    /**
     * Seluruh tautan internal dari HTML, memakai DOM parser.
     *
     * @return array<int, string>
     */
    protected function internalLinks(string $html): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $links = [];
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim((string) $anchor->getAttribute('href'));

            if ($href === '' || $this->isExcludedLink($href)) {
                continue;
            }

            $links[] = $this->normalizeUrl($href);
        }

        return array_values(array_unique(array_filter($links)));
    }

    /**
     * Pengecualian eksplisit: jangkar, eksternal, logout, dan unduhan/media.
     */
    protected function isExcludedLink(string $href): bool
    {
        if (str_starts_with($href, '#')) {
            return true;
        }

        foreach (['mailto:', 'tel:', 'javascript:', 'wa.me', 'api.whatsapp.com'] as $prefix) {
            if (str_contains($href, $prefix)) {
                return true;
            }
        }

        // Unduhan berkas/media dan halaman cetak/nota tagihan.
        foreach (['/media/', '/cetak', '/nota', '/unduh', '/download'] as $needle) {
            if (str_contains($href, $needle)) {
                return true;
            }
        }

        // Keluar sesi bukan halaman.
        if (str_contains($href, 'logout')) {
            return true;
        }

        $parts = parse_url($href);

        if ($parts === false) {
            return true;
        }

        // Tautan eksternal (berbeda host dengan aplikasi).
        if (isset($parts['host'])) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            return $appHost !== null && $parts['host'] !== $appHost;
        }

        return false;
    }

    /**
     * Ubah href menjadi path internal relatif (tanpa host) untuk GET.
     */
    protected function normalizeUrl(string $href): string
    {
        $parts = parse_url($href);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $path.$query;
    }

    /**
     * Ikuti redirect manual sampai berhenti, maksimal 5 lompatan.
     */
    protected function resolveFinalPage(User $user, TestResponse $response): TestResponse
    {
        $hops = 0;

        while ($response->isRedirect() && $hops < 5) {
            $location = $response->headers->get('Location');

            if ($location === null) {
                break;
            }

            $response = $this->actingAs($user)->get($this->normalizeUrl($location));
            $hops++;
        }

        return $response;
    }

    public function test_crawl_tautan_internal_per_peran_tanpa_tautan_mati(): void
    {
        // Beranda tiap peran + halaman indeks/tab miliknya sebagai titik awal
        // (kedalaman 0), agar halaman detail di balik tab katalog juga
        // tercakup dalam batas kedalaman 2.
        $starts = [
            'klien' => ['/klien', '/klien/acara', '/klien/katalog', '/klien/katalog-vendor', '/klien/katalog-peralatan'],
            'vendor' => ['/vendor', '/vendor/layanan', '/vendor/peralatan', '/vendor/pesanan', '/vendor/rundown', '/vendor/pencairan'],
            'venue' => ['/venue', '/venue/data', '/venue/pesanan', '/venue/rundown', '/venue/pencairan'],
            'admin' => [
                '/admin', '/admin/pesanan', '/admin/pembayaran', '/admin/pembatalan',
                '/admin/pencairan', '/admin/acara', '/admin/katalog', '/admin/admin-penyedia',
                '/admin/aktivitas', '/admin/pengaturan', '/admin/pengajuan-harga',
            ],
        ];

        $report = [];

        foreach ($starts as $role => $seeds) {
            $user = match ($role) {
                'klien' => $this->users['client'],
                'vendor' => $this->users['vendor'],
                'venue' => $this->users['venueOwner'],
                'admin' => $this->users['admin'],
            };

            $visited = [];
            $queue = array_map(fn (string $url): array => [$url, 0], $seeds);

            while ($queue !== [] && count($visited) < self::MAX_URLS_PER_ROLE) {
                [$url, $depth] = array_shift($queue);

                if (isset($visited[$url])) {
                    continue;
                }

                $visited[$url] = true;

                $response = $this->resolveFinalPage($user, $this->actingAs($user)->get($url));

                $this->assertSame(
                    200,
                    $response->getStatusCode(),
                    "Tautan mati pada portal {$role}: {$url} (status {$response->getStatusCode()})."
                );

                if ($depth >= self::MAX_DEPTH) {
                    continue;
                }

                foreach ($this->internalLinks($response->getContent()) as $link) {
                    if (! isset($visited[$link])) {
                        $queue[] = [$link, $depth + 1];
                    }
                }
            }

            $report[$role] = count($visited);
        }

        fwrite(STDERR, "\n[F3] URL dikunjungi per peran: ".json_encode($report)."\n");

        foreach ($report as $role => $count) {
            $this->assertGreaterThan(0, $count, "Crawl portal {$role} tidak menemukan URL.");
        }
    }
}
