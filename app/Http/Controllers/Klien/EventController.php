<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Enums\PricingMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreEventRequest;
use App\Http\Requests\Client\UpdateEventRequest;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alur acara Klien: daftar agenda/riwayat, pembuatan dan penyuntingan draft,
 * pengajuan, serta detail acara yang dimiliki akun login.
 *
 * Alur pesanan, tagihan, rundown, refund-intake, dan ulasan ditangani
 * controller tahap BE-5–BE-7; halaman detail juga menampilkan quote terkait.
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
     * Show the event draft form.
     */
    public function create(): View
    {
        return view('klien.acara-baru');
    }

    /**
     * Save a new event as a draft.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $request->user()->events()->create($request->validated());

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Konsep acara tersimpan sebagai draft.');
    }

    /**
     * Show the edit form for an owned draft.
     */
    public function edit(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($event->status === EventStatus::Draft, 404);

        return view('klien.acara-form', ['event' => $event]);
    }

    /**
     * Update an owned draft without accepting client-controlled state.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_unless($event->status === EventStatus::Draft, 404);

        $event->update($request->validated());

        return redirect()->route('klien.acara.detail', $event)
            ->with('status', 'Draft acara diperbarui.');
    }

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
            'event' => $event->load([
                'orderItems.subject',
                'orderItems.objections',
                'orderItems.review',
                'quoteRequests.subject',
                'quoteRequests.proposals',
                'quoteRequests.decision',
                'latestInvoice.latestPayment',
                'refundRequest',
            ]),
            'catalogItems' => CatalogItem::publikUntukKlien()
                ->where('pricing_mode', PricingMode::Tetap)
                ->with('vendorProfile')
                ->orderBy('name')
                ->get(),
            'venueRooms' => VenueRoom::query()
                ->whereHas('venue', fn ($query) => $query->publikUntukKlien())
                ->with('venue')
                ->orderBy('name')
                ->get(),
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
