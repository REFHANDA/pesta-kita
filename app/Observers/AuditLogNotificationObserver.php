<?php

namespace App\Observers;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PriceChangeRequest;
use App\Models\ProviderBankAccount;
use App\Models\QuoteRequest;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Notifications\PortalUpdateNotification;
use Illuminate\Database\Eloquent\Model;

class AuditLogNotificationObserver
{
    /**
     * Notify affected portal users when an audited business transition occurs.
     */
    public function created(AuditLog $auditLog): void
    {
        $subject = $auditLog->subject;
        if ($subject === null) {
            return;
        }

        switch ($auditLog->action) {
            case 'verification_submitted':
                $this->notifyAdmins(
                    'Verifikasi penyedia diajukan',
                    'Ada permohonan verifikasi yang menunggu pemeriksaan.',
                    route('admin.penyedia.index'),
                );
                break;
            case 'verification_approved':
            case 'verification_rejected':
                $this->notifyProfileOwner($subject);
                break;
            case 'quote_approved':
            case 'quote_rejected':
                if ($subject instanceof QuoteRequest) {
                    $this->notify($subject->client, $this->titleFor($auditLog), $this->messageFor($auditLog), route('klien.penawaran.detail', $subject));
                }
                break;
            case 'quote_request_submitted':
                $this->notifyQuoteProvider($subject);
                break;
            case 'event_submitted':
                $this->notifyEventSubmitted($subject);
                break;
            case 'price_change_submitted':
                $this->notifyAdmins('Pengajuan harga baru', 'Penyedia mengajukan perubahan harga untuk ditinjau.', route('admin.pengajuan-harga'));
                break;
            case 'order_item_approved':
            case 'order_item_rejected':
                $this->notifyOrderParticipants($subject, $auditLog);
                break;
            case 'order_objection_accepted':
            case 'order_objection_rejected':
                $this->notifyObjectionParticipants($subject, $auditLog);
                break;
            case 'invoice_issued':
                if ($subject instanceof Invoice) {
                    $this->notify($subject->event->client, 'Tagihan diterbitkan', 'Tagihan acara Anda telah diterbitkan.', route('klien.tagihan', $subject));
                }
                break;
            case 'payment_proof_submitted':
                if ($subject instanceof Payment) {
                    $this->notifyAdmins('Bukti pembayaran menunggu verifikasi', 'Bukti transfer baru menunggu pemeriksaan Admin.', route('admin.pembayaran.detail', $subject->invoice));
                }
                break;
            case 'payment_verified':
            case 'payment_rejected':
                if ($subject instanceof Payment) {
                    $this->notify($subject->invoice->event->client, $this->titleFor($auditLog), $this->messageFor($auditLog), route('klien.tagihan', $subject->invoice));
                }
                break;
            case 'refund_intake_submitted':
                if ($subject instanceof RefundRequest) {
                    $this->notifyAdmins('Permohonan refund baru', 'Permohonan refund baru masuk untuk ditinjau. Keputusan final tetap mengikuti kebijakan yang berlaku.', route('admin.pembatalan.detail', $subject));
                }
                break;
            case 'provider_payout_recorded':
                if ($subject instanceof Payout) {
                    $provider = $subject->orderItem->provider;
                    if ($provider !== null) {
                        $this->notifyProvider($provider, 'Pencairan dicatat', 'Pencairan untuk pesanan Anda telah dicatat Admin.', route($this->providerRole($provider).'.pencairan.detail', $subject));
                    }
                }
                break;
            case 'provider_bank_account_submitted':
                $this->notifyAdmins('Rekening payout baru menunggu review', 'Penyedia mengajukan rekening payout baru.', route('admin.provider-bank-accounts'));
                break;
            case 'provider_bank_account_approved':
            case 'provider_bank_account_rejected':
                $this->notifyBankAccountOwner($subject, $auditLog);
                break;
            case 'price_change_approved':
            case 'price_change_rejected':
                $this->notifyPriceChangeOwner($subject, $auditLog);
                break;
            case 'provider_review_submitted':
                $this->notifyReviewProvider($subject);
                break;
            case 'event_marked_complete':
                $this->notifyEventParticipants($subject);
                break;
            case 'user_account_status_changed':
                if ($subject instanceof User) {
                    $this->notify($subject, 'Status akun diperbarui', 'Status akun Anda berubah menjadi '.str_replace('_', ' ', $auditLog->to_status ?? '').'.', '/login');
                }
                break;
        }
    }

    private function notifyProfileOwner(Model $subject): void
    {
        if (! $subject instanceof VendorProfile && ! $subject instanceof VenueProfile) {
            return;
        }

        $role = $subject instanceof VendorProfile ? 'vendor' : 'venue';
        $this->notify(
            $subject->user,
            'Status verifikasi diperbarui',
            'Admin telah memperbarui status verifikasi penyedia Anda.',
            route($role.'.verifikasi'),
        );
    }

    private function notifyQuoteProvider(Model $subject): void
    {
        if (! $subject instanceof QuoteRequest) {
            return;
        }

        $provider = match (true) {
            $subject->subject instanceof CatalogItem => $subject->subject->vendorProfile,
            $subject->subject instanceof VenueRoom => $subject->subject->venue?->venueProfile,
            default => null,
        };
        if ($provider === null) {
            return;
        }

        $this->notifyProvider(
            $provider,
            'Permintaan penawaran baru',
            'Klien mengirim permintaan penawaran untuk layanan Anda.',
            route($this->providerRole($provider).'.penawaran.detail', $subject),
        );
    }

    private function notifyEventSubmitted(Model $subject): void
    {
        if (! $subject instanceof Event) {
            return;
        }

        $this->notifyAdmins('Acara baru menunggu review', 'Klien mengajukan acara dengan pesanan yang perlu ditinjau.', route('admin.acara.detail', $subject));
        foreach ($subject->orderItems()->with('provider')->get() as $item) {
            if ($item->provider !== null) {
                $this->notifyProvider(
                    $item->provider,
                    'Acara diajukan untuk review',
                    'Pesanan Anda terkait acara baru sedang ditinjau Admin.',
                    route($this->providerRole($item->provider).'.pesanan.detail', $item),
                );
            }
        }
    }

    private function notifyOrderParticipants(Model $subject, AuditLog $auditLog): void
    {
        if (! $subject instanceof OrderItem) {
            return;
        }

        $this->notify(
            $subject->event->client,
            $this->titleFor($auditLog),
            $this->messageFor($auditLog),
            route('klien.acara.detail', $subject->event),
        );

        $provider = $subject->provider;
        if ($provider !== null) {
            $this->notifyProvider(
                $provider,
                $this->titleFor($auditLog),
                $this->messageFor($auditLog),
                route($this->providerRole($provider).'.pesanan.detail', $subject),
            );
        }
    }

    private function notifyObjectionParticipants(Model $subject, AuditLog $auditLog): void
    {
        if (! $subject instanceof OrderObjection) {
            return;
        }

        $orderItem = $subject->orderItem;
        if ($orderItem->provider === null) {
            return;
        }
        $this->notify(
            $subject->providerUser,
            $this->titleFor($auditLog),
            $this->messageFor($auditLog),
            route($this->providerRole($orderItem->provider).'.keberatan'),
        );
        $this->notify(
            $orderItem->event->client,
            $this->titleFor($auditLog),
            $this->messageFor($auditLog),
            route('klien.acara.detail', $orderItem->event),
        );
    }

    private function notifyBankAccountOwner(Model $subject, AuditLog $auditLog): void
    {
        if (! $subject instanceof ProviderBankAccount) {
            return;
        }

        $profile = $subject->owner;
        if (! $profile instanceof VendorProfile && ! $profile instanceof VenueProfile) {
            return;
        }
        $role = $profile instanceof VendorProfile ? 'vendor' : 'venue';
        $this->notify($profile->user, $this->titleFor($auditLog), $this->messageFor($auditLog), route($role.'.bank-accounts'));
    }

    private function notifyPriceChangeOwner(Model $subject, AuditLog $auditLog): void
    {
        if (! $subject instanceof PriceChangeRequest) {
            return;
        }

        $owner = match (true) {
            $subject->subject instanceof \App\Models\CatalogItem => $subject->subject->vendorProfile,
            $subject->subject instanceof \App\Models\Venue => $subject->subject->venueProfile,
            default => null,
        };
        if ($owner === null) {
            return;
        }

        $role = $owner instanceof VendorProfile ? 'vendor' : 'venue';
        $this->notify($owner->user, $this->titleFor($auditLog), $this->messageFor($auditLog), route($role.'.pengajuan-harga'));
    }

    private function notifyReviewProvider(Model $subject): void
    {
        if (! $subject instanceof EventReview || $subject->orderItem->provider === null) {
            return;
        }

        $this->notifyProvider(
            $subject->orderItem->provider,
            'Ulasan klien baru',
            'Klien telah mengirim ulasan untuk layanan Anda.',
            route($this->providerRole($subject->orderItem->provider).'.ulasan'),
        );
    }

    private function notifyEventParticipants(Model $subject): void
    {
        if (! $subject instanceof Event) {
            return;
        }

        $this->notify($subject->client, 'Acara ditandai selesai', 'Acara Anda telah ditandai selesai oleh Admin.', route('klien.acara.detail', $subject));
        foreach ($subject->orderItems()->with('provider')->get() as $item) {
            if ($item->provider !== null) {
                $this->notifyProvider(
                    $item->provider,
                    'Acara ditandai selesai',
                    'Acara yang melibatkan layanan Anda telah ditandai selesai.',
                    route($this->providerRole($item->provider).'.pesanan.detail', $item),
                );
            }
        }
    }

    private function notifyProvider(?Model $provider, string $title, string $message, string $url): void
    {
        if ($provider instanceof VendorProfile || $provider instanceof VenueProfile) {
            $this->notify($provider->user, $title, $message, $url);
        }
    }

    private function providerRole(Model $provider): string
    {
        return $provider instanceof VendorProfile ? 'vendor' : 'venue';
    }

    private function notifyAdmins(string $title, string $message, string $url): void
    {
        User::query()->where('role', Role::Admin)
            ->where('status', UserStatus::Aktif)
            ->get()
            ->each(fn (User $admin) => $this->notify($admin, $title, $message, $url));
    }

    private function notify(?User $user, string $title, string $message, string $url): void
    {
        if ($user === null) {
            return;
        }

        $parts = parse_url($url);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (isset($parts['host'])) {
            if ($appHost !== null && $parts['host'] !== $appHost) {
                $url = '/';
            } else {
                $url = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
            }
        }

        $user->notify(new PortalUpdateNotification($title, $message, $url));
    }

    private function titleFor(AuditLog $auditLog): string
    {
        return match ($auditLog->action) {
            'quote_approved' => 'Penawaran disetujui Admin',
            'quote_rejected' => 'Penawaran ditolak Admin',
            'order_item_approved' => 'Pesanan disetujui Admin',
            'order_item_rejected' => 'Pesanan ditolak Admin',
            'order_objection_accepted' => 'Keberatan diterima Admin',
            'order_objection_rejected' => 'Keberatan ditolak Admin',
            'payment_verified' => 'Pembayaran diverifikasi',
            'payment_rejected' => 'Bukti pembayaran ditolak',
            'provider_bank_account_approved' => 'Rekening payout disetujui',
            'provider_bank_account_rejected' => 'Rekening payout ditolak',
            'price_change_approved' => 'Pengajuan harga disetujui',
            'price_change_rejected' => 'Pengajuan harga ditolak',
            default => 'Pembaruan transaksi',
        };
    }

    private function messageFor(AuditLog $auditLog): string
    {
        return match ($auditLog->action) {
            'payment_rejected' => $auditLog->reason ?: 'Bukti pembayaran Anda ditolak. Silakan periksa catatan Admin.',
            'provider_bank_account_rejected', 'price_change_rejected' => $auditLog->reason ?: 'Lihat catatan Admin pada halaman terkait.',
            default => 'Status proses Anda telah diperbarui. Buka halaman terkait untuk melihat rinciannya.',
        };
    }
}
