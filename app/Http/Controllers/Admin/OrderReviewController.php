<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Setting;
use App\Models\VenueRoom;
use App\Notifications\OrderItemReviewedNotification;
use App\Notifications\OrderObjectionDecidedNotification;
use App\Services\AvailabilityService;
use App\Services\InvoiceIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controller Admin untuk review pesanan, keputusan keberatan,
 * dan penerbitan/buat ulang tagihan sesuai rancangan EMS.
 */
class OrderReviewController extends Controller
{
    /**
     * Daftar pesanan dari acara yang sudah diajukan oleh klien.
     */
    public function index(Request $request): View
    {
        $statusParam = $request->query('status');
        $search = $request->query('q');
        $type = $request->query('j');

        $query = OrderItem::query()
            ->with(['event.client', 'provider', 'subject', 'reviewer'])
            ->whereHas('event', function ($q) {
                $q->where('status', '!=', 'draft');
            })
            ->latest();

        $tab = $request->query('tab', $statusParam ? null : 'review');

        match ($tab) {
            'review' => $query->where('status', OrderItemStatus::MenungguReview),
            'berjalan' => $query->whereIn('status', [OrderItemStatus::Disetujui, OrderItemStatus::Terkonfirmasi]),
            'keberatan' => $query->whereHas('objections'),
            'riwayat' => $query->whereIn('status', [OrderItemStatus::Selesai, OrderItemStatus::Ditolak, OrderItemStatus::Dialihkan, OrderItemStatus::Dibatalkan]),
            default => null,
        };

        if ($statusParam && $statusParam !== 'semua') {
            $enumStatus = match ($statusParam) {
                'menunggu-review', 'menunggu_review' => OrderItemStatus::MenungguReview,
                'disetujui' => OrderItemStatus::Disetujui,
                'terkonfirmasi' => OrderItemStatus::Terkonfirmasi,
                'selesai' => OrderItemStatus::Selesai,
                'ditolak' => OrderItemStatus::Ditolak,
                'dialihkan' => OrderItemStatus::Dialihkan,
                'dibatalkan' => OrderItemStatus::Dibatalkan,
                default => null,
            };

            if ($enumStatus) {
                $query->where('status', $enumStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('event', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                })->orWhereHas('event.client', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($type) {
            match ($type) {
                'venue' => $query->where('subject_type', 'venue_room'),
                'layanan' => $query->where('subject_type', 'catalog_item')->whereHasMorph(
                    'subject',
                    [CatalogItem::class],
                    fn ($q) => $q->where('kind', 'layanan')
                ),
                'peralatan' => $query->where('subject_type', 'catalog_item')->whereHasMorph(
                    'subject',
                    [CatalogItem::class],
                    fn ($q) => $q->where('kind', 'peralatan')
                ),
                default => null,
            };
        }

        $orderItems = $query->paginate(15)->withQueryString();

        $baseCountQuery = OrderItem::query()->whereHas('event', function ($q) {
            $q->where('status', '!=', 'draft');
        });

        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'menunggu_review' => (clone $baseCountQuery)->where('status', OrderItemStatus::MenungguReview)->count(),
            'disetujui' => (clone $baseCountQuery)->where('status', OrderItemStatus::Disetujui)->count(),
            'terkonfirmasi' => (clone $baseCountQuery)->where('status', OrderItemStatus::Terkonfirmasi)->count(),
            'selesai' => (clone $baseCountQuery)->where('status', OrderItemStatus::Selesai)->count(),
            'ditolak' => (clone $baseCountQuery)->where('status', OrderItemStatus::Ditolak)->count(),
            'dialihkan' => (clone $baseCountQuery)->where('status', OrderItemStatus::Dialihkan)->count(),
            'dibatalkan' => (clone $baseCountQuery)->where('status', OrderItemStatus::Dibatalkan)->count(),
            'keberatan' => (clone $baseCountQuery)->whereHas('objections')->count(),
        ];
        $counts['berjalan'] = $counts['disetujui'] + $counts['terkonfirmasi'];
        $counts['riwayat'] = $counts['selesai'] + $counts['ditolak'] + $counts['dialihkan'] + $counts['dibatalkan'];

        return view('admin.pesanan', [
            'orderItems' => $orderItems,
            'counts' => $counts,
            'activeStatus' => $statusParam ?? 'semua',
            'activeTab' => $tab,
            'search' => $search,
            'type' => $type,
        ]);
    }

    /**
     * Detail satu pesanan untuk ditinjau oleh Admin.
     */
    public function show(OrderItem $orderItem): View
    {
        $orderItem->load([
            'event.client',
            'event.orderItems.subject',
            'provider',
            'subject',
            'reviewer',
            'replacedBy.subject',
            'objections.providerUser',
            'objections.evidence',
        ]);

        $auditLogs = AuditLog::with('actor')
            ->where('subject_type', $orderItem->getMorphClass())
            ->where('subject_id', $orderItem->id)
            ->latest()
            ->get();

        $activeInvoice = $orderItem->event->activeInvoice;

        $replacementCatalogItems = CatalogItem::query()
            ->where('status', 'aktif')
            ->where('id', '!=', $orderItem->subject_id)
            ->limit(20)
            ->get();

        $replacementVenueRooms = VenueRoom::query()
            ->with('venue')
            ->limit(10)
            ->get();

        return view('admin.pesanan-detail', [
            'orderItem' => $orderItem,
            'event' => $orderItem->event,
            'auditLogs' => $auditLogs,
            'activeInvoice' => $activeInvoice,
            'replacementCatalogItems' => $replacementCatalogItems,
            'replacementVenueRooms' => $replacementVenueRooms,
        ]);
    }

    /**
     * Setujui item pesanan.
     * Kunci provider_price, hitung komisi platform 10%, set approved_at dan objection_deadline.
     */
    public function approve(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $orderItem) {
            /** @var OrderItem $lockedItem */
            $lockedItem = OrderItem::query()->where('id', $orderItem->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->status !== OrderItemStatus::MenungguReview) {
                return redirect()
                    ->route('admin.pesanan.show', $lockedItem)
                    ->with('error', 'Hanya pesanan berstatus menunggu review yang dapat disetujui.');
            }

            $providerPrice = (int) ($lockedItem->provider_price ?? $lockedItem->catalog_price_snapshot);
            $commissionPercent = (int) Setting::get('commission_percent', 10);
            $lineTotal = (int) ($providerPrice * max(1, (int) ($lockedItem->quantity ?? 1)));
            // Aritmetika bilangan bulat: pembulatan setengah ke atas tanpa float.
            $commission = intdiv($lineTotal * $commissionPercent + 50, 100);

            $objectionWindowHours = (int) Setting::get('objection_window_hours', 24);
            $now = now();
            $objectionDeadline = $now->copy()->addHours($objectionWindowHours);

            $oldStatus = $lockedItem->status->value;

            $lockedItem->update([
                'provider_price' => $providerPrice,
                'commission' => $commission,
                'status' => OrderItemStatus::Disetujui,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $now,
                'approved_at' => $now,
                'objection_deadline' => $objectionDeadline,
                'admin_note' => $request->input('admin_note') ?? $lockedItem->admin_note,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'order_item_approved',
                'subject_type' => $lockedItem->getMorphClass(),
                'subject_id' => $lockedItem->id,
                'from_status' => $oldStatus,
                'to_status' => OrderItemStatus::Disetujui->value,
                'reason' => $request->input('admin_note') ?? 'Pesanan disetujui Admin. Masa keberatan 24 jam dimulai.',
                'ip' => $request->ip(),
            ]);

            $lockedItem->event->client?->notify(new OrderItemReviewedNotification($lockedItem, 'approved'));
            $lockedItem->provider?->user?->notify(new OrderItemReviewedNotification($lockedItem, 'approved'));

            return redirect()
                ->route('admin.pesanan.show', $lockedItem)
                ->with('success', 'Pesanan berhasil disetujui. Harga penyedia dikunci dan masa keberatan dimulai.');
        });
    }

    /**
     * Tolak item pesanan dengan alasan wajib.
     * Status menjadi ditolak dan hold_key dilepas.
     */
    public function reject(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        return DB::transaction(function () use ($request, $validated, $orderItem) {
            /** @var OrderItem $lockedItem */
            $lockedItem = OrderItem::query()->where('id', $orderItem->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->status !== OrderItemStatus::MenungguReview) {
                return redirect()
                    ->route('admin.pesanan.show', $lockedItem)
                    ->with('error', 'Hanya pesanan berstatus menunggu review yang dapat ditolak.');
            }

            $oldStatus = $lockedItem->status->value;

            $lockedItem->update([
                'status' => OrderItemStatus::Ditolak,
                'rejection_reason' => $validated['rejection_reason'],
                'admin_note' => $validated['rejection_reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'hold_key' => null,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'order_item_rejected',
                'subject_type' => $lockedItem->getMorphClass(),
                'subject_id' => $lockedItem->id,
                'from_status' => $oldStatus,
                'to_status' => OrderItemStatus::Ditolak->value,
                'reason' => $validated['rejection_reason'],
                'ip' => $request->ip(),
            ]);

            $lockedItem->event->client?->notify(new OrderItemReviewedNotification($lockedItem, 'rejected', $validated['rejection_reason']));
            $lockedItem->provider?->user?->notify(new OrderItemReviewedNotification($lockedItem, 'rejected', $validated['rejection_reason']));

            return redirect()
                ->route('admin.pesanan.show', $lockedItem)
                ->with('success', 'Pesanan berhasil ditolak dan slot telah dilepas.');
        });
    }

    /**
     * Alihkan item pesanan ke penyedia lain.
     * Item lama ditandai dialihkan, item baru dibuat dengan rujukan ke item baru.
     */
    public function reassign(Request $request, OrderItem $orderItem, AvailabilityService $availabilityService): RedirectResponse
    {
        $validated = $request->validate([
            'replacement_subject_type' => 'required|in:catalog_item,venue_room',
            'replacement_subject_id' => 'required|integer',
            'reason' => 'required|string|min:3|max:1000',
        ]);

        return DB::transaction(function () use ($request, $validated, $orderItem, $availabilityService) {
            /** @var OrderItem $lockedItem */
            $lockedItem = OrderItem::query()->where('id', $orderItem->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedItem->status, [OrderItemStatus::MenungguReview, OrderItemStatus::Disetujui])) {
                return redirect()
                    ->route('admin.pesanan.show', $lockedItem)
                    ->with('error', 'Pesanan saat ini tidak dapat dialihkan.');
            }

            $event = $lockedItem->event;
            $usageDate = $lockedItem->usage_date?->toDateString() ?? $event->event_date?->toDateString();

            if ($validated['replacement_subject_type'] === 'catalog_item') {
                $replacement = CatalogItem::with('vendorProfile')->findOrFail($validated['replacement_subject_id']);
                $providerType = 'vendor_profile';
                $providerId = $replacement->vendor_profile_id;
                $price = (int) $replacement->catalog_price;
                $holdKey = null;
            } else {
                $replacement = VenueRoom::with('venue.venueProfile')->findOrFail($validated['replacement_subject_id']);
                $venue = $replacement->venue;
                $providerType = 'venue_profile';
                $providerId = $venue?->venue_profile_id;
                $price = (int) ($venue?->rental_price ?? 0);
                $holdKey = $venue ? $availabilityService->makeVenueHoldKey((int) $venue->id, (string) $usageDate) : null;
            }

            $slaHours = (int) Setting::get('admin_review_sla_hours', 24);

            $newItem = OrderItem::create([
                'event_id' => $lockedItem->event_id,
                'usage_date' => $lockedItem->usage_date,
                'quantity' => $lockedItem->quantity,
                'provider_type' => $providerType,
                'provider_id' => $providerId,
                'subject_type' => $validated['replacement_subject_type'],
                'subject_id' => $replacement->id,
                'catalog_price_snapshot' => $price,
                'provider_price' => $price,
                'commission' => 0,
                'status' => OrderItemStatus::MenungguReview,
                'review_due_at' => now()->addHours($slaHours),
                'hold_key' => $holdKey,
            ]);

            $oldStatus = $lockedItem->status->value;

            $lockedItem->update([
                'status' => OrderItemStatus::Dialihkan,
                'replaced_by_order_item_id' => $newItem->id,
                'admin_note' => $validated['reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'hold_key' => null,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'order_item_reassigned',
                'subject_type' => $lockedItem->getMorphClass(),
                'subject_id' => $lockedItem->id,
                'from_status' => $oldStatus,
                'to_status' => OrderItemStatus::Dialihkan->value,
                'reason' => "Dialihkan ke {$replacement->name}. Alasan: {$validated['reason']}",
                'ip' => $request->ip(),
            ]);

            $lockedItem->event->client?->notify(new OrderItemReviewedNotification($lockedItem, 'reassigned', $validated['reason']));
            $newItem->provider?->user?->notify(new OrderItemReviewedNotification($newItem, 'new'));

            return redirect()
                ->route('admin.pesanan.show', $lockedItem)
                ->with('success', "Pesanan berhasil dialihkan. Pesanan baru #{$newItem->id} telah dibuat.");
        });
    }

    /**
     * Putuskan keberatan penyedia (diterima atau ditolak).
     */
    public function decideObjection(Request $request, OrderObjection $objection): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:terima,tolak',
            'decision_reason' => 'required|string|min:3|max:1000',
            'action_type' => 'nullable|in:tetap,tolak',
        ]);

        return DB::transaction(function () use ($request, $validated, $objection) {
            /** @var OrderObjection $lockedObjection */
            $lockedObjection = OrderObjection::query()->where('id', $objection->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedObjection->status, [OrderObjectionStatus::Diajukan, OrderObjectionStatus::Ditinjau])) {
                return redirect()
                    ->back()
                    ->with('error', 'Keberatan ini sudah pernah diputuskan sebelumnya.');
            }

            $newStatus = $validated['decision'] === 'terima'
                ? OrderObjectionStatus::Diterima
                : OrderObjectionStatus::Ditolak;

            $lockedObjection->update([
                'status' => $newStatus,
                'decision_reason' => $validated['decision_reason'],
                'admin_decision' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            // Jika keberatan diterima dan admin memilih untuk membatalkan/menolak pesanan
            if ($newStatus === OrderObjectionStatus::Diterima && ($validated['action_type'] ?? null) === 'tolak') {
                $orderItem = $lockedObjection->orderItem()->lockForUpdate()->first();
                if ($orderItem && in_array($orderItem->status, [OrderItemStatus::Disetujui, OrderItemStatus::MenungguReview])) {
                    $orderItem->update([
                        'status' => OrderItemStatus::Ditolak,
                        'rejection_reason' => "Keberatan penyedia diterima: {$validated['decision_reason']}",
                        'hold_key' => null,
                    ]);
                }
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'order_objection_decided',
                'subject_type' => $lockedObjection->getMorphClass(),
                'subject_id' => $lockedObjection->id,
                'from_status' => OrderObjectionStatus::Diajukan->value,
                'to_status' => $newStatus->value,
                'reason' => "Keputusan: {$validated['decision']}. Catatan: {$validated['decision_reason']}",
                'ip' => $request->ip(),
            ]);

            $lockedObjection->providerUser?->notify(new OrderObjectionDecidedNotification($lockedObjection));
            $lockedObjection->orderItem?->event?->client?->notify(new OrderObjectionDecidedNotification($lockedObjection));

            return redirect()
                ->back()
                ->with('success', "Keberatan berhasil diputuskan sebagai '{$newStatus->value}'.");
        });
    }

    /**
     * Buat ulang tagihan (versi baru) hanya bila tagihan lama belum dibayar.
     */
    public function reissueInvoice(Request $request, Invoice $invoice, InvoiceIssuer $issuer): RedirectResponse
    {
        try {
            $newInvoice = $issuer->reissue($invoice, $request->user());

            return redirect()
                ->back()
                ->with('success', "Tagihan baru {$newInvoice->invoice_number} (versi {$newInvoice->version}) berhasil dibuat ulang.");
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
