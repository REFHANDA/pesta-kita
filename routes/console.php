<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FASE 4: Jadwal command EMS
// 1. Penerbitan tagihan otomatis tiap 15 menit (idempoten dan tidak overlap)
Schedule::command('invoices:issue')->everyFifteenMinutes()->withoutOverlapping();

// 2. Pengingat SLA review pesanan Admin tiap jam
Schedule::command('orders:remind-sla')->hourly();

// FASE 5: Pembatalan tagihan kedaluwarsa tiap jam
Schedule::command('invoices:expire')->hourly()->withoutOverlapping();

// FASE 5: Pengingat SLA validasi pembayaran Admin (24 jam) tiap jam
Schedule::command('payments:remind-sla')->hourly()->withoutOverlapping();
