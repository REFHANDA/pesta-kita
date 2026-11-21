<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceChangeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecidePriceChangeRequest;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\PriceChangeRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * BE-3b: antrean dan keputusan pengajuan harga oleh Admin. Persetujuan
 * satu-satunya jalur yang mengubah harga aktif (catalog_price /
 * rental_price); penolakan wajib beralasan dan harga lama tetap berlaku
 * (aturan terkunci A11). Setiap keputusan menulis audit log.
 */
class PriceChangeController extends Controller
{
    /**
     * Antrean pengajuan harga (filter ?status=; default menunggu).
     */
    public function index(Request $request): View
    {
        $status = $this->statusFilter($request);

        $query = PriceChangeRequest::with(['subject', 'reviewer'])->latest();
        if ($status !== null) {
            $query->where('status', $status);
        }

        return view('admin.pengajuan-harga', [
            'requests' => $query->get(),
            'statusFilter' => $status,
            'waitingCount' => PriceChangeRequest::where('status', PriceChangeStatus::Menunggu)->count(),
        ]);
    }

    /**
     * Detail satu pengajuan beserta konteks item/venue dan penyedia.
     */
    public function show(PriceChangeRequest $priceChange): View
    {
        return view('admin.pengajuan-harga-detail', [
            'change' => $priceChange->load(['subject', 'reviewer']),
            'profile' => $this->providerProfile($priceChange->subject),
        ]);
    }

    /**
     * Satu pintu keputusan berdasarkan aksi rute. DecidePriceChangeRequest
     * membuat alasan wajib hanya saat menolak (dibaca dari rute action).
     */
    public function decide(DecidePriceChangeRequest $request, PriceChangeRequest $priceChange, string $action): RedirectResponse
    {
        return match ($action) {
            'approve' => $this->approve($request, $priceChange),
            'reject' => $this->reject($request, $priceChange),
            default => abort(404),
        };
    }

    /**
     * Setujui pengajuan: harga aktif subjek berubah, pesanan lama tidak
     * tersentuh karena harga final dikunci di snapshot pesanan (BE-5/BE-6).
     */
    public function approve(DecidePriceChangeRequest $request, PriceChangeRequest $priceChange): RedirectResponse
    {
        if ($priceChange->status !== PriceChangeStatus::Menunggu) {
            return redirect()->back()->withErrors([
                'price_change' => 'Hanya pengajuan berstatus menunggu yang dapat diputuskan.',
            ]);
        }

        DB::transaction(function () use ($request, $priceChange): void {
            $change = PriceChangeRequest::whereKey($priceChange->getKey())->lockForUpdate()->first();

            if ($change === null || $change->status !== PriceChangeStatus::Menunggu) {
                return;
            }

            $this->applyNewPrice($change);

            $change->update([
                'status' => PriceChangeStatus::Disetujui,
                'decision_note' => $request->validated('reason'),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'price_change_approved',
                'subject_type' => $change->getMorphClass(),
                'subject_id' => $change->id,
                'from_status' => PriceChangeStatus::Menunggu->value,
                'to_status' => PriceChangeStatus::Disetujui->value,
                'reason' => $request->validated('reason'),
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.pengajuan-harga')
            ->with('status', 'Pengajuan harga disetujui. Harga aktif katalog berubah; pesanan lama tetap memakai harga yang sudah dikunci.');
    }

    /**
     * Tolak pengajuan dengan alasan; harga lama tetap berlaku (A11).
     */
    public function reject(DecidePriceChangeRequest $request, PriceChangeRequest $priceChange): RedirectResponse
    {
        if ($priceChange->status !== PriceChangeStatus::Menunggu) {
            return redirect()->back()->withErrors([
                'price_change' => 'Hanya pengajuan berstatus menunggu yang dapat diputuskan.',
            ]);
        }

        DB::transaction(function () use ($request, $priceChange): void {
            $change = PriceChangeRequest::whereKey($priceChange->getKey())->lockForUpdate()->first();

            if ($change === null || $change->status !== PriceChangeStatus::Menunggu) {
                return;
            }

            $change->update([
                'status' => PriceChangeStatus::Ditolak,
                'decision_note' => $request->validated('reason'),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'price_change_rejected',
                'subject_type' => $change->getMorphClass(),
                'subject_id' => $change->id,
                'from_status' => PriceChangeStatus::Menunggu->value,
                'to_status' => PriceChangeStatus::Ditolak->value,
                'reason' => $request->validated('reason'),
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.pengajuan-harga')
            ->with('status', 'Pengajuan harga ditolak. Harga lama tetap berlaku dan alasan tersimpan di log.');
    }

    /**
     * Filter status dari query; default menunggu seperti antrean kerja Admin.
     */
    protected function statusFilter(Request $request): ?string
    {
        $status = (string) $request->query('status', 'menunggu');

        if ($status === 'semua') {
            return null;
        }

        return in_array($status, array_column(PriceChangeStatus::cases(), 'value'), true)
            ? $status
            : PriceChangeStatus::Menunggu->value;
    }

    /**
     * Terapkan harga baru ke subjek (satu-satunya jalur tulis harga aktif).
     */
    protected function applyNewPrice(PriceChangeRequest $change): void
    {
        $subject = $change->subject;

        if ($subject instanceof CatalogItem) {
            $subject->forceFill(['catalog_price' => $change->new_price])->save();
        } elseif ($subject instanceof Venue) {
            $subject->forceFill(['rental_price' => $change->new_price])->save();
        }
    }

    /**
     * Profil usaha penyedia pemilik subjek pengajuan (untuk halaman detail).
     */
    protected function providerProfile(mixed $subject): mixed
    {
        if ($subject instanceof CatalogItem) {
            return $subject->vendorProfile?->load('user');
        }

        if ($subject instanceof Venue) {
            return $subject->venueProfile?->load('user');
        }

        return null;
    }
}
