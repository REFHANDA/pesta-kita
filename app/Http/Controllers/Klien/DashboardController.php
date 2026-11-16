<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda Klien: ringkasan nyata dari database (acara milik akun login).
 * Tagihan, pembayaran, dan penilaian belum punya backend (BE-6/BE-7);
 * view memberi catatan jujur, bukan angka contoh.
 */
class DashboardController extends Controller
{
    /**
     * Ringkasan acara milik Klien login.
     */
    public function index(Request $request): View
    {
        $events = $request->user()->events()
            ->orderBy('event_date')
            ->get();

        $berjalan = $events->filter(fn ($event) => in_array($event->status, [
            EventStatus::Draft,
            EventStatus::Diajukan,
            EventStatus::MenungguPembayaran,
            EventStatus::Terkonfirmasi,
        ], true))->count();

        $selesai = $events->filter(fn ($event) => in_array($event->status, [
            EventStatus::Selesai,
            EventStatus::Dinilai,
        ], true))->count();

        return view('klien.index', [
            'events' => $events,
            'berjalanCount' => $berjalan,
            'selesaiCount' => $selesai,
        ]);
    }
}
