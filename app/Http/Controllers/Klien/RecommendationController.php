<?php

namespace App\Http\Controllers\Klien;

use App\Enums\AvailabilityStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    /**
     * Show currently listed providers filtered by event needs.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'budget' => ['nullable', 'integer', 'min:0'],
        ]);

        $itemsQuery = CatalogItem::publikUntukKlien()
            ->with('vendorProfile')
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['area'] ?? null, fn (Builder $query, string $area) => $query->whereHas('vendorProfile', fn (Builder $profile) => $profile
                ->where('service_area', 'like', '%'.$area.'%')
                ->orWhere('address', 'like', '%'.$area.'%')))
            ->when($filters['event_date'] ?? null, fn (Builder $query, string $date) => $query
                ->whereHas('availabilityDays', fn (Builder $availability) => $availability
                    ->whereDate('date', $date)
                    ->where('status', AvailabilityStatus::Tersedia->value))
                ->whereDoesntHave('orderItems', fn (Builder $orders) => $orders->whereHas('event', fn (Builder $event) => $event
                    ->whereDate('event_date', $date)
                    ->where('status', EventStatus::Terkonfirmasi->value))));

        $venuesQuery = Venue::publikUntukKlien()
            ->with('venueProfile')
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('kind', $category))
            ->when($filters['area'] ?? null, fn (Builder $query, string $area) => $query->where(function (Builder $location) use ($area): void {
                $location->where('address', 'like', '%'.$area.'%')
                    ->orWhereHas('venueProfile', fn (Builder $profile) => $profile->where('service_area', 'like', '%'.$area.'%'));
            }))
            ->when($filters['event_date'] ?? null, fn (Builder $query, string $date) => $query->whereHas('rooms', fn (Builder $room) => $room
                ->whereHas('availabilityDays', fn (Builder $availability) => $availability
                    ->whereDate('date', $date)
                    ->where('status', AvailabilityStatus::Tersedia->value))
                ->whereDoesntHave('orderItems', fn (Builder $orders) => $orders->whereHas('event', fn (Builder $event) => $event
                    ->whereDate('event_date', $date)
                    ->where('status', EventStatus::Terkonfirmasi->value)))));

        $items = $itemsQuery->latest()->get()->map(function (CatalogItem $item): CatalogItem {
            $item->setAttribute('estimated_total', $item->catalog_price + (int) ceil($item->catalog_price * 0.1));

            return $item;
        });

        $venues = $venuesQuery->latest()->get()->map(function (Venue $venue): Venue {
            $venue->setAttribute('estimated_total', $venue->rental_price + (int) ceil($venue->rental_price * 0.1) + ($venue->cleaning_fee ?? 0));

            return $venue;
        });

        if (isset($filters['budget'])) {
            $items = $items->where('estimated_total', '<=', $filters['budget'])->values();
            $venues = $venues->where('estimated_total', '<=', $filters['budget'])->values();
        }

        $categories = CatalogItem::publikUntukKlien()->distinct()->orderBy('category')->pluck('category')
            ->merge(Venue::publikUntukKlien()->distinct()->orderBy('kind')->pluck('kind'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('klien.rekomendasi-index', [
            'items' => $items,
            'venues' => $venues,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }
}
