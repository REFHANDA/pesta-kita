<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only tinjauan katalog Admin (keputusan Gate 4B butir 2: tidak
 * ada persetujuan per item — item dipublik oleh vendornya sendiri). Halaman
 * ini hanya membaca data nyata `catalog_items` + `venues`; perubahan status
 * item dilakukan penyedia di portalnya, harga lewat pengajuan harga, dan
 * venue lewat formulir venue Admin yang sudah terhubung.
 */
class CatalogController extends Controller
{
    /**
     * Daftar seluruh item katalog dengan filter jenis/status dari query.
     */
    public function index(Request $request): View
    {
        $kind = CatalogKind::tryFrom((string) $request->query('jenis'));
        $status = CatalogItemStatus::tryFrom((string) $request->query('status'));

        return view('admin.katalog', [
            'items' => CatalogItem::query()
                ->when($kind, fn ($q) => $q->where('kind', $kind))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->with('vendorProfile')
                ->orderByDesc('updated_at')
                ->get(),
            'venues' => Venue::with('venueProfile')->orderByDesc('updated_at')->get(),
        ]);
    }

    /**
     * Detail satu item katalog nyata milik penyedia.
     */
    public function show(CatalogItem $item): View
    {
        return view('admin.katalog-detail', [
            'item' => $item->load('vendorProfile'),
        ]);
    }
}
