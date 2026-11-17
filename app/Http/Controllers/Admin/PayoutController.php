<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePayoutRequest;
use App\Models\AuditLog;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\ProviderBankAccount;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    /**
     * List payable provider rights and completed transfers.
     */
    public function index(): View
    {
        return view('admin.pencairan', [
            'eligibleItems' => OrderItem::query()
                ->where('status', OrderItemStatus::Mengikat->value)
                ->whereDoesntHave('payout')
                ->whereHas('event', fn ($query) => $query
                    ->whereIn('status', [EventStatus::Selesai->value, EventStatus::Dinilai->value])
                    ->whereHas('invoices', fn ($invoices) => $invoices->where('status', 'lunas')))
                ->with(['event', 'subject', 'provider'])
                ->latest('id')
                ->paginate(20, ['*'], 'eligible_page'),
            'payouts' => Payout::query()
                ->with(['orderItem.event', 'orderItem.subject', 'orderItem.provider'])
                ->latest('id')
                ->paginate(20, ['*'], 'paid_page'),
        ]);
    }

    /**
     * Show provider account details and payout form to Admin.
     */
    public function show(OrderItem $orderItem): View
    {
        abort_unless($this->isPayable($orderItem), 404);
        $orderItem->load([
            'event',
            'subject',
            'payout',
            'provider' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                VendorProfile::class => ['bankAccounts'],
                VenueProfile::class => ['bankAccounts'],
            ]),
        ]);

        $bankAccounts = $orderItem->provider?->bankAccounts()
            ->where('verification_status', VerificationStatus::Terverifikasi->value)
            ->get() ?? collect();

        return view('admin.pencairan-detail', [
            'orderItem' => $orderItem,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Record a one-time provider payment from the Admin's transfer.
     */
    public function store(
        StorePayoutRequest $request,
        OrderItem $orderItem,
    ): RedirectResponse {
        abort_unless($this->isPayable($orderItem), 404);
        $data = $request->validated();
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payout-proofs', 'private');
            if ($proofPath === false) {
                throw ValidationException::withMessages([
                    'proof' => 'Bukti pencairan gagal disimpan. Coba unggah ulang.',
                ]);
            }
        }

        $saved = false;
        try {
            DB::transaction(function () use ($request, $orderItem, $data, $proofPath): void {
                $lockedItem = OrderItem::query()->whereKey($orderItem->id)->lockForUpdate()->firstOrFail();
                if (! $this->isPayable($lockedItem) || $lockedItem->payout()->exists()) {
                    throw ValidationException::withMessages([
                        'order_item' => 'Hak penyedia sudah dibayar atau belum memenuhi syarat pencairan.',
                    ]);
                }

                $profile = $lockedItem->provider;
                abort_if($profile === null, 404);
                $account = ProviderBankAccount::query()
                    ->whereKey($data['provider_bank_account_id'])
                    ->where('owner_type', $lockedItem->provider_type)
                    ->where('owner_id', $lockedItem->provider_id)
                    ->where('verification_status', VerificationStatus::Terverifikasi->value)
                    ->lockForUpdate()
                    ->first();
                if ($account === null) {
                    throw ValidationException::withMessages([
                        'provider_bank_account_id' => 'Pilih rekening terverifikasi milik penyedia pesanan ini.',
                    ]);
                }

                $payout = $lockedItem->payout()->create([
                    'provider_bank_account_id' => $account->id,
                    'bank_name_snapshot' => $account->bank_name,
                    'account_number_snapshot' => $account->account_number,
                    'account_name_snapshot' => $account->account_name,
                    'amount' => $lockedItem->final_price,
                    'transferred_at' => $data['transferred_at'],
                    'proof_disk' => 'private',
                    'proof_path' => $proofPath,
                    'recorded_by' => $request->user()->id,
                    'notes' => $data['notes'] ?? null,
                ]);

                AuditLog::create([
                    'actor_id' => $request->user()->id,
                    'action' => 'provider_payout_recorded',
                    'subject_type' => $payout->getMorphClass(),
                    'subject_id' => $payout->id,
                    'from_status' => 'menunggu_admin',
                    'to_status' => 'berhasil',
                    'reason' => $data['notes'] ?? null,
                    'ip' => $request->ip(),
                ]);
            });

            $saved = true;
        } finally {
            if (! $saved && is_string($proofPath)) {
                Storage::disk('private')->delete($proofPath);
            }
        }

        return redirect()->route('admin.pencairan.detail', $orderItem)
            ->with('status', 'Pencairan hak penyedia dicatat satu kali.');
    }

    /**
     * Stream an optional payout proof to Admin only.
     */
    public function proof(Payout $payout): StreamedResponse
    {
        abort_unless(is_string($payout->proof_path)
            && Storage::disk($payout->proof_disk)->exists($payout->proof_path), 404);

        return Storage::disk($payout->proof_disk)->download($payout->proof_path);
    }

    /**
     * Check event completion, paid invoice, and a locked provider item.
     */
    private function isPayable(OrderItem $orderItem): bool
    {
        return $orderItem->status === OrderItemStatus::Mengikat
            && $orderItem->final_price !== null
            && $orderItem->event()
                ->whereIn('status', [EventStatus::Selesai->value, EventStatus::Dinilai->value])
                ->whereHas('invoices', fn ($query) => $query->where('status', 'lunas'))
                ->exists();
    }
}
