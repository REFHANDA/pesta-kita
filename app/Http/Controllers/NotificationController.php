<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Notifikasi in-app (database) untuk keempat portal. Setiap pengguna hanya
 * dapat membaca dan menandai notifikasi miliknya sendiri.
 */
class NotificationController extends Controller
{
    /**
     * Judul singkat per jenis notifikasi.
     *
     * @var array<string, string>
     */
    public const TITLES = [
        'InvoiceIssuedNotification' => 'Tagihan terbit',
        'InvoiceExpiredNotification' => 'Tagihan kedaluwarsa',
        'EventCancelledNotification' => 'Acara dibatalkan',
        'CancellationSubmittedNotification' => 'Pengajuan pembatalan baru',
        'CancellationStatusChangedNotification' => 'Status pembatalan',
        'CompensationRecordedNotification' => 'Kompensasi pembatalan',
        'EventCompletedNotification' => 'Acara selesai',
        'DisbursementPaidNotification' => 'Pencairan ditransfer',
        'NewOrderSubmittedNotification' => 'Pesanan baru diajukan',
        'OrderConfirmedNotification' => 'Pesanan terkonfirmasi',
        'OrderItemReviewedNotification' => 'Keputusan pesanan',
        'OrderObjectionDecidedNotification' => 'Keputusan keberatan',
        'OrderObjectionSubmittedNotification' => 'Keberatan baru',
        'OrderSlaReminderNotification' => 'Pengingat review pesanan',
        'PaymentRejectedNotification' => 'Pembayaran ditolak',
        'PaymentValidatedNotification' => 'Pembayaran divalidasi',
        'PaymentValidationSlaReminderNotification' => 'Pengingat validasi pembayaran',
        'RundownUpdatedNotification' => 'Rundown diperbarui',
        'EvaluationSubmittedNotification' => 'Evaluasi acara',
        'ProviderBankAccountSubmittedNotification' => 'Rekening penyedia diajukan',
        'ProviderBankAccountDecidedNotification' => 'Keputusan rekening pencairan',
    ];

    public function index(Request $request): View
    {
        $portal = $this->portal($request);

        return view("{$portal}.notifikasi", [
            'notifications' => $request->user()->notifications()->latest()->paginate(15),
            'portal' => $portal,
        ]);
    }

    /**
     * Buka notifikasi: tandai dibaca lalu arahkan ke halaman terkait.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $portal = $this->portal($request);

        /** @var DatabaseNotification $item */
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return redirect()->to($this->targetUrl($portal, $item->data) ?? route("{$portal}.notifikasi"));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $portal = $this->portal($request);

        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route("{$portal}.notifikasi")->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public static function titleFor(DatabaseNotification $notification): string
    {
        return self::TITLES[class_basename($notification->type)] ?? 'Notifikasi';
    }

    protected function portal(Request $request): string
    {
        return (string) $request->route('portal');
    }

    /**
     * URL tujuan dari payload: pakai `url` bila ada, selain itu diturunkan
     * dari ID tagihan/pesanan/acara sesuai portal penerima.
     *
     * @param  array<string, mixed>  $data
     */
    protected function targetUrl(string $portal, array $data): ?string
    {
        if (! empty($data['url']) && is_string($data['url'])) {
            return $data['url'];
        }

        return match ($portal) {
            'klien' => match (true) {
                ! empty($data['invoice_id']) => route('klien.tagihan.show', $data['invoice_id']),
                ! empty($data['event_id']) => route('klien.acara.detail', $data['event_id']),
                default => null,
            },
            'vendor', 'venue' => match (true) {
                ! empty($data['disbursement_id']) => route("{$portal}.pencairan"),
                ! empty($data['order_item_id']) => route("{$portal}.pesanan.show", $data['order_item_id']),
                default => null,
            },
            'admin' => match (true) {
                ! empty($data['cancellation_id']) => route('admin.pembatalan.show', $data['cancellation_id']),
                ! empty($data['invoice_id']) => route('admin.pembayaran.show', $data['invoice_id']),
                ! empty($data['order_item_id']) => route('admin.pesanan.show', $data['order_item_id']),
                ! empty($data['event_id']) => route('admin.acara.detail', $data['event_id']),
                default => null,
            },
            default => null,
        };
    }
}
