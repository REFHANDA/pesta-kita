<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\VenueRoom;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Issue an invoice after every item decision and objection window is final.
     */
    public function issue(Event $event): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($event): Invoice {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedEvent->status, [EventStatus::Diajukan, EventStatus::MenungguPembayaran], true)) {
                throw ValidationException::withMessages([
                    'event' => 'Tagihan hanya dapat diterbitkan untuk acara yang sudah diajukan.',
                ]);
            }

            $orderItems = $lockedEvent->orderItems()
                ->with([
                    'objections',
                    'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        CatalogItem::class => [],
                        VenueRoom::class => ['venue'],
                    ]),
                ])
                ->lockForUpdate()
                ->get();

            if ($orderItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'event' => 'Acara belum memiliki item pesanan.',
                ]);
            }

            foreach ($orderItems as $orderItem) {
                if ($orderItem->objections->contains('status', 'diajukan')) {
                    throw ValidationException::withMessages([
                        'event' => 'Selesaikan semua keberatan penyedia sebelum menerbitkan tagihan.',
                    ]);
                }

                if ($orderItem->status === OrderItemStatus::MasaKeberatan) {
                    if ($orderItem->objection_deadline === null || $orderItem->objection_deadline->isFuture()) {
                        throw ValidationException::withMessages([
                            'event' => 'Masa keberatan 24 jam belum berakhir untuk seluruh item.',
                        ]);
                    }

                    $orderItem->update(['status' => OrderItemStatus::Mengikat]);
                }
            }

            $unresolvedItems = $orderItems->contains(
                fn (OrderItem $orderItem): bool => ! in_array(
                    $orderItem->fresh()->status,
                    [OrderItemStatus::Mengikat, OrderItemStatus::Ditolak],
                    true,
                ),
            );
            if ($unresolvedItems) {
                throw ValidationException::withMessages([
                    'event' => 'Seluruh item harus disetujui atau ditolak sebelum tagihan diterbitkan.',
                ]);
            }

            $billableItems = $orderItems
                ->filter(fn (OrderItem $orderItem): bool => $orderItem->fresh()->status === OrderItemStatus::Mengikat);
            if ($billableItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'event' => 'Tidak ada item yang disetujui untuk ditagihkan.',
                ]);
            }

            foreach ($billableItems as $orderItem) {
                $this->ensureItemAvailable($lockedEvent, $orderItem);
            }

            $latestInvoice = $lockedEvent->invoices()->orderByDesc('version')->lockForUpdate()->first();
            if ($latestInvoice?->status === 'lunas') {
                throw ValidationException::withMessages([
                    'event' => 'Tagihan yang sudah lunas tidak dapat diterbitkan ulang.',
                ]);
            }

            if ($latestInvoice?->status === 'terbit') {
                if ($latestInvoice->payments()->where('status', 'menunggu_verifikasi')->exists()) {
                    throw ValidationException::withMessages([
                        'event' => 'Bukti pembayaran sedang menunggu verifikasi Admin.',
                    ]);
                }

                if ($latestInvoice->due_at->isFuture()) {
                    throw ValidationException::withMessages([
                        'event' => 'Tagihan aktif masih berlaku dan belum dapat diganti.',
                    ]);
                }

                $latestInvoice->update([
                    'status' => 'kedaluwarsa',
                    'active_code' => null,
                ]);
            }

            $now = now('Asia/Jakarta');
            $dueAt = $this->dueAt($lockedEvent, $now);
            $code = $this->availableCode();
            $version = ($latestInvoice?->version ?? 0) + 1;
            $providerSubtotal = 0;
            $commissionTotal = 0;
            $cleaningTotal = 0;
            $lineSnapshots = [];

            foreach ($billableItems as $orderItem) {
                $subject = $orderItem->subject;
                if (! $subject instanceof CatalogItem && ! $subject instanceof VenueRoom) {
                    throw ValidationException::withMessages([
                        'event' => 'Item pesanan tidak lagi tersedia untuk diterbitkan.',
                    ]);
                }

                $providerAmount = $orderItem->final_price;
                if ($providerAmount === null) {
                    throw ValidationException::withMessages([
                        'event' => 'Harga final Admin belum tersedia pada semua item.',
                    ]);
                }

                $commission = intdiv($providerAmount, 10);
                $cleaningFee = $subject instanceof VenueRoom ? ($subject->venue?->cleaning_fee ?? 0) : 0;
                $description = $subject instanceof VenueRoom
                    ? trim(($subject->venue?->name ? $subject->venue->name.' — ' : '').$subject->name)
                    : $subject->name;

                $lineSnapshots[] = [
                    'order_item_id' => $orderItem->id,
                    'description_snapshot' => $description,
                    'provider_amount' => $providerAmount,
                    'commission_amount' => $commission,
                    'cleaning_fee_amount' => $cleaningFee,
                    'total_amount' => $providerAmount + $commission + $cleaningFee,
                ];
                $providerSubtotal += $providerAmount;
                $commissionTotal += $commission;
                $cleaningTotal += $cleaningFee;
            }

            $invoice = $lockedEvent->invoices()->create([
                'version' => $version,
                'unique_code' => $code,
                'active_code' => $code,
                'provider_subtotal' => $providerSubtotal,
                'commission_total' => $commissionTotal,
                'cleaning_total' => $cleaningTotal,
                'total_amount' => $providerSubtotal + $commissionTotal + $cleaningTotal + $code,
                'status' => 'terbit',
                'issued_at' => $now,
                'due_at' => $dueAt,
            ]);

            $invoice->lines()->createMany($lineSnapshots);
            $lockedEvent->update(['status' => EventStatus::MenungguPembayaran]);

            AuditLog::create([
                'actor_id' => auth()->id(),
                'action' => 'invoice_issued',
                'subject_type' => $invoice->getMorphClass(),
                'subject_id' => $invoice->id,
                'from_status' => $latestInvoice?->status,
                'to_status' => $invoice->status,
                'reason' => 'Invoice versi '.$version.' diterbitkan setelah seluruh review item selesai.',
                'ip' => request()->ip(),
            ]);

            return $invoice;
        });

        return redirect()->route('admin.pembayaran.detail', $invoice)
            ->with('status', 'Tagihan diterbitkan dengan kode unik dan rincian harga snapshot.');
    }

    /**
     * Calculate the payment deadline using the approved WIB policy.
     */
    private function dueAt(Event $event, CarbonInterface $issuedAt): CarbonImmutable
    {
        $issuedAt = CarbonImmutable::instance($issuedAt)->setTimezone('Asia/Jakarta');
        $eventEnd = CarbonImmutable::parse($event->event_date->toDateString(), 'Asia/Jakarta')->endOfDay();
        if ($issuedAt->greaterThanOrEqualTo($eventEnd)) {
            throw ValidationException::withMessages([
                'event' => 'Tagihan tidak dapat diterbitkan setelah tanggal acara berakhir.',
            ]);
        }

        $sevenDaysCutoff = CarbonImmutable::parse($event->event_date->toDateString(), 'Asia/Jakarta')
            ->subDays(7)
            ->setTime(23, 59);

        return $issuedAt->lessThanOrEqualTo($sevenDaysCutoff)
            ? $sevenDaysCutoff
            : $issuedAt->addHours(24)->min($eventEnd);
    }

    /**
     * Pick a three-digit code not currently assigned to an open invoice.
     */
    private function availableCode(): int
    {
        $codes = range(100, 999);
        shuffle($codes);

        foreach ($codes as $code) {
            if (! Invoice::query()->where('active_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'event' => 'Tidak ada kode unik tagihan yang tersedia saat ini.',
        ]);
    }

    /**
     * Recheck item availability while holding its subject row lock.
     */
    private function ensureItemAvailable(Event $event, OrderItem $orderItem): void
    {
        $subject = $orderItem->subject;
        $subjectModel = $subject instanceof CatalogItem ? CatalogItem::class : VenueRoom::class;
        $lockedSubject = $subjectModel::query()->whereKey($orderItem->subject_id)->lockForUpdate()->first();
        if ($lockedSubject === null) {
            throw ValidationException::withMessages([
                'event' => 'Item pesanan tidak lagi tersedia.',
            ]);
        }

        $unavailable = $lockedSubject->availabilityDays()
            ->whereDate('date', $event->event_date->toDateString())
            ->where('status', 'tidak_tersedia')
            ->exists();
        $alreadyBooked = OrderItem::query()
            ->where('subject_type', $orderItem->subject_type)
            ->where('subject_id', $orderItem->subject_id)
            ->where('event_id', '!=', $event->id)
            ->whereHas('event', fn ($query) => $query
                ->whereDate('event_date', $event->event_date->toDateString())
                ->where('status', EventStatus::Terkonfirmasi->value))
            ->exists();

        if ($unavailable || $alreadyBooked) {
            throw ValidationException::withMessages([
                'event' => 'Item sudah tidak tersedia pada tanggal acara.',
            ]);
        }
    }
}
