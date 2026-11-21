<?php

namespace App\Http\Controllers\Klien;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rundown\SaveRundownItemRequest;
use App\Models\Event;
use App\Models\RundownItem;
use App\Services\RundownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Rundown di detail acara Klien (Tahap C2). Tidak ada menu baru: seluruh
 * aksi hidup di dalam halaman detail acara. Aturan peran/status ada di
 * RundownService dan RundownItemPolicy.
 */
class RundownController extends Controller
{
    /**
     * Daftar rundown klien diarahkan ke bagian rundown pada detail acara.
     */
    public function index(Request $request, Event $event): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        return redirect()->to(route('klien.acara.detail', $event).'#rundown');
    }

    /**
     * Tambah item rundown pada acara milik klien login.
     */
    public function store(SaveRundownItemRequest $request, Event $event, RundownService $rundown): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);
        Gate::authorize('create', [RundownItem::class, $event]);

        $rundown->add($event, $request->user(), $request->validated(), $request->ip());

        return redirect()
            ->to(route('klien.acara.detail', $event).'#rundown')
            ->with('success', 'Item rundown berhasil ditambahkan.');
    }

    /**
     * Ubah item rundown pada acara milik klien login.
     */
    public function update(SaveRundownItemRequest $request, Event $event, RundownItem $rundownItem, RundownService $rundown): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);
        abort_unless((int) $rundownItem->event_id === (int) $event->id, 404);
        Gate::authorize('update', $rundownItem);

        $rundown->update($rundownItem, $request->user(), $request->validated(), $request->ip());

        return redirect()
            ->to(route('klien.acara.detail', $event).'#rundown')
            ->with('success', 'Item rundown berhasil diperbarui.');
    }

    /**
     * Hapus item rundown pada acara milik klien login.
     */
    public function destroy(Request $request, Event $event, RundownItem $rundownItem, RundownService $rundown): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);
        abort_unless((int) $rundownItem->event_id === (int) $event->id, 404);
        Gate::authorize('delete', $rundownItem);

        $rundown->delete($rundownItem, $request->user(), $request->ip());

        return redirect()
            ->to(route('klien.acara.detail', $event).'#rundown')
            ->with('success', 'Item rundown berhasil dihapus.');
    }
}
