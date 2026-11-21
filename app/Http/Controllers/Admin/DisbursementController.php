<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Services\DisbursementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

/**
 * Halaman Admin "Pencairan" (Tahap B7): antrean belum/sudah cair dan pencatatan transfer.
 */
class DisbursementController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'cair' ? 'cair' : 'belum';
        $type = DisbursementType::tryFrom((string) $request->query('jenis'));
        $provider = (string) $request->query('penyedia');

        $query = Disbursement::query()
            ->with(['orderItem.subject', 'provider.bankAccounts', 'proof'])
            ->where('status', $tab === 'cair' ? DisbursementStatus::Cair : DisbursementStatus::BelumCair)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when(preg_match('/^(vendor_profile|venue_profile):(\d+)$/', $provider, $m) === 1, fn ($q) => $q->where('provider_type', $m[1])->where('provider_id', (int) $m[2]));

        $providers = Disbursement::query()->with('provider')->select(['provider_type', 'provider_id'])->distinct()->get()
            ->mapWithKeys(fn (Disbursement $d): array => [$d->provider_type.':'.$d->provider_id => ($d->provider?->business_name ?? 'Penyedia').' ('.($d->provider_type === 'venue_profile' ? 'venue' : 'vendor').')']);

        return view('admin.pencairan', [
            'tab' => $tab,
            'type' => $type,
            'provider' => $provider,
            'providers' => $providers,
            'totalUnpaid' => (int) Disbursement::where('status', DisbursementStatus::BelumCair)->sum('amount'),
            'totalPaid' => (int) Disbursement::where('status', DisbursementStatus::Cair)->sum('amount'),
            'disbursements' => $query->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function record(Request $request, Disbursement $disbursement, DisbursementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'paid_at.required' => 'Tanggal transfer wajib diisi.',
            'paid_at.date' => 'Tanggal transfer tidak valid.',
            'paid_at.before_or_equal' => 'Tanggal transfer tidak boleh melewati hari ini.',
            'proof.required' => 'Unggah bukti transfer pencairan.',
            'proof.mimes' => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        try {
            $service->recordTransfer($disbursement, $request->user(), Carbon::parse($validated['paid_at']), $request->file('proof'), $request->ip());
        } catch (RuntimeException $e) {
            if ($e->getCode() === 409) {
                abort(409, $e->getMessage());
            }

            return redirect()->route('admin.pencairan')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pencairan', ['tab' => 'cair'])->with('success', 'Transfer pencairan dicatat.');
    }
}
