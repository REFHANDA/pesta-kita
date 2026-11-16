<?php

namespace App\Http\Controllers\Venue;

use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only permintaan penawaran yang masuk ke ruangan venue milik
 * Pemilik Venue login (`quote_requests` dengan subject venue_room milik
 * profil akun). Alur tulis pengusulan harga menyusul pada BE-4; halaman
 * ini hanya menampilkan data yang sudah tersimpan, tanpa form aksi.
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
     * Daftar permintaan penawaran atas ruangan venue milik login.
     */
    public function index(Request $request): View
    {
        $statuses = self::STATUS_FILTERS[$request->query('status')] ?? null;

        return view('venue.permintaan-penawaran', [
            'requests' => $this->ownedRequests($request)
                ->when($statuses, fn (Builder $q) => $q->whereIn('status', array_column($statuses, 'value')))
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Detail satu permintaan penawaran atas ruangan milik login.
     */
    public function show(Request $request, QuoteRequest $quoteRequest): View
    {
        abort_unless(
            $this->ownedRequests($request)->whereKey($quoteRequest->id)->exists(),
            404,
        );

        return view('venue.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load(['subject', 'event', 'proposals']),
        ]);
    }

    /**
     * Query dasar permintaan penawaran dengan subject ruangan dari venue
     * milik profil akun login (profil bisa belum ada -> collection kosong).
     */
    protected function ownedRequests(Request $request): Builder
    {
        $venueIds = $request->user()->venueProfile?->venues()->pluck('id') ?? collect();
        $roomIds = VenueRoom::query()->whereIn('venue_id', $venueIds)->pluck('id');

        return QuoteRequest::query()
            ->where('subject_type', 'venue_room')
            ->whereIn('subject_id', $roomIds)
            ->with(['subject', 'event', 'proposals']);
    }
}
