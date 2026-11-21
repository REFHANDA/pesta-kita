<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EvaluationService;
use App\Services\EventCompletionService;
use App\Services\RundownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Pemantauan acara oleh Admin (baca-saja). Draft milik klien tidak tampil.
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        $status = EventStatus::tryFrom((string) $request->query('status'));
        $tab = $request->query('tab') === 'evaluasi' ? 'evaluasi' : 'semua';

        $events = Event::query()
            ->with('client')
            ->withCount('orderItems')
            ->where('status', '!=', EventStatus::Draft)
            ->when($tab === 'evaluasi', fn ($query) => $query->whereIn('status', [EventStatus::Selesai, EventStatus::Dievaluasi]))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('event_date')
            ->paginate(15)
            ->withQueryString();

        // G2: hitungan per status dalam satu query untuk tab subnav.
        $counts = Event::query()
            ->where('status', '!=', EventStatus::Draft)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.acara', [
            'events' => $events,
            'activeStatus' => $status,
            'activeTab' => $tab,
            'counts' => $counts,
            'totalSemua' => (int) $counts->sum(),
        ]);
    }

    public function show(Event $event, EventCompletionService $completion, RundownService $rundown, EvaluationService $evaluations): View
    {
        abort_if($event->status === EventStatus::Draft, 404);

        $evaluationSubjects = $evaluations->subjectsFor($event);

        return view('admin.acara-detail', [
            'event' => $event->load(['client', 'orderItems.subject', 'orderItems.provider', 'invoices']),
            'completionBlocker' => $event->status === EventStatus::Terkonfirmasi ? $completion->blocker($event) : null,
            // Tahap C3: tab rundown hanya lihat untuk Admin.
            'rundownItems' => $rundown->items($event),
            // Tahap C5: hasil evaluasi lengkap untuk Admin.
            'evaluation' => $event->evaluation()->with('items')->first(),
            'evaluationNames' => collect($evaluationSubjects)
                ->mapWithKeys(fn (array $subject): array => [$subject['type'].':'.($subject['id'] ?? 0) => $subject['name']])
                ->all(),
        ]);
    }

    /**
     * Tandai acara selesai dan buat pencairan hak pesanan.
     */
    public function complete(Request $request, Event $event, EventCompletionService $completion): RedirectResponse
    {
        try {
            $completion->complete($event, $request->user(), $request->ip());
        } catch (RuntimeException $e) {
            if ($e->getCode() === 409) {
                abort(409, $e->getMessage());
            }

            return redirect()->route('admin.acara.detail', $event)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.acara.detail', $event)->with('success', 'Acara ditandai selesai. Pencairan hak pesanan penyedia telah dibuat.');
    }
}
