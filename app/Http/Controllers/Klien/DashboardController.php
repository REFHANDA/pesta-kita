<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
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
            ->with(['orderItems', 'cancellations'])
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
            EventStatus::Dievaluasi,
        ], true))->count();

        $pendingInvoices = Invoice::query()
            ->whereHas('event', fn ($q) => $q->where('client_id', $request->user()->id))
            ->whereIn('status', [InvoiceStatus::BelumDibayar, InvoiceStatus::MenungguValidasi])
            ->with('event')
            ->latest()
            ->get();

        return view('klien.index', [
            'events' => $events,
            'berjalanCount' => $berjalan,
            'selesaiCount' => $selesai,
            'pendingInvoices' => $pendingInvoices,
        ]);
    }
}
