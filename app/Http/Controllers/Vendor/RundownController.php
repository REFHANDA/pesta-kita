<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rundown\SaveRundownItemRequest;
use App\Models\Event;
use App\Models\RundownItem;
use App\Services\RundownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rundown di portal Vendor (Tahap C3). Vendor melihat acara yang pesanannya
 * terkonfirmasi/selesai dan hanya dapat mengubah item buatannya sendiri yang
 * tertaut ke pesanannya. Tidak ada nama/kontak klien di seluruh halaman.
 */
class RundownController extends Controller
{
    /**
     * Daftar acara yang memiliki pesanan vendor login.
     */
    public function index(Request $request, RundownService $rundown): View
    {
        $events = $rundown->eventsForProvider($request->user())
            ->loadCount('rundownItems');

        return view('vendor.rundown', [
            'events' => $events,
        ]);
    }

    /**
     * Rundown lengkap satu acara.
     */
    public function show(Request $request, Event $event, RundownService $rundown): View
    {
        abort_unless($rundown->canView($request->user(), $event), 403, 'Anda tidak memiliki akses ke rundown acara ini.');

        return view('vendor.rundown-show', [
            'event' => $event,
            'rundownItems' => $rundown->items($event),
            'canWrite' => $rundown->canWrite($request->user(), $event),
            'linkableOrderItems' => $rundown->linkableOrderItems($event, $request->user()),
            'lockedMessage' => $rundown->isLocked($event) ? 'Rundown dikunci karena acara sudah selesai.' : null,
        ]);
    }

    /**
     * Tambah item rundown milik vendor pada pesanannya.
     */
    public function store(SaveRundownItemRequest $request, Event $event, RundownService $rundown): RedirectResponse
    {
        abort_unless($rundown->canView($request->user(), $event), 403, 'Anda tidak memiliki akses ke rundown acara ini.');
        Gate::authorize('create', [RundownItem::class, $event]);

        $rundown->add($event, $request->user(), $request->validated(), $request->ip());

        return redirect()
            ->route('vendor.rundown.show', $event)
            ->with('success', 'Item rundown berhasil ditambahkan.');
    }

    /**
     * Ubah item rundown milik vendor.
     */
    public function update(SaveRundownItemRequest $request, Event $event, RundownItem $rundownItem, RundownService $rundown): RedirectResponse
    {
        abort_unless($rundown->canView($request->user(), $event), 403, 'Anda tidak memiliki akses ke rundown acara ini.');
        abort_unless((int) $rundownItem->event_id === (int) $event->id, 404);
        Gate::authorize('update', $rundownItem);

        $rundown->update($rundownItem, $request->user(), $request->validated(), $request->ip());

        return redirect()
            ->route('vendor.rundown.show', $event)
            ->with('success', 'Item rundown berhasil diperbarui.');
    }

    /**
     * Hapus item rundown milik vendor.
     */
    public function destroy(Request $request, Event $event, RundownItem $rundownItem, RundownService $rundown): RedirectResponse
    {
        abort_unless($rundown->canView($request->user(), $event), 403, 'Anda tidak memiliki akses ke rundown acara ini.');
        abort_unless((int) $rundownItem->event_id === (int) $event->id, 404);
        Gate::authorize('delete', $rundownItem);

        $rundown->delete($rundownItem, $request->user(), $request->ip());

        return redirect()
            ->route('vendor.rundown.show', $event)
            ->with('success', 'Item rundown berhasil dihapus.');
    }
}
