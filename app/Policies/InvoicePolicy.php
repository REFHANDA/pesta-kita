<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\User;

/**
 * Kebijakan akses tagihan (Invoice) dan nota lunas (Receipt):
 * - Tagihan: hanya Admin dan Klien pemilik acara terkait.
 * - Pembayaran: hanya Klien pemilik acara jika status belum_dibayar atau menunggu_validasi (unggah ulang).
 * - Nota lunas: hanya Admin dan Klien pemilik acara, dan status tagihan wajib lunas.
 */
class InvoicePolicy
{
    /**
     * Bolehkah pengguna melihat rincian tagihan.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role === Role::Admin) {
            return true;
        }

        return $invoice->event?->client_id === $user->id;
    }

    /**
     * Bolehkah pengguna mengunggah bukti pembayaran untuk tagihan ini.
     */
    public function pay(User $user, Invoice $invoice): bool
    {
        if ($user->role !== Role::Klien) {
            return false;
        }

        if ($invoice->event?->client_id !== $user->id) {
            return false;
        }

        // Boleh bayar jika belum_dibayar, atau jika menunggu_validasi dengan bukti terakhir ditolak
        return in_array($invoice->status, [InvoiceStatus::BelumDibayar, InvoiceStatus::MenungguValidasi], true);
    }

    /**
     * Bolehkah pengguna melihat / mengunduh nota lunas.
     */
    public function viewReceipt(User $user, Invoice $invoice): bool
    {
        if ($invoice->status !== InvoiceStatus::Lunas) {
            return false;
        }

        if ($user->role === Role::Admin) {
            return true;
        }

        return $invoice->event?->client_id === $user->id;
    }
}
