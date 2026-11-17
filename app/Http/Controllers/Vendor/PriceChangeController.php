<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\CatalogItemStatus;
use App\Enums\PriceChangeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StorePriceChangeRequest;
use App\Models\CatalogItem;
use App\Models\AuditLog;
use App\Models\PriceChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3b: pengajuan perubahan harga item katalog oleh vendor. old_price
 * selalu dibaca server dari harga aktif (aturan terkunci A11); harga
 * katalog tidak pernah berubah dari halaman ini — hanya lewat keputusan
 * Admin. Satu item hanya boleh punya satu pengajuan menunggu.
 */
class PriceChangeController extends Controller
{
    /**
     * Riwayat pengajuan harga item milik vendor login + form pengajuan.
     */
    public function index(Request $request): View
    {
        $itemIds = $this->itemIds($request);

        $requests = PriceChangeRequest::with('subject')
            ->where('subject_type', 'catalog_item')
            ->whereIn('subject_id', $itemIds)
            ->latest()
            ->get();

        return view('vendor.pengajuan-harga', [
            'requests' => $requests,
            'items' => $this->formItems($request),
            'statusFilter' => $this->statusFilter($request),
        ]);
    }

    /**
     * Kirim pengajuan harga baru ke antrean Admin.
     */
    public function store(StorePriceChangeRequest $request): RedirectResponse
    {
        $item = CatalogItem::findOrFail($request->validated('item_id'));
        Gate::authorize('update', $item);

        if ((int) $request->validated('new_price') === (int) $item->catalog_price) {
            return redirect()->back()->withErrors([
                'new_price' => 'Harga baru harus berbeda dari harga saat ini.',
            ])->withInput();
        }

        if ($item->priceChangeRequests()->where('status', PriceChangeStatus::Menunggu)->exists()) {
            return redirect()->back()->withErrors([
                'item_id' => 'Item ini masih punya pengajuan yang menunggu keputusan Admin.',
            ]);
        }

        $priceChange = PriceChangeRequest::create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'old_price' => $item->catalog_price,
            'new_price' => $request->validated('new_price'),
            'reason' => $request->validated('reason'),
            'status' => PriceChangeStatus::Menunggu,
        ]);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'price_change_submitted',
            'subject_type' => $priceChange->getMorphClass(),
            'subject_id' => $priceChange->id,
            'to_status' => PriceChangeStatus::Menunggu->value,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('vendor.pengajuan-harga')
            ->with('status', 'Pengajuan harga terkirim ke Admin. Harga lama tetap berlaku sampai diputuskan.');
    }

    /**
     * Filter status riwayat dari query (?status=), null untuk semua.
     */
    protected function statusFilter(Request $request): ?string
    {
        $status = (string) $request->query('status', '');

        return in_array($status, array_column(PriceChangeStatus::cases(), 'value'), true)
            ? $status
            : null;
    }

    /**
     * ID item katalog milik vendor login.
     *
     * @return array<int, int>
     */
    protected function itemIds(Request $request): array
    {
        $profile = $request->user()->vendorProfile;

        return $profile === null
            ? []
            : $profile->catalogItems()->pluck('id')->all();
    }

    /**
     * Item aktif yang dapat diajukan perubahan harganya.
     *
     * @return Collection<int, CatalogItem>
     */
    protected function formItems(Request $request): Collection
    {
        $profile = $request->user()->vendorProfile;

        if ($profile === null) {
            return collect();
        }

        return $profile->catalogItems()
            ->where('status', CatalogItemStatus::Aktif)
            ->orderBy('name')
            ->get();
    }
}
