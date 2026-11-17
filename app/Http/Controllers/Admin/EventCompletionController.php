<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventCompletionController extends Controller
{
    /**
     * List confirmed and completed events for Admin monitoring.
     */
    public function index(): View
    {
        return view('admin.acara', [
            'events' => Event::query()
                ->with(['client', 'latestInvoice'])
                ->whereIn('status', [
                    EventStatus::Diajukan->value,
                    EventStatus::MenungguPembayaran->value,
                    EventStatus::Terkonfirmasi->value,
                    EventStatus::Selesai->value,
                    EventStatus::Dinilai->value,
                ])
                ->withCount('orderItems')
                ->orderBy('event_date')
                ->paginate(20),
        ]);
    }

    /**
     * Show event completion details to Admin.
     */
    public function show(Event $event): View
    {
        return view('admin.acara-detail', [
            'event' => $event->load([
                'client',
                'orderItems.subject',
                'orderItems.objections',
                'latestInvoice.latestPayment',
                'rundownItems',
            ]),
        ]);
    }

    /**
     * Close an event after its date and paid invoice.
     */
    public function complete(Request $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($request, $event): void {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($lockedEvent->status !== EventStatus::Terkonfirmasi) {
                throw ValidationException::withMessages([
                    'event' => 'Hanya acara terkonfirmasi yang dapat ditandai selesai.',
                ]);
            }

            if (! $lockedEvent->invoices()->where('status', 'lunas')->exists()) {
                throw ValidationException::withMessages([
                    'event' => 'Acara belum memiliki tagihan lunas.',
                ]);
            }

            if ($lockedEvent->event_date->isToday() || $lockedEvent->event_date->isFuture()) {
                throw ValidationException::withMessages([
                    'event' => 'Acara hanya dapat ditutup setelah tanggal acara berakhir.',
                ]);
            }

            $lockedEvent->update([
                'status' => EventStatus::Selesai,
                'closed_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'event_marked_complete',
                'subject_type' => $lockedEvent->getMorphClass(),
                'subject_id' => $lockedEvent->id,
                'from_status' => EventStatus::Terkonfirmasi->value,
                'to_status' => EventStatus::Selesai->value,
                'reason' => null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.acara.detail', $event)
            ->with('status', 'Acara ditandai selesai. Pencairan dan ulasan kini dapat diproses.');
    }
}
