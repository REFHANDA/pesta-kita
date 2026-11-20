<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Venue;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kalender ketersediaan untuk Klien yang menampilkan jadwal ketersediaan vendor & venue,
 * menggabungkan input manual penyedia dan status pesanan menahan via AvailabilityService.
 */
class CalendarController extends Controller
{
    /**
     * Kalender ketersediaan vendor (item) dan venue (ruangan) publik.
     */
    public function index(Request $request, AvailabilityService $availabilityService): View
    {
        $items = CatalogItem::publikUntukKlien()->with('vendorProfile')->orderBy('name')->get();

        $venues = Venue::publikUntukKlien()->with([
            'rooms' => fn ($query) => $query->orderBy('name'),
        ])->orderBy('name')->get();

        // Siapkan dataset ketersediaan dengan overlay pesanan menahan dari AvailabilityService
        $datasets = collect($items->map(fn ($item) => [
            'key' => 'item-'.$item->id,
            'kategori' => 'vendor',
            'nama' => $item->vendorProfile?->business_name ?? 'Vendor',
            'jenis' => $item->name.' ('.($item->kind->value === 'layanan' ? 'Layanan' : 'Peralatan').')',
            'days' => (object) $availabilityService->getMergedAvailabilityMap($item),
            'subject_id' => $item->id,
            'subject_type' => 'catalog_item',
        ])->all())
            ->merge($venues->flatMap(fn ($venue) => $venue->rooms->map(fn ($room) => [
                'key' => 'room-'.$room->id,
                'kategori' => 'venue',
                'nama' => $venue->name,
                'jenis' => $room->name.' · kapasitas '.$room->capacity,
                'days' => (object) $availabilityService->getMergedAvailabilityMap($room),
                'subject_id' => $room->id,
                'subject_type' => 'venue_room',
            ]))->all())
            ->all();

        $draftEvents = $request->user()?->events()
            ->where('status', EventStatus::Draft->value)
            ->orderBy('name')
            ->get() ?? collect();

        return view('klien.kalender-ketersediaan', [
            'items' => $items,
            'venues' => $venues,
            'customDatasets' => $datasets,
            'draftEvents' => $draftEvents,
        ]);
    }
}
