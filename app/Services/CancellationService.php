<?php

namespace App\Services;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\CancellationStatusChangedNotification;
use App\Notifications\CancellationSubmittedNotification;
use App\Notifications\CompensationRecordedNotification;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Alur pembatalan setelah lunas: pengajuan klien (B3) dan keputusan Admin (B4/B5).
 * Semua perubahan status berjalan dalam transaksi dengan penguncian baris.
 */
class CancellationService
{
    public function __construct(
        protected RefundCalculator $calculator,
        protected AvailabilityService $availability,
    ) {}

    /**
     * Tagihan lunas acara (dasar perhitungan refund), bila ada.
     */
    public function paidInvoice(Event $event): ?Invoice
    {
        return Invoice::query()
            ->where('event_id', $event->id)
            ->where('status', InvoiceStatus::Lunas)
            ->latest('id')
            ->first();
    }

    public function activeCancellation(Event $event): ?Cancellation
    {
        return Cancellation::query()
            ->where('event_id', $event->id)
            ->whereIn('status', array_column(CancellationStatus::active(), 'value'))
            ->latest('id')
            ->first();
    }

    /**
     * Alasan acara belum/tidak bisa diajukan pembatalan; null bila boleh.
     */
    public function submissionBlocker(Event $event, ?DateTimeInterface $today = null): ?string
    {
        $today = CarbonImmutable::instance($today ?? now())->setTimezone(RefundCalculator::TIMEZONE)->startOfDay();

        if ($event->status !== EventStatus::Terkonfirmasi) {
            return 'Pembatalan dengan refund hanya untuk acara terkonfirmasi yang sudah lunas.';
        }

        if (! $this->paidInvoice($event)) {
            return 'Tagihan acara ini belum lunas.';
        }

        if ($event->event_date === null || $this->calculator->remainingDays($event->event_date, $today) < 0) {
            return 'Tanggal acara sudah lewat; pembatalan tidak diterima.';
        }

        if ($this->activeCancellation($event)) {
            return 'Acara ini sudah memiliki pengajuan pembatalan yang sedang diproses.';
        }

        return null;
    }

    /**
     * Perkiraan refund (tidak disimpan) untuk ditampilkan ke klien.
     *
     * @return array<string, mixed>|null
     */
    public function preview(Event $event, ?DateTimeInterface $today = null): ?array
    {
        if ($this->submissionBlocker($event, $today) !== null) {
            return null;
        }

        return $this->calculator->forInvoice($event, $this->paidInvoice($event), CancellationCause::Klien, $today ?? now());
    }

    /**
     * Simpan pengajuan pembatalan klien beserta snapshot perhitungan saat ini.
     *
     * @param  array{reason: string, refund_bank_name: string, refund_account_number: string, refund_account_name: string}  $data
     *
     * @throws RuntimeException bila syarat pengajuan tidak terpenuhi (kode 422).
     */
    public function submit(Event $event, User $client, array $data, ?string $ip = null): Cancellation
    {
        $cancellation = DB::transaction(function () use ($event, $client, $data, $ip): Cancellation {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            Cancellation::query()->where('event_id', $locked->id)->lockForUpdate()->get();

            $now = now();
            if ($blocker = $this->submissionBlocker($locked, $now)) {
                throw new RuntimeException($blocker, 422);
            }

            $invoice = $this->paidInvoice($locked);
            $result = $this->calculator->forInvoice($locked, $invoice, CancellationCause::Klien, $now);

            $cancellation = Cancellation::create([
                'event_id' => $locked->id,
                'invoice_id' => $invoice->id,
                'reason' => $data['reason'],
                'cause' => CancellationCause::Klien,
                'refund_percent' => $result['refund_percent'],
                'refund_amount' => $result['refund_amount'],
                'unique_code_refund' => $result['unique_code_refund'],
                'provider_compensation_total' => $result['provider_compensation_total'],
                'company_income' => $result['company_income'],
                'policy_snapshot' => $result['policy_snapshot'],
                'status' => CancellationStatus::Diajukan,
                'refund_bank_name' => $data['refund_bank_name'],
                'refund_account_number' => $data['refund_account_number'],
                'refund_account_name' => $data['refund_account_name'],
            ]);

            AuditLog::create([
                'actor_id' => $client->id,
                'action' => 'cancellation_submitted',
                'subject_type' => $cancellation->getMorphClass(),
                'subject_id' => $cancellation->id,
                'from_status' => null,
                'to_status' => CancellationStatus::Diajukan->value,
                'reason' => $data['reason'],
                'ip' => $ip,
            ]);

            return $cancellation;
        });

        User::query()->where('role', Role::Admin->value)->get()
            ->each(fn (User $admin) => $admin->notify(new CancellationSubmittedNotification($cancellation)));
        $client->notify(new CancellationStatusChangedNotification($cancellation));

        return $cancellation;
    }

    /**
     * Tinjau: diajukan → ditinjau.
     */
    public function review(Cancellation $cancellation, User $admin, ?string $ip = null): Cancellation
    {
        return $this->transition($cancellation, [CancellationStatus::Diajukan], CancellationStatus::Ditinjau, $admin, 'cancellation_reviewed', null, $ip, function (Cancellation $locked) use ($admin): void {
            $locked->reviewed_by = $admin->id;
        });
    }

    /**
     * Tolak: diajukan/ditinjau → ditolak. Acara tetap terkonfirmasi.
     */
    public function reject(Cancellation $cancellation, User $admin, string $reason, ?string $ip = null): Cancellation
    {
        return $this->transition($cancellation, [CancellationStatus::Diajukan, CancellationStatus::Ditinjau], CancellationStatus::Ditolak, $admin, 'cancellation_rejected', $reason, $ip, function (Cancellation $locked) use ($admin, $reason): void {
            $locked->reviewed_by = $admin->id;
            $locked->decision_reason = $reason;
        });
    }

    /**
     * Setujui: diajukan/ditinjau → disetujui, hitung ulang dengan penyebab/persen
     * keputusan Admin memakai tanggal pengajuan (bukan tanggal persetujuan).
     */
    public function approve(Cancellation $cancellation, User $admin, CancellationCause $cause, ?int $overridePercent, ?string $reason, ?string $ip = null): Cancellation
    {
        $auditReason = null;

        return $this->transition($cancellation, [CancellationStatus::Diajukan, CancellationStatus::Ditinjau], CancellationStatus::Disetujui, $admin, 'cancellation_approved', null, $ip, function (Cancellation $locked) use ($admin, $cause, $overridePercent, $reason, &$auditReason): void {
            $event = Event::query()->whereKey($locked->event_id)->lockForUpdate()->firstOrFail();
            $snapshot = $locked->policy_snapshot ?? [];
            $requestedDate = $snapshot['requested_date'] ?? $locked->created_at;
            $tiers = $snapshot['tiers'] ?? null;

            $result = $this->calculator->forInvoice(
                $event,
                $locked->invoice,
                $cause,
                $requestedDate,
                $cause === CancellationCause::Klien ? $overridePercent : null,
                $reason,
                $tiers,
            );

            $isOverride = $result['policy_snapshot']['percent_source'] === 'override_admin';

            if (($cause !== CancellationCause::Klien || $isOverride) && blank($reason)) {
                throw new RuntimeException('Alasan wajib diisi untuk penyebab non-klien atau perubahan persen.', 422);
            }

            $auditReason = trim(sprintf(
                'Penyebab: %s; persen %d%%%s. %s',
                $cause->value,
                $result['refund_percent'],
                $isOverride ? " (override dari tier {$result['policy_snapshot']['tier_percent']}%)" : '',
                (string) $reason,
            ));

            $locked->fill([
                'cause' => $cause,
                'refund_percent' => $result['refund_percent'],
                'refund_amount' => $result['refund_amount'],
                'unique_code_refund' => $result['unique_code_refund'],
                'provider_compensation_total' => $result['provider_compensation_total'],
                'company_income' => $result['company_income'],
                'policy_snapshot' => $result['policy_snapshot'] + ['approved_at' => now()->toIso8601String(), 'approved_by' => $admin->id],
                'reviewed_by' => $admin->id,
                'decision_reason' => $reason,
            ]);
        }, function () use (&$auditReason): ?string {
            return $auditReason;
        });
    }

    /**
     * Mulai proses refund: disetujui → refund_diproses.
     */
    public function startRefund(Cancellation $cancellation, User $admin, ?string $ip = null): Cancellation
    {
        return $this->transition($cancellation, [CancellationStatus::Disetujui], CancellationStatus::RefundDiproses, $admin, 'cancellation_refund_started', null, $ip);
    }

    /**
     * Catat refund selesai: refund_diproses → refund_selesai dengan bukti transfer privat.
     */
    public function completeRefund(Cancellation $cancellation, User $admin, DateTimeInterface $paidAt, UploadedFile $proof, ?string $ip = null): Cancellation
    {
        $path = null;

        try {
            return $this->transition($cancellation, [CancellationStatus::RefundDiproses], CancellationStatus::RefundSelesai, $admin, 'cancellation_refund_completed', null, $ip, function (Cancellation $locked) use ($paidAt, $proof, &$path): void {
                $path = $proof->store('refunds', 'private');

                $media = Media::create([
                    'owner_type' => $locked->getMorphClass(),
                    'owner_id' => $locked->id,
                    'kind' => MediaKind::BuktiRefund,
                    'doc_type' => null,
                    'path' => $path,
                    'disk' => 'private',
                    'mime_type' => $proof->getClientMimeType(),
                    'size' => $proof->getSize(),
                    'original_name' => $proof->getClientOriginalName(),
                    'visibility' => MediaVisibility::Privat,
                    'sort_order' => 0,
                ]);

                $locked->refund_proof_media_id = $media->id;
                $locked->refund_paid_at = $paidAt;
            }, fn (Cancellation $locked): string => 'Refund Rp '.number_format($locked->refundTotal(), 0, ',', '.').' ditransfer ke '.$locked->refund_bank_name.' '.$locked->refund_account_number.'.');
        } catch (\Throwable $e) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }

    /**
     * Jalankan satu transisi status yang sah (kunci baris, cek status asal, AuditLog, notifikasi klien).
     *
     * @param  array<int, CancellationStatus>  $from
     * @param  (callable(Cancellation): void)|null  $apply
     * @param  (callable(Cancellation): ?string)|null  $auditReason
     *
     * @throws RuntimeException kode 409 bila status asal tidak sah.
     */
    protected function transition(
        Cancellation $cancellation,
        array $from,
        CancellationStatus $to,
        User $actor,
        string $action,
        ?string $reason,
        ?string $ip,
        ?callable $apply = null,
        ?callable $auditReason = null,
    ): Cancellation {
        $updated = DB::transaction(function () use ($cancellation, $from, $to, $actor, $action, $reason, $ip, $apply, $auditReason): Cancellation {
            /** @var Cancellation $locked */
            $locked = Cancellation::query()->whereKey($cancellation->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true)) {
                throw new RuntimeException("Pembatalan berstatus {$locked->status->label()} tidak dapat diubah ke {$to->label()}.", 409);
            }

            $fromStatus = $locked->status;

            if ($apply) {
                $apply($locked);
            }

            $locked->status = $to;
            $locked->save();

            if ($to === CancellationStatus::RefundSelesai) {
                $this->applyRefundCompletedEffects($locked, $actor, $ip);
            }

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => $action,
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => $fromStatus->value,
                'to_status' => $to->value,
                'reason' => $auditReason ? $auditReason($locked) : $reason,
                'ip' => $ip,
            ]);

            return $locked;
        });

        $updated->event?->client?->notify(new CancellationStatusChangedNotification($updated));

        return $updated;
    }

    /**
     * Efek refund selesai (Tahap B5).
     */
    protected function applyRefundCompletedEffects(Cancellation $cancellation, User $actor, ?string $ip): void
    {
        /** @var Event $event */
        $event = Event::query()->whereKey($cancellation->event_id)->lockForUpdate()->firstOrFail();
        $fromStatus = $event->status;

        $items = OrderItem::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [OrderItemStatus::Terkonfirmasi, OrderItemStatus::Disetujui, OrderItemStatus::MenungguReview])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $item->update(['status' => OrderItemStatus::Dibatalkan]);
            $this->availability->releaseHold($item);
        }

        if ($event->status !== EventStatus::Dibatalkan) {
            $event->update(['status' => EventStatus::Dibatalkan]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => 'event_cancelled_after_refund',
                'subject_type' => $event->getMorphClass(),
                'subject_id' => $event->id,
                'from_status' => $fromStatus->value,
                'to_status' => EventStatus::Dibatalkan->value,
                'reason' => "Refund pembatalan #{$cancellation->id} selesai.",
                'ip' => $ip,
            ]);
        }

        foreach ($cancellation->policy_snapshot['lines'] ?? [] as $line) {
            $compensation = (int) ($line['compensation'] ?? 0);
            $orderItem = OrderItem::query()->find($line['order_item_id'] ?? null);

            if ($compensation <= 0 || $orderItem === null) {
                continue;
            }

            $disbursement = Disbursement::query()->firstOrCreate(
                ['order_item_id' => $orderItem->id, 'type' => DisbursementType::Kompensasi],
                [
                    'provider_type' => $orderItem->provider_type,
                    'provider_id' => $orderItem->provider_id,
                    'amount' => $compensation,
                    'status' => DisbursementStatus::BelumCair,
                ],
            );

            if ($disbursement->wasRecentlyCreated) {
                $orderItem->provider?->user?->notify(new CompensationRecordedNotification($disbursement, $event->id));
            }
        }
    }
}
