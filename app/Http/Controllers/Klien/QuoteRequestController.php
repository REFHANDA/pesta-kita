<?php

namespace App\Http\Controllers\Klien;

use App\Enums\QuoteRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only permintaan penawaran Klien: halaman daftar dan detail
 * membaca tabel `quote_requests` + `quote_proposals` milik akun login.
 * Pengajuan baru, keputusan Admin, dan lanjutan ke pesanan belum punya
 * backend (BE-4/BE-5), karena itu halaman hanya menampilkan data nyata
 * tanpa form atau tombol aksi.
 */
class QuoteRequestController extends Controller
{
    /**
     * Peta tab status halaman ke enum QuoteRequestStatus.
     */
    protected const STATUS_FILTERS = [
        'menunggu' => [QuoteRequestStatus::Diajukan],
        'ditinjau' => [QuoteRequestStatus::DiusulkanPenyedia],
        'disetujui' => [QuoteRequestStatus::DisetujuiAdmin],
        'ditolak' => [QuoteRequestStatus::DitolakAdmin],
    ];

    /**
     * Daftar permintaan penawaran milik Klien login.
     */
    public function index(Request $request): View
    {
        $statuses = self::STATUS_FILTERS[$request->query('status')] ?? null;

        return view('klien.permintaan-penawaran', [
            'requests' => $request->user()->quoteRequests()
                ->when($statuses, fn ($q) => $q->whereIn('status', array_column($statuses, 'value')))
                ->with(['subject', 'event', 'proposals'])
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Detail satu permintaan penawaran milik Klien login.
     */
    public function show(Request $request, QuoteRequest $quoteRequest): View
    {
        abort_unless($quoteRequest->client_id === $request->user()->id, 404);

        return view('klien.permintaan-penawaran-detail', [
            'quoteRequest' => $quoteRequest->load(['subject', 'event', 'proposals']),
        ]);
    }
}
