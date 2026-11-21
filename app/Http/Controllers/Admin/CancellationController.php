<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Http\Controllers\Controller;
use App\Models\Cancellation;
use App\Services\CancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Halaman Admin "Pembatalan & refund" (Tahap B4): antrean, detail, dan
 * transisi status yang sah. Semua aksi lewat CancellationService.
 */
class CancellationController extends Controller
{
    /**
     * @var array<string, array{label: string, statuses: array<int, CancellationStatus>}>
     */
    public const TABS = [
        'tinjau' => ['label' => 'Perlu ditinjau', 'statuses' => [CancellationStatus::Diajukan, CancellationStatus::Ditinjau]],
        'proses' => ['label' => 'Disetujui & diproses', 'statuses' => [CancellationStatus::Disetujui, CancellationStatus::RefundDiproses]],
        'selesai' => ['label' => 'Selesai', 'statuses' => [CancellationStatus::RefundSelesai]],
        'ditolak' => ['label' => 'Ditolak', 'statuses' => [CancellationStatus::Ditolak]],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'tinjau';

        $counts = [];
        foreach (self::TABS as $key => $definition) {
            $counts[$key] = Cancellation::query()->whereIn('status', array_column($definition['statuses'], 'value'))->count();
        }

        return view('admin.pembatalan', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'counts' => $counts,
            'cancellations' => Cancellation::query()
                ->with(['event.client'])
                ->whereIn('status', array_column(self::TABS[$tab]['statuses'], 'value'))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Cancellation $cancellation): View
    {
        return view('admin.pembatalan-detail', [
            'cancellation' => $cancellation->load(['event.client', 'invoice', 'reviewer', 'refundProof']),
        ]);
    }

    public function review(Request $request, Cancellation $cancellation, CancellationService $service): RedirectResponse
    {
        return $this->run(fn () => $service->review($cancellation, $request->user(), $request->ip()), $cancellation, 'Pengajuan ditandai sedang ditinjau.');
    }

    public function reject(Request $request, Cancellation $cancellation, CancellationService $service): RedirectResponse
    {
        $validated = $request->validate(
            ['decision_reason' => ['required', 'string', 'min:5', 'max:1000']],
            [
                'decision_reason.required' => 'Alasan penolakan wajib diisi.',
                'decision_reason.min' => 'Alasan penolakan minimal 5 karakter.',
                'decision_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
            ],
        );

        return $this->run(fn () => $service->reject($cancellation, $request->user(), $validated['decision_reason'], $request->ip()), $cancellation, 'Pengajuan pembatalan ditolak.');
    }

    public function approve(Request $request, Cancellation $cancellation, CancellationService $service): RedirectResponse
    {
        $tierPercent = (int) ($cancellation->policy_snapshot['tier_percent'] ?? $cancellation->refund_percent);

        $validated = $request->validate([
            'cause' => ['required', Rule::enum(CancellationCause::class)],
            'refund_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'decision_reason' => [
                'nullable', 'string', 'max:1000',
                Rule::requiredIf(fn (): bool => $request->input('cause') !== CancellationCause::Klien->value
                    || ($request->filled('refund_percent') && (int) $request->input('refund_percent') !== $tierPercent)),
            ],
        ], [
            'cause.required' => 'Pilih penyebab pembatalan.',
            'cause.enum' => 'Penyebab pembatalan tidak valid.',
            'refund_percent.integer' => 'Persen refund harus bilangan bulat.',
            'refund_percent.min' => 'Persen refund minimal 0.',
            'refund_percent.max' => 'Persen refund maksimal 100.',
            'decision_reason.required' => 'Alasan wajib diisi untuk penyebab vendor/force majeure atau perubahan persen dari tabel.',
        ]);

        $cause = CancellationCause::from($validated['cause']);
        $percent = isset($validated['refund_percent']) ? (int) $validated['refund_percent'] : null;

        return $this->run(fn () => $service->approve($cancellation, $request->user(), $cause, $percent, $validated['decision_reason'] ?? null, $request->ip()), $cancellation, 'Pengajuan pembatalan disetujui. Nominal refund final telah dihitung ulang.');
    }

    public function startRefund(Request $request, Cancellation $cancellation, CancellationService $service): RedirectResponse
    {
        return $this->run(fn () => $service->startRefund($cancellation, $request->user(), $request->ip()), $cancellation, 'Refund ditandai sedang diproses. Transfer sesuai nominal dan rekening tujuan.');
    }

    public function completeRefund(Request $request, Cancellation $cancellation, CancellationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'refund_paid_at' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'refund_paid_at.required' => 'Tanggal transfer refund wajib diisi.',
            'refund_paid_at.date' => 'Tanggal transfer tidak valid.',
            'refund_paid_at.before_or_equal' => 'Tanggal transfer tidak boleh melewati hari ini.',
            'proof.required' => 'Unggah bukti transfer refund.',
            'proof.mimes' => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        return $this->run(
            fn () => $service->completeRefund($cancellation, $request->user(), Carbon::parse($validated['refund_paid_at']), $request->file('proof'), $request->ip()),
            $cancellation,
            'Refund dicatat selesai. Acara dibatalkan dan kompensasi penyedia masuk antrean pencairan.',
        );
    }

    /**
     * Jalankan aksi; status tidak sah → 409/422 tanpa mengubah data.
     */
    protected function run(callable $action, Cancellation $cancellation, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            abort(in_array($e->getCode(), [409, 422], true) ? $e->getCode() : 409, $e->getMessage());
        }

        return redirect()->route('admin.pembatalan.show', $cancellation)->with('success', $success);
    }
}
