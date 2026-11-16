<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreCatalogItemRequest;
use App\Http\Requests\Vendor\ToggleCatalogItemStatusRequest;
use App\Http\Requests\Vendor\UpdateCatalogItemRequest;
use App\Models\CatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3: CRUD katalog vendor (B15 satu tabel catalog_items, kind layanan/
 * peralatan). Kepemilikan selalu dari akun login; item baru langsung
 * aktif dan visibilitas publik mengikuti verifikasi penyedia (keputusan
 * Gate 4B butir 2). Hapus = soft delete; foto dikelola MediaController.
 */
class CatalogItemController extends Controller
{
    /**
     * Daftar layanan milik vendor login (termasuk nonaktif).
     */
    public function layanan(Request $request): View
    {
        return $this->index($request, CatalogKind::Layanan, 'vendor.layanan');
    }

    /**
     * Daftar peralatan milik vendor login (termasuk nonaktif).
     */
    public function peralatan(Request $request): View
    {
        return $this->index($request, CatalogKind::Peralatan, 'vendor.peralatan');
    }

    /**
     * Form tambah layanan.
     */
    public function createLayanan(Request $request): View
    {
        Gate::authorize('create', CatalogItem::class);

        return view('vendor.item-form', ['item' => null, 'kind' => CatalogKind::Layanan]);
    }

    /**
     * Form tambah peralatan.
     */
    public function createPeralatan(Request $request): View
    {
        Gate::authorize('create', CatalogItem::class);

        return view('vendor.peralatan-form', ['item' => null, 'kind' => CatalogKind::Peralatan]);
    }

    /**
     * Form ubah item; tampilan mengikuti kind item.
     */
    public function edit(Request $request, CatalogItem $item): View
    {
        Gate::authorize('update', $item);

        $view = $item->kind === CatalogKind::Layanan ? 'vendor.item-form' : 'vendor.peralatan-form';

        return view($view, ['item' => $item, 'kind' => $item->kind]);
    }

    /**
     * Simpan item baru untuk profil vendor login; status selalu aktif.
     */
    public function store(StoreCatalogItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', CatalogItem::class);

        $item = $request->user()->vendorProfile->catalogItems()->create(
            $request->validated() + ['status' => CatalogItemStatus::Aktif]
        );

        return redirect()->route($this->listRoute($item->kind))
            ->with('status', 'Item katalog berhasil ditambahkan.');
    }

    /**
     * Perbarui item milik sendiri; kind/kepemilikan/status tidak dari input.
     */
    public function update(UpdateCatalogItemRequest $request, CatalogItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return redirect()->back()->with('status', 'Perubahan item berhasil disimpan.');
    }

    /**
     * Aktifkan/nonaktifkan item milik sendiri.
     */
    public function toggleStatus(ToggleCatalogItemStatusRequest $request, CatalogItem $item): RedirectResponse
    {
        Gate::authorize('toggleStatus', $item);

        $item->update(['status' => $request->validated('status')]);

        return redirect()->back()->with('status', 'Status item berhasil diperbarui.');
    }

    /**
     * Soft delete item milik sendiri; riwayat pesanan tetap aman.
     */
    public function destroy(Request $request, CatalogItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $kind = $item->kind;
        $item->delete();

        return redirect()->route($this->listRoute($kind))
            ->with('status', 'Item katalog berhasil dihapus.');
    }

    /**
     * Daftar per kind untuk halaman portal vendor.
     */
    protected function index(Request $request, CatalogKind $kind, string $view): View
    {
        $items = $request->user()->vendorProfile->catalogItems()
            ->where('kind', $kind)
            ->latest()
            ->get();

        return view($view, ['items' => $items, 'kind' => $kind]);
    }

    /**
     * Nama rute daftar sesuai kind item.
     */
    protected function listRoute(CatalogKind $kind): string
    {
        return $kind === CatalogKind::Layanan ? 'vendor.layanan' : 'vendor.peralatan';
    }
}
