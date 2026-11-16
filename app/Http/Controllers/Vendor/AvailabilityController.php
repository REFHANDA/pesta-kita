<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateAvailabilityRequest;
use App\Models\CatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3b: kalender ketersediaan item katalog vendor. Status manual
 * (B11) disimpan per item per tanggal; status "dipesan" tidak pernah
 * diketik di sini — kelak diturunkan dari pesanan terkonfirmasi (BE-5).
 * Klien hanya melihat gambaran jadwal yang sama (read-only).
 */
class AvailabilityController extends Controller
{
    /**
     * Kalender item milik vendor login beserta tanggal yang sudah ditandai.
     */
    public function index(Request $request): View
    {
        $items = $this->items($request);

        return view('vendor.kalender-ketersediaan', [
            'items' => $items,
            'selectedItemId' => (int) $request->input('item', $items->first()?->id ?? 0),
        ]);
    }

    /**
     * Tandai/simpan status ketersediaan satu tanggal milik item sendiri.
     */
    public function update(UpdateAvailabilityRequest $request): RedirectResponse
    {
        $item = CatalogItem::findOrFail($request->validated('item_id'));
        Gate::authorize('update', $item);

        $item->availabilityDays()->updateOrCreate(
            ['date' => $request->validated('date')],
            ['status' => $request->validated('status')]
        );

        return redirect()
            ->route('vendor.kalender', ['item' => $item->id])
            ->with('status', 'Jadwal '.$request->validated('date').' tersimpan.');
    }

    /**
     * Item milik vendor login beserta jadwal aktifnya (untuk picker).
     *
     * @return Collection<int, CatalogItem>
     */
    protected function items(Request $request): Collection
    {
        $profile = $request->user()->vendorProfile;

        if ($profile === null) {
            return collect();
        }

        return $profile->catalogItems()->with(['availabilityDays' => fn ($query) => $query
            ->where('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->limit(180),
        ])
            ->orderBy('name')
            ->get();
    }
}
