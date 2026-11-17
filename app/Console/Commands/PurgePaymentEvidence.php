<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PurgePaymentEvidence extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evidence:prune';

    /**
     * The console command description.
     */
    protected $description = 'Delete payment evidence five years after event closure';

    /**
     * Delete private proof files once their five-year retention period ends.
     */
    public function handle(): int
    {
        $cutoff = now()->subYears(5);
        $deleted = 0;

        $payments = Payment::query()
            ->whereNotNull('proof_path')
            ->whereHas('invoice.event', fn ($query) => $query
                ->whereIn('status', [EventStatus::Selesai->value, EventStatus::Dinilai->value])
                ->whereNotNull('closed_at')
                ->where('closed_at', '<=', $cutoff))
            ->get();

        foreach ($payments as $payment) {
            $this->deleteEvidence($payment->proof_disk, $payment->proof_path);
            $payment->update(['proof_path' => null]);
            $deleted++;
        }

        $payouts = Payout::query()
            ->whereNotNull('proof_path')
            ->whereHas('orderItem.event', fn ($query) => $query
                ->whereIn('status', [EventStatus::Selesai->value, EventStatus::Dinilai->value])
                ->whereNotNull('closed_at')
                ->where('closed_at', '<=', $cutoff))
            ->get();

        foreach ($payouts as $payout) {
            $this->deleteEvidence($payout->proof_disk, $payout->proof_path);
            $payout->update(['proof_path' => null]);
            $deleted++;
        }

        $this->info("Removed {$deleted} expired proof file(s).");

        return self::SUCCESS;
    }

    /**
     * Remove a private file or fail explicitly when storage is inconsistent.
     */
    private function deleteEvidence(string $diskName, ?string $path): void
    {
        if (! is_string($path) || ! Storage::disk($diskName)->exists($path)) {
            throw new RuntimeException('A stored proof file is missing before its retention cleanup.');
        }

        if (! Storage::disk($diskName)->delete($path)) {
            throw new RuntimeException('A stored proof file could not be deleted.');
        }
    }
}
