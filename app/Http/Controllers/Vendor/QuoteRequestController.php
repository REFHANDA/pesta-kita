<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only permintaan penawaran yang masuk ke katalog Vendor login
 * (`quote_requests` dengan subject item milik profil akun). Pengusulan
 * harga oleh penyedia adalah alur tulis BE-4; halaman ini hanya menampilkan
 * permintaan dan usulan yang sudah tersimpan, tanpa form aksi.
 */
class QuoteRequestController extends Controller
{
    /**
     * Peta tab status halaman ke enum QuoteRequestStatus.
     */
    protected const STATUS_FILTERS = [
        'baru' => [QuoteRequestStatus::Diajukan],
        'ditinjau' => [QuoteRequestStatus::DiusulkanPenyedia],
        'disetujui' => [QuoteRequestStatus::DisetujuiAdmin],
        'ditolak' => [QuoteRequestStatus::DitolakAdmin],
    ];

    /**
     * Daftar permintaan penawaran atas item katalog milik vendor login.
     */
    public function index(Request $request): View
    {
        $statuses = self::STATUS_FILTERS[$request->query('status')] ?? null;

        return view('vendor.permintaan-penawaran', [
            'requests' => $this->ownedRequests($request)
                ->when($statuses, fn (Builder $q) => $q->whereIn('status', array_column($statuses, 'value')))
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Detail satu permintaan penawaran atas item milik vendor login.
     */
    public function show(Request $request, QuoteRequest $quoteRequest): View
    {
        abort_unless(
            $this->ownedRequests($request)->whereKey($quoteRequest->id)->exists(),
            404,
        );

        return view('vendor.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load(['subject', 'event', 'proposals']),
        ]);
    }

    /**
     * Query dasar permintaan penawaran dengan subject item katalog milik
     * profil vendor akun login (profil bisa belum ada -> collection kosong).
     */
    protected function ownedRequests(Request $request): Builder
    {
        $itemIds = $request->user()->vendorProfile?->catalogItems()->pluck('id') ?? collect();

        return QuoteRequest::query()
            ->where('subject_type', 'catalog_item')
            ->whereIn('subject_id', $itemIds)
            ->with(['subject', 'event', 'proposals']);
    }
}
