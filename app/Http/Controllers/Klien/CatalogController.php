<?php

namespace App\Http\Controllers\Klien;

use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Enums\MediaVisibility;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BE-3: katalog publik untuk Klien — pembatasan di query backend
 * (scope publikUntukKlien), bukan sekadar sembunyi di frontend.
 * Detail subjek tidak publik menghasilkan 404, bukan 403, agar tidak
 * membocorkan keberadaan data. Kontak privat penyedia tidak pernah ikut.
 */
class CatalogController extends Controller
{
    /**
     * Katalog layanan vendor terverifikasi (filter server-side).
     */
    public function katalogVendor(Request $request): View
    {
        return $this->itemList($request, CatalogKind::Layanan, 'klien.katalog-vendor');
    }

    /**
     * Katalog peralatan vendor terverifikasi (filter server-side).
     */
    public function katalogPeralatan(Request $request): View
    {
        return $this->itemList($request, CatalogKind::Peralatan, 'klien.katalog-peralatan');
    }

    /**
     * Detail item layanan/peralatan; 404 bila tidak memenuhi syarat publik.
     */
    public function katalogVendorDetail(Request $request, CatalogItem $item): View
    {
        $item = CatalogItem::publikUntukKlien()->with('vendorProfile')->findOrFail($item->id);

        return view('klien.katalog-vendor-detail', [
            'item' => $item,
            'vendor' => $item->vendorProfile,
            'photos' => $this->publicPhotos($item),
            'draftEvents' => $request->user()?->events()->where('status', EventStatus::Draft->value)->orderBy('name')->get() ?? collect(),
        ]);
    }

    /**
     * Katalog venue tayang (profil terverifikasi + venue tidak nonaktif).
     */
    public function katalog(Request $request): View
    {
        $query = Venue::publikUntukKlien()->with('venueProfile');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q')->trim().'%');
        }
        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind')->trim());
        }
        if ($request->filled('max_price')) {
            $query->where('rental_price', '<=', (int) $request->input('max_price'));
        }

        return view('klien.katalog', [
            'venues' => $query->latest()->get(),
            'kinds' => Venue::publikUntukKlien()->distinct()->orderBy('kind')->pluck('kind'),
            'filters' => $request->only(['q', 'kind', 'max_price']),
        ]);
    }

    /**
     * Detail venue; 404 bila tidak memenuhi syarat publik.
     */
    public function katalogVenueDetail(Request $request, Venue $venue): View
    {
        $venue = Venue::publikUntukKlien()->with(['venueProfile', 'rooms', 'facilities'])->findOrFail($venue->id);

        return view('klien.katalog-venue-detail', [
            'venue' => $venue,
            'photos' => $this->publicPhotos($venue),
            'draftEvents' => $request->user()?->events()->where('status', EventStatus::Draft->value)->orderBy('name')->get() ?? collect(),
        ]);
    }

    /**
     * Profil publik vendor terverifikasi beserta item tayangnya.
     */
    public function penyediaVendor(Request $request, VendorProfile $profile): View
    {
        abort_unless($profile->verification_status === VerificationStatus::Terverifikasi, 404);

        return view('klien.penyedia-vendor', [
            'vendor' => $profile,
            'items' => $profile->catalogItems()->publikUntukKlien()->latest()->get(),
            'photos' => $this->publicPhotos($profile),
        ]);
    }

    /**
     * Profil publik pemilik venue terverifikasi beserta venue tayangnya.
     */
    public function penyediaVenue(Request $request, VenueProfile $profile): View
    {
        abort_unless($profile->verification_status === VerificationStatus::Terverifikasi, 404);

        return view('klien.penyedia-venue', [
            'provider' => $profile,
            'venues' => $profile->venues()->publikUntukKlien()->latest()->get(),
            'photos' => $this->publicPhotos($profile),
        ]);
    }

    /**
     * Daftar item publik per kind dengan filter nama/kategori/harga.
     */
    protected function itemList(Request $request, CatalogKind $kind, string $view): View
    {
        $query = CatalogItem::publikUntukKlien()->with('vendorProfile')->where('kind', $kind);

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q')->trim().'%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->trim());
        }
        if ($request->filled('max_price')) {
            $query->where('catalog_price', '<=', (int) $request->input('max_price'));
        }

        $categories = CatalogItem::publikUntukKlien()->where('kind', $kind)
            ->distinct()->orderBy('category')->pluck('category');

        return view($view, [
            'items' => $query->latest()->get(),
            'categories' => $categories,
            'filters' => $request->only(['q', 'category', 'max_price']),
        ]);
    }

    /**
     * Hanya foto bervisibility publik untuk tampilan Klien (B12).
     *
     * @return Collection<int, Media>
     */
    protected function publicPhotos(Model $owner): Collection
    {
        return $owner->media()->foto()
            ->where('visibility', MediaVisibility::Publik)
            ->orderBy('sort_order')
            ->get();
    }
}
