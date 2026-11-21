<?php

namespace App\Http\Controllers\Klien;

use App\Http\Controllers\Controller;
use App\Http\Requests\Klien\StoreEvaluationRequest;
use App\Models\Event;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Evaluasi acara oleh Klien (Tahap C4). Form dan ringkasan hidup di dalam
 * detail acara; URL lama /klien/evaluasi sudah dihapus.
 */
class EvaluationController extends Controller
{
    /**
     * Daftar evaluasi diarahkan ke bagian evaluasi pada detail acara.
     */
    public function index(Request $request, Event $event): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        return redirect()->to(route('klien.acara.detail', $event).'#evaluasi');
    }

    /**
     * Kirim evaluasi final acara yang sudah selesai.
     */
    public function store(
        StoreEvaluationRequest $request,
        Event $event,
        EvaluationService $evaluations,
    ): RedirectResponse {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        try {
            $evaluations->submit(
                $event,
                $request->user(),
                $request->validated('ratings'),
                $request->validated('system_feedback'),
                $request->ip(),
            );
        } catch (RuntimeException $exception) {
            if ($exception->getCode() === 409) {
                abort(409, $exception->getMessage());
            }

            return redirect()
                ->to(route('klien.acara.detail', $event).'#evaluasi')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route('klien.acara.detail', $event).'#evaluasi')
            ->with('success', 'Terima kasih. Evaluasi acara berhasil dikirim dan tidak dapat diubah.');
    }
}
