<?php

namespace App\Console\Commands;

use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderSlaReminderNotification;
use Illuminate\Console\Command;

/**
 * Command terjadwal untuk mengirimkan notifikasi pengingat SLA review admin
 * bagi pesanan yang mendekati atau telah melewati batas 24 jam.
 */
class RemindOrderSlaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:remind-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat SLA review 24 jam untuk pesanan yang mendekati atau melewati batas waktu.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa pesanan yang mendekati atau melewati SLA review...');

        $now = now();
        $warningThreshold = $now->copy()->addHours(4);

        $pendingOrders = OrderItem::query()
            ->where('status', OrderItemStatus::MenungguReview)
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<=', $warningThreshold)
            ->with(['event', 'subject'])
            ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('Tidak ada pesanan yang mendekati batas SLA.');

            return Command::SUCCESS;
        }

        $admins = User::query()->where('role', Role::Admin->value)->get();
        if ($admins->isEmpty()) {
            $this->warn('Tidak ada user Admin untuk menerima notifikasi.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pendingOrders as $orderItem) {
            $isOverdue = $now->greaterThanOrEqualTo($orderItem->review_due_at);

            foreach ($admins as $admin) {
                $admin->notify(new OrderSlaReminderNotification($orderItem, $isOverdue));
            }
            $count++;
        }

        $this->info("Selesai. Notifikasi dikirimkan untuk {$count} pesanan.");

        return Command::SUCCESS;
    }
}
