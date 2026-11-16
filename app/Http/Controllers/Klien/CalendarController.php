<?php

namespace App\Http\Controllers\Klien;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BE-3b: kalender ketersediaan read-only untuk Klien. Hanya katalog yang
 * memenuhi syarat publik (item aktif dari vendor terverifikasi, venue
 * tayang dari profil terverifikasi — scope BE-3) yang dapat dilihat
 * jadwalnya. Status manual penyedia bukan jaminan; konfirmasi tetap lewat
 * proses penawaran dan persetujuan Admin.
 */
class CalendarController extends Controller
{
    /**
     * Kalender ketersediaan vendor (item) dan venue (ruangan) publik.
     */
    public function index(Request $request): View
    {
        $items = CatalogItem::publikUntukKlien()->with([
            'vendorProfile',
            'availabilityDays' => fn ($query) => $query
                ->where('date', '>=', today()->toDateString())
                ->orderBy('date')
                ->limit(180),
        ])->orderBy('name')->get();

        $venues = Venue::publikUntukKlien()->with([
            'rooms' => fn ($query) => $query->orderBy('name'),
            'rooms.availabilityDays' => fn ($query) => $query
                ->where('date', '>=', today()->toDateString())
                ->orderBy('date')
                ->limit(180),
        ])->orderBy('name')->get();

        return view('klien.kalender-ketersediaan', [
            'items' => $items,
            'venues' => $venues,
        ]);
    }
}
