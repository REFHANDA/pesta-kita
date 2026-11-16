<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only daftar acara Klien: halaman `acara` menampilkan agenda
 * yang masih berjalan, `riwayat` menampilkan acara yang sudah berakhir
 * (selesai/dinilai/dibatalkan). Keduanya membaca tabel `events` milik akun
 * login — sama seperti sumber data beranda. Halaman status (draft/diajukan/
 * terkonfirmasi/dibatalkan) adalah daftar milik sendiri yang difilter per
 * status, dan `acara-detail` menampilkan satu acara nyata miliknya sendiri.
 *
 * Pesanan, tagihan, rundown, dan penilaian belum punya backend (BE-4–BE-7),
 * karena itu halaman detail hanya merender kolom nyata `events` plus
 * permintaan penawaran terkait — tanpa tombol aksi yang mati.
 */
class EventController extends Controller
{
    /**
     * Status yang masih berjalan (agenda mendatang).
     */
    protected const UPCOMING = [
        EventStatus::Draft,
        EventStatus::Diajukan,
        EventStatus::MenungguPembayaran,
        EventStatus::Terkonfirmasi,
    ];

    /**
     * Status yang sudah berakhir (riwayat).
     */
    protected const FINISHED = [
        EventStatus::Selesai,
        EventStatus::Dinilai,
        EventStatus::Dibatalkan,
    ];

    /**
     * Daftar acara mendatang milik Klien login.
     */
    public function index(Request $request): View
    {
        return view('klien.acara', [
            'events' => $this->eventsFor($request, self::UPCOMING)->get(),
        ]);
    }

    /**
     * Daftar acara selesai/dibatalkan milik Klien login.
     */
    public function riwayat(Request $request): View
    {
        return view('klien.riwayat', [
            'events' => $this->eventsFor($request, self::FINISHED)
                ->reorder('event_date', 'desc')
                ->get(),
        ]);
    }

    /**
     * Detail satu acara nyata milik Klien login, beserta permintaan
     * penawaran terkait (sumber: `events` + `quote_requests`).
     */
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);

        return view('klien.acara-detail', [
            'event' => $event->load(['quoteRequests.subject', 'quoteRequests.proposals']),
        ]);
    }

    /**
     * Daftar acara berstatus draft milik Klien login.
     */
    public function draft(Request $request): View
    {
        return $this->statusList($request, 'klien.acara-draft', [EventStatus::Draft]);
    }

    /**
     * Daftar acara berstatus diajukan milik Klien login.
     */
    public function diajukan(Request $request): View
    {
        return $this->statusList($request, 'klien.acara-diajukan', [EventStatus::Diajukan]);
    }

    /**
     * Daftar acara berstatus terkonfirmasi milik Klien login.
     */
    public function terkonfirmasi(Request $request): View
    {
        return $this->statusList($request, 'klien.acara-terkonfirmasi', [EventStatus::Terkonfirmasi]);
    }

    /**
     * Daftar acara berstatus dibatalkan milik Klien login.
     */
    public function dibatalkan(Request $request): View
    {
        return $this->statusList($request, 'klien.acara-dibatalkan', [EventStatus::Dibatalkan]);
    }

    /**
     * Render daftar acara dengan satu set status pada view terpilih.
     *
     * @param  array<int, EventStatus>  $statuses
     */
    protected function statusList(Request $request, string $view, array $statuses): View
    {
        return view($view, [
            'events' => $this->eventsFor($request, $statuses)->get(),
        ]);
    }

    /**
     * Query dasar acara milik akun login dengan status terpilih.
     *
     * @param  array<int, EventStatus>  $statuses
     */
    protected function eventsFor(Request $request, array $statuses): HasMany
    {
        return $request->user()->events()
            ->whereIn('status', array_column($statuses, 'value'))
            ->orderBy('event_date');
    }
}
