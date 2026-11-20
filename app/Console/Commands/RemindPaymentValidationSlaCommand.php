<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PaymentValidationSlaReminderNotification;
use Illuminate\Console\Command;

/**
 * Command terjadwal untuk mengirimkan notifikasi pengingat SLA validasi pembayaran
 * bagi bukti transfer yang mendekati atau telah melewati batas SLA 24 jam.
 */
class RemindPaymentValidationSlaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:remind-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat SLA validasi pembayaran 24 jam untuk bukti transfer yang mendekati atau melewati batas waktu.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa pembayaran menunggu validasi yang mendekati atau melewati SLA 24 jam...');

        $now = now();
        $slaHours = (int) Setting::get('admin_review_sla_hours', 24);

        // Ambil pembayaran menunggu validasi yang diunggah minimal (slaHours - 4) jam yang lalu
        $warningAgeHours = max(1, $slaHours - 4);
        $thresholdTime = $now->copy()->subHours($warningAgeHours);

        $pendingPayments = Payment::query()
            ->where('status', PaymentStatus::MenungguValidasi)
            ->where('created_at', '<=', $thresholdTime)
            ->with(['invoice', 'companyBankAccount'])
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('Tidak ada pembayaran yang mendekati atau melewati batas SLA validasi.');

            return Command::SUCCESS;
        }

        $admins = User::query()->where('role', Role::Admin->value)->get();
        if ($admins->isEmpty()) {
            $this->warn('Tidak ada user Admin untuk menerima notifikasi.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pendingPayments as $payment) {
            $slaDeadline = $payment->created_at->copy()->addHours($slaHours);
            $isOverdue = $now->greaterThanOrEqualTo($slaDeadline);

            foreach ($admins as $admin) {
                $admin->notify(new PaymentValidationSlaReminderNotification($payment, $isOverdue));
            }
            $count++;
        }

        $this->info("Selesai. Notifikasi SLA validasi pembayaran dikirimkan untuk {$count} bukti transfer.");

        return Command::SUCCESS;
    }
}
