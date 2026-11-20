<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\VenueRoom;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Controller penyusunan acara, budget, dan pemesanan layanan/venue untuk portal Klien.
 * Sesuai dokumen perancangan EMS §1.4 dan Fase 3.
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
        EventStatus::Dievaluasi,
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
     * Form pembuatan konsep acara draft baru.
     */
    public function create(Request $request): View
    {
        return view('klien.acara-baru');
    }

    /**
     * Simpan konsep acara draft baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'theme' => ['nullable', 'string', 'max:255'],
            'budget' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $event = $request->user()->events()->create([
            ...$validated,
            'status' => EventStatus::Draft,
        ]);

        return redirect()
            ->route('klien.acara.detail', $event)
            ->with('success', 'Konsep acara draft berhasil disimpan.');
    }

    /**
     * Form ubah konsep acara draft.
     */
    public function edit(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_if($event->status !== EventStatus::Draft, 403, 'Hanya acara berstatus draft yang dapat diubah.');

        return view('klien.acara-form', [
            'event' => $event,
        ]);
    }

    /**
     * Perbarui konsep acara draft.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_if($event->status !== EventStatus::Draft, 403, 'Hanya acara berstatus draft yang dapat diubah.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'theme' => ['nullable', 'string', 'max:255'],
            'budget' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $event->update($validated);

        // Jika tanggal acara berubah, sinkronkan tanggal pakai pada item pesanan draft
        $event->orderItems()
            ->where('status', OrderItemStatus::Draft)
            ->update(['usage_date' => $event->event_date]);

        return redirect()
            ->route('klien.acara.detail', $event)
            ->with('success', 'Konsep acara berhasil diperbarui.');
    }

    /**
     * Hapus acara draft beserta item pesanan draft di dalamnya.
     */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_if($event->status !== EventStatus::Draft, 403, 'Hanya acara berstatus draft yang dapat dihapus.');

        $event->orderItems()->delete();
        $event->delete();

        return redirect()
            ->route('klien.acara')
            ->with('success', 'Acara draft berhasil dihapus.');
    }

    /**
     * Tambah layanan/peralatan/venue ke acara berstatus draft (tanpa menahan tanggal sampai diajukan).
     */
    public function addItem(Request $request, Event $event, AvailabilityService $availabilityService): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_if($event->status !== EventStatus::Draft, 403, 'Pesanan hanya dapat ditambahkan pada acara draft.');

        $validated = $request->validate([
            'subject_type' => ['required', 'in:catalog_item,venue_room'],
            'subject_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = max(1, (int) ($validated['quantity'] ?? 1));

        if ($validated['subject_type'] === 'catalog_item') {
            /** @var CatalogItem $item */
            $item = CatalogItem::publikUntukKlien()->with('vendorProfile')->findOrFail($validated['subject_id']);

            if ($event->event_date && $availabilityService->isManualUnavailable($item, $event->event_date->toDateString())) {
                return back()->with('error', "Penyedia menandai item '{$item->name}' tidak tersedia pada tanggal acara Anda.");
            }

            $event->orderItems()->create([
                'usage_date' => $event->event_date,
                'quantity' => $quantity,
                'provider_type' => 'vendor_profile',
                'provider_id' => $item->vendor_profile_id,
                'subject_type' => 'catalog_item',
                'subject_id' => $item->id,
                'catalog_price_snapshot' => $item->catalog_price,
                'provider_price' => $item->catalog_price,
                'commission' => 0,
                'status' => OrderItemStatus::Draft,
                'hold_key' => null,
            ]);
        } else {
            /** @var VenueRoom $room */
            $room = VenueRoom::with('venue.venueProfile')->findOrFail($validated['subject_id']);
            $venue = $room->venue;

            abort_unless($venue && $venue->verification_status === VerificationStatus::Terverifikasi, 404);

            if ($event->event_date && $availabilityService->isManualUnavailable($room, $event->event_date->toDateString())) {
                return back()->with('error', "Venue menandai ruangan '{$room->name}' tidak tersedia pada tanggal acara Anda.");
            }

            $event->orderItems()->create([
                'usage_date' => $event->event_date,
                'quantity' => 1,
                'provider_type' => 'venue_profile',
                'provider_id' => $venue->venue_profile_id,
                'subject_type' => 'venue_room',
                'subject_id' => $room->id,
                'catalog_price_snapshot' => $venue->rental_price,
                'provider_price' => $venue->rental_price,
                'commission' => 0,
                'status' => OrderItemStatus::Draft,
                'hold_key' => null,
            ]);
        }

        return redirect()
            ->route('klien.acara.detail', $event)
            ->with('success', 'Layanan/venue berhasil ditambahkan ke acara.');
    }

    /**
     * Hapus item pesanan draft dari acara draft.
     */
    public function removeItem(Request $request, Event $event, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);
        abort_if($event->status !== EventStatus::Draft, 403, 'Hanya item pada acara draft yang dapat dihapus.');
        abort_unless($orderItem->event_id === $event->id, 404);
        abort_if($orderItem->status !== OrderItemStatus::Draft, 403, 'Hanya pesanan berstatus draft yang dapat dihapus.');

        $orderItem->delete();

        return redirect()
            ->route('klien.acara.detail', $event)
            ->with('success', 'Item pesanan berhasil dihapus dari acara.');
    }

    /**
     * Ajukan acara (status draft -> diajukan) dengan cek ketersediaan, lock DB, dan pembuatan hold key venue.
     */
    public function submit(Request $request, Event $event, AvailabilityService $availabilityService): RedirectResponse
    {
        abort_unless($event->client_id === $request->user()->id, 404);

        try {
            $availabilityService->validateAndSubmitEvent($event);

            return redirect()
                ->route('klien.acara.detail', $event)
                ->with('success', 'Acara berhasil diajukan dan sedang ditinjau oleh Admin Pesta Kita.');
        } catch (RuntimeException $e) {
            return redirect()
                ->route('klien.acara.detail', $event)
                ->with('error', $e->getMessage());
        }
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
     * Detail satu acara nyata milik Klien login, beserta pesanan
     * layanan/venue terkait.
     */
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->client_id === $request->user()->id, 404);

        return view('klien.acara-detail', [
            'event' => $event->load(['orderItems.subject', 'orderItems.provider']),
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
