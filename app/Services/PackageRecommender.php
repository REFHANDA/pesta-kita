<?php

namespace App\Services;

use App\Enums\CatalogKind;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Setting;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Support\Collection;

/**
 * Rekomendasi paket berbasis anggaran (Tahap D1, estimasi biaya sejak F2).
 * Deterministik, tanpa AI, tanpa penulisan database.
 *
 * `price` tetap harga penyedia (catalog_price / rental_price) — sama dengan
 * `order_items.provider_price` saat item ditambahkan — sedangkan seluruh
 * filter, urutan, dan ringkasan memakai `estimate`: harga penyedia + komisi
 * layanan + (khusus venue) biaya kebersihan, sesuai tagihan nyata.
 */
class PackageRecommender
{
    /**
     * Maksimal kandidat per kategori.
     */
    public const MAX_CANDIDATES = 3;

    /**
     * Alokasi bawaan bila Pengaturan belum menyimpan budget_allocation.
     *
     * @var array<string, int>
     */
    public const DEFAULT_ALLOCATION = [
        'venue' => 35,
        'catering' => 30,
        'dekorasi' => 15,
        'hiburan' => 10,
        'peralatan' => 5,
        'cadangan' => 5,
    ];

    /**
     * Pemetaan kategori katalog (string bebas) ke kunci budget_allocation.
     * Kunci dinormalisasi huruf kecil; kategori tak terpetakan diabaikan.
     *
     * @var array<string, string>
     */
    public const CATEGORY_MAP = [
        'katering & f&b' => 'catering',
        'katering' => 'catering',
        'catering' => 'catering',
        'makanan & minuman' => 'catering',
        'dekorasi' => 'dekorasi',
        'dekorasi & tata rias' => 'dekorasi',
        'dekorasi & bunga' => 'dekorasi',
        'hiburan' => 'hiburan',
        'musik & hiburan' => 'hiburan',
        'entertainment' => 'hiburan',
    ];

    /**
     * Urutan kategori pada keluaran.
     *
     * @var array<int, string>
     */
    public const CATEGORY_ORDER = ['venue', 'catering', 'dekorasi', 'hiburan', 'peralatan'];

    /**
     * Label tampilan kategori.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        'venue' => 'Venue',
        'catering' => 'Catering',
        'dekorasi' => 'Dekorasi',
        'hiburan' => 'Hiburan',
        'peralatan' => 'Peralatan',
        'cadangan' => 'Dana cadangan',
    ];

    public function __construct(
        protected AvailabilityService $availability,
        protected RatingSummary $ratings,
        protected ClientCostEstimator $costs,
    ) {}

    /**
     * Seluruh keluaran rekomendasi untuk satu acara.
     *
     * @return array<string, mixed>
     */
    public function recommend(Event $event): array
    {
        $budget = (int) ($event->budget ?? 0);
        $guests = (int) ($event->guest_count ?? 0);
        $date = $event->event_date?->toDateString();

        $missing = [];
        if ($budget <= 0) {
            $missing[] = 'anggaran acara';
        }
        if ($guests <= 0) {
            $missing[] = 'jumlah tamu';
        }
        if ($date === null) {
            $missing[] = 'tanggal acara';
        }

        $allocations = $this->allocations($budget);

        if ($missing !== []) {
            return [
                'ready' => false,
                'missing' => $missing,
                'budget' => $budget,
                'guest_count' => $guests,
                'event_date' => $date,
                'allocations' => $allocations,
                'categories' => [],
                'summary' => ['items' => [], 'total' => 0, 'remaining' => $budget],
            ];
        }

        $categories = $this->candidates($event, $date, $guests, $allocations);

        return [
            'ready' => true,
            'missing' => [],
            'budget' => $budget,
            'guest_count' => $guests,
            'event_date' => $date,
            'allocations' => $allocations,
            'categories' => $categories,
            'summary' => $this->summary($categories, $budget),
        ];
    }

    /**
     * Alokasi integer per kategori; selisih pembulatan masuk cadangan.
     *
     * @return array<string, int>
     */
    public function allocations(int $budget): array
    {
        $setting = Setting::get('budget_allocation', self::DEFAULT_ALLOCATION);
        $percentages = is_array($setting) ? $setting : self::DEFAULT_ALLOCATION;

        $allocations = [];
        $used = 0;

        foreach (self::CATEGORY_ORDER as $key) {
            $percent = (int) ($percentages[$key] ?? 0);
            $allocations[$key] = intdiv($budget * $percent, 100);
            $used += $allocations[$key];
        }

        $allocations['cadangan'] = max(0, $budget - $used);

        return $allocations;
    }

    /**
     * Kandidat per kategori beserta pesan kosong.
     *
     * @param  array<string, int>  $allocations
     * @return array<int, array<string, mixed>>
     */
    protected function candidates(Event $event, string $date, int $guests, array $allocations): array
    {
        $venueCandidates = $this->venueCandidates($date, $guests, $allocations['venue']);

        // Prasaring aman: estimasi selalu ≥ harga penyedia, sehingga kandidat
        // dengan harga > alokasi terbesar pasti juga > alokasinya sendiri.
        // Prasaring ini hanya memangkas query, bukan menyaring kandidat sah.
        $items = CatalogItem::query()
            ->publikUntukKlien()
            ->with('vendorProfile')
            ->whereIn('kind', [CatalogKind::Layanan, CatalogKind::Peralatan])
            ->where('catalog_price', '<=', max($allocations))
            ->get();

        $itemRatings = $this->ratings->forVendors($items->pluck('vendor_profile_id')->unique()->all());

        $perCategory = [
            'catering' => $this->itemCandidates($items, $itemRatings, $date, 'catering', $allocations['catering']),
            'dekorasi' => $this->itemCandidates($items, $itemRatings, $date, 'dekorasi', $allocations['dekorasi']),
            'hiburan' => $this->itemCandidates($items, $itemRatings, $date, 'hiburan', $allocations['hiburan']),
            'peralatan' => $this->equipmentCandidates($items, $itemRatings, $date, $allocations['peralatan']),
        ];

        $result = [];
        foreach (self::CATEGORY_ORDER as $key) {
            $candidates = $key === 'venue' ? $venueCandidates : $perCategory[$key];

            $result[] = [
                'key' => $key,
                'label' => self::CATEGORY_LABELS[$key],
                'allocation' => $allocations[$key],
                'candidates' => $candidates,
                'message' => $candidates === [] ? 'Belum ada pilihan yang cocok untuk alokasi ini' : null,
            ];
        }

        return $result;
    }

    /**
     * Venue terverifikasi dengan kapasitas dan ruangan tersedia pada tanggal.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function venueCandidates(string $date, int $guests, int $allocation): array
    {
        if ($allocation <= 0) {
            return [];
        }

        // Prasaring aman: biaya kebersihan dan komisi hanya menambah estimasi,
        // jadi harga sewa > alokasi tidak mungkin menjadi kandidat sah.
        $venues = Venue::query()
            ->publikUntukKlien()
            ->with(['venueProfile', 'rooms'])
            ->where('capacity', '>=', $guests)
            ->where('rental_price', '<=', $allocation)
            ->orderBy('id')
            ->get();

        $ratings = $this->ratings->forVenues($venues->pluck('id')->all());

        $candidates = [];
        foreach ($venues as $venue) {
            $cost = $this->costs->venue((int) $venue->rental_price, $venue->cleaning_fee === null ? null : (int) $venue->cleaning_fee);

            if ($cost['estimate'] > $allocation) {
                continue;
            }

            $room = $venue->rooms->first(function (VenueRoom $room) use ($date, $guests): bool {
                if ($room->capacity !== null && (int) $room->capacity < $guests) {
                    return false;
                }

                return $this->availability->isVenueRoomAvailable($room, $date);
            });

            if ($room === null) {
                continue;
            }

            $candidates[] = [
                'subject_type' => 'venue_room',
                'subject_id' => $room->id,
                'name' => $venue->name,
                'provider' => $venue->venueProfile?->business_name,
                'price' => $cost['price'],
                'commission' => $cost['commission'],
                'cleaning_fee' => $cost['cleaning_fee'],
                'estimate' => $cost['estimate'],
                'rating' => $ratings[$venue->id] ?? ['average' => null, 'count' => 0],
                'meta' => 'Kapasitas '.number_format((int) $venue->capacity, 0, ',', '.').' tamu · ruangan '.$room->name,
            ];
        }

        return $this->sortAndLimit($candidates);
    }

    /**
     * Kandidat layanan vendor sesuai pemetaan kategori.
     *
     * @param  Collection<int, CatalogItem>  $items
     * @param  array<int, array{average: float|null, count: int}>  $itemRatings
     * @return array<int, array<string, mixed>>
     */
    protected function itemCandidates(Collection $items, array $itemRatings, string $date, string $categoryKey, int $allocation): array
    {
        if ($allocation <= 0) {
            return [];
        }

        $candidates = [];
        foreach ($items as $item) {
            if ($item->kind !== CatalogKind::Layanan) {
                continue;
            }

            if ($this->categoryKey($item->category) !== $categoryKey) {
                continue;
            }

            $cost = $this->costs->catalogItem((int) $item->catalog_price);

            if ($cost['estimate'] > $allocation) {
                continue;
            }

            if (! $this->availability->isCatalogItemAvailable($item, $date, 1)) {
                continue;
            }

            $candidates[] = [
                'subject_type' => 'catalog_item',
                'subject_id' => $item->id,
                'name' => $item->name,
                'provider' => $item->vendorProfile?->business_name,
                'price' => $cost['price'],
                'commission' => $cost['commission'],
                'cleaning_fee' => $cost['cleaning_fee'],
                'estimate' => $cost['estimate'],
                'rating' => $itemRatings[$item->vendor_profile_id] ?? ['average' => null, 'count' => 0],
                'meta' => $item->category,
            ];
        }

        return $this->sortAndLimit($candidates);
    }

    /**
     * Kandidat peralatan (dipetakan dari kind, bukan kategori teks).
     *
     * @param  Collection<int, CatalogItem>  $items
     * @param  array<int, array{average: float|null, count: int}>  $itemRatings
     * @return array<int, array<string, mixed>>
     */
    protected function equipmentCandidates(Collection $items, array $itemRatings, string $date, int $allocation): array
    {
        if ($allocation <= 0) {
            return [];
        }

        $candidates = [];
        foreach ($items as $item) {
            if ($item->kind !== CatalogKind::Peralatan) {
                continue;
            }

            $cost = $this->costs->catalogItem((int) $item->catalog_price);

            if ($cost['estimate'] > $allocation) {
                continue;
            }

            if (! $this->availability->isCatalogItemAvailable($item, $date, 1)) {
                continue;
            }

            $candidates[] = [
                'subject_type' => 'catalog_item',
                'subject_id' => $item->id,
                'name' => $item->name,
                'provider' => $item->vendorProfile?->business_name,
                'price' => $cost['price'],
                'commission' => $cost['commission'],
                'cleaning_fee' => $cost['cleaning_fee'],
                'estimate' => $cost['estimate'],
                'rating' => $itemRatings[$item->vendor_profile_id] ?? ['average' => null, 'count' => 0],
                'meta' => $item->category,
            ];
        }

        return $this->sortAndLimit($candidates);
    }

    /**
     * Urutan: rating menurun (null terakhir), estimasi biaya menaik, lalu id.
     * Maksimal tiga kandidat.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, array<string, mixed>>
     */
    protected function sortAndLimit(array $candidates): array
    {
        usort($candidates, function (array $a, array $b): int {
            $ratingA = $a['rating']['average'];
            $ratingB = $b['rating']['average'];

            if ($ratingA !== $ratingB) {
                if ($ratingA === null) {
                    return 1;
                }
                if ($ratingB === null) {
                    return -1;
                }

                return $ratingB <=> $ratingA;
            }

            if ($a['estimate'] !== $b['estimate']) {
                return $a['estimate'] <=> $b['estimate'];
            }

            return $a['subject_id'] <=> $b['subject_id'];
        });

        return array_slice($candidates, 0, self::MAX_CANDIDATES);
    }

    /**
     * Ringkasan paket: kandidat teratas yang estimasinya masih muat anggaran.
     *
     * @param  array<int, array<string, mixed>>  $categories
     * @return array{items: array<int, array<string, mixed>>, total: int, remaining: int}
     */
    protected function summary(array $categories, int $budget): array
    {
        $remaining = $budget;
        $items = [];

        foreach ($categories as $category) {
            foreach ($category['candidates'] as $candidate) {
                if ($candidate['estimate'] <= $remaining) {
                    $items[] = $candidate + ['category' => $category['label']];
                    $remaining -= $candidate['estimate'];

                    break;
                }
            }
        }

        $total = $budget - $remaining;

        return [
            'items' => $items,
            'total' => max(0, $total),
            'remaining' => max(0, $remaining),
        ];
    }

    /**
     * Kunci kategori ternormalisasi atau null bila tak terpetakan.
     */
    protected function categoryKey(?string $category): ?string
    {
        $normalized = strtolower(trim((string) $category));

        return self::CATEGORY_MAP[$normalized] ?? null;
    }
}
