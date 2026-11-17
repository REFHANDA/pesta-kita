<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideOrderItemRequest;
use App\Http\Requests\Admin\DecideOrderObjectionRequest;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderReviewController extends Controller
{
    /**
     * List event order items for Admin review.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', OrderItemStatus::MenungguAdmin->value);
        $validStatuses = array_column(OrderItemStatus::cases(), 'value');
        if (! is_string($status) || ($status !== 'semua' && ! in_array($status, $validStatuses, true))) {
            $status = OrderItemStatus::MenungguAdmin->value;
        }

        $orderItems = OrderItem::query()
            ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Diajukan->value))
            ->with([
                'event.client',
                'provider' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    VendorProfile::class => ['user'],
                    VenueProfile::class => ['user'],
                ]),
                'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    CatalogItem::class => [],
                    VenueRoom::class => ['venue'],
                ]),
            ])
            ->when($status !== 'semua', fn ($query) => $query->where('status', $status))
            ->orderBy('status')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pesanan', [
            'orderItems' => $orderItems,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Show one order item and its owning event to Admin.
     */
    public function show(OrderItem $orderItem): View
    {
        return view('admin.pesanan-detail', [
            'orderItem' => $orderItem->load([
                'event.client',
                'provider' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    VendorProfile::class => ['user'],
                    VenueProfile::class => ['user'],
                ]),
                'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    CatalogItem::class => [],
                    VenueRoom::class => ['venue'],
                ]),
                'objections.providerUser',
            ]),
        ]);
    }

    /**
     * Approve or reject a single order item.
     */
    public function decide(DecideOrderItemRequest $request, OrderItem $orderItem, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject'], true), 404);
        $data = $request->validated();

        DB::transaction(function () use ($request, $orderItem, $action, $data): void {
            $eventId = OrderItem::query()->whereKey($orderItem->id)->value('event_id');
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            $lockedItem = OrderItem::query()->whereKey($orderItem->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->event_id !== $event->id
                || $event->status !== EventStatus::Diajukan
                || $lockedItem->status !== OrderItemStatus::MenungguAdmin) {
                throw ValidationException::withMessages([
                    'order_item' => 'Hanya item pada acara yang diajukan dan belum diputuskan dapat diproses.',
                ]);
            }

            $fromStatus = $lockedItem->status;
            $reason = $data['reason'] ?? null;
            $approved = $action === 'approve';
            $toStatus = $approved ? OrderItemStatus::MasaKeberatan : OrderItemStatus::Ditolak;

            $lockedItem->update([
                'status' => $toStatus,
                'final_price' => $approved
                    ? ($lockedItem->proposed_price_snapshot ?? $lockedItem->catalog_price_snapshot)
                    : null,
                'rejection_reason' => $approved ? null : $reason,
                'objection_deadline' => $approved ? now()->addHours(24) : null,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => $approved ? 'order_item_approved' : 'order_item_rejected',
                'subject_type' => $lockedItem->getMorphClass(),
                'subject_id' => $lockedItem->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'reason' => $reason,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.pesanan.detail', $orderItem)
            ->with('status', $action === 'approve'
                ? 'Item disetujui. Harga dikunci dan masa keberatan 24 jam dimulai.'
                : 'Item ditolak; alasan tersimpan di log aktivitas.');
    }

    /**
     * List provider objections for Admin.
     */
    public function objections(Request $request): View
    {
        $status = $request->query('status', 'diajukan');
        if (! is_string($status) || ! in_array($status, ['diajukan', 'diputuskan', 'semua'], true)) {
            $status = 'diajukan';
        }

        $objections = OrderObjection::query()
            ->with(['orderItem.event', 'orderItem.subject', 'providerUser'])
            ->when($status !== 'semua', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.keberatan', [
            'objections' => $objections,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Show one provider objection to Admin.
     */
    public function showObjection(OrderObjection $orderObjection): View
    {
        return view('admin.keberatan-detail', [
            'objection' => $orderObjection->load([
                'providerUser',
                'orderItem.event.client',
                'orderItem.subject',
            ]),
        ]);
    }

    /**
     * Decide a provider objection; acceptance removes the item from the event.
     */
    public function decideObjection(
        DecideOrderObjectionRequest $request,
        OrderObjection $orderObjection,
        string $action,
    ): RedirectResponse {
        abort_unless(in_array($action, ['approve', 'reject'], true), 404);
        $reason = $request->validated('reason');

        DB::transaction(function () use ($request, $orderObjection, $action, $reason): void {
            $lockedObjection = OrderObjection::query()
                ->whereKey($orderObjection->id)
                ->lockForUpdate()
                ->firstOrFail();
            $item = OrderItem::query()
                ->whereKey($lockedObjection->order_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedObjection->status !== 'diajukan' || $item->status !== OrderItemStatus::MasaKeberatan) {
                throw ValidationException::withMessages([
                    'objection' => 'Hanya keberatan terbuka atas item dalam masa keberatan yang dapat diputuskan.',
                ]);
            }
            if ($item->objection_deadline === null) {
                throw ValidationException::withMessages([
                    'objection' => 'Batas masa keberatan item ini tidak tersedia.',
                ]);
            }

            $accepted = $action === 'approve';
            $lockedObjection->update([
                'status' => 'diputuskan',
                'admin_decision' => $accepted ? 'diterima' : 'ditolak',
                'decided_at' => now(),
            ]);

            $fromStatus = $item->status;
            $toStatus = $accepted
                ? OrderItemStatus::Ditolak
                : ($item->objection_deadline->isPast() ? OrderItemStatus::Mengikat : OrderItemStatus::MasaKeberatan);

            $item->update([
                'status' => $toStatus,
                'final_price' => $accepted ? null : $item->final_price,
                'rejection_reason' => $accepted ? $reason : $item->rejection_reason,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => $accepted ? 'order_objection_accepted' : 'order_objection_rejected',
                'subject_type' => $lockedObjection->getMorphClass(),
                'subject_id' => $lockedObjection->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'reason' => $reason,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.keberatan.detail', $orderObjection)
            ->with('status', $action === 'approve'
                ? 'Keberatan diterima; item dikeluarkan dari pesanan.'
                : 'Keberatan ditolak; item tetap berlaku setelah masa keberatan berakhir.');
    }
}
