<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\RundownItem;
use App\Models\User;
use App\Notifications\RundownUpdatedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aturan rundown acara (Tahap C1). Seluruh aturan akses, status, dan batas
 * item berada di kelas ini; controller hanya memanggil dan menampilkan.
 *
 * - Klien pemilik: melihat seluruh rundown acaranya, menulis semua item.
 * - Vendor: melihat rundown acara yang pesanannya terkonfirmasi/selesai,
 *   menulis hanya item buatannya yang tertaut ke pesanan miliknya.
 * - Venue dan Admin: hanya melihat.
 * - Tulis hanya saat acara `terkonfirmasi`; terkunci permanen setelah selesai.
 */
class RundownService
{
    /**
     * Batas maksimal item rundown per acara.
     */
    public const MAX_ITEMS = 100;

    /**
     * Status acara yang memiliki rundown (baca).
     *
     * @var array<int, EventStatus>
     */
    public const READABLE_STATUSES = [
        EventStatus::Terkonfirmasi,
        EventStatus::Selesai,
        EventStatus::Dievaluasi,
    ];

    /**
     * Status acara yang memperbolehkan perubahan rundown.
     *
     * @var array<int, EventStatus>
     */
    public const WRITABLE_STATUSES = [
        EventStatus::Terkonfirmasi,
    ];

    /**
     * Status pesanan penyedia yang membuka akses baca rundown.
     *
     * @var array<int, OrderItemStatus>
     */
    public const PROVIDER_ORDER_STATUSES = [
        OrderItemStatus::Terkonfirmasi,
        OrderItemStatus::Selesai,
    ];

    /**
     * Bolehkah pengguna melihat rundown acara ini.
     */
    public function canView(User $user, Event $event): bool
    {
        // Admin melihat seluruh acara non-draft (halaman Admin tidak memuat draft).
        if ($user->role === Role::Admin) {
            return $event->status !== EventStatus::Draft;
        }

        if (! in_array($event->status, self::READABLE_STATUSES, true)) {
            return false;
        }

        if ($user->role === Role::Klien) {
            return (int) $event->client_id === (int) $user->id;
        }

        $profile = $user->providerProfile();
        if ($profile === null) {
            return false;
        }

        return $this->providerOrderQuery($event, $profile)->exists();
    }

    /**
     * Bolehkah pengguna menambah/mengubah/menghapus item rundown acara ini.
     */
    public function canWrite(User $user, Event $event): bool
    {
        if (! in_array($event->status, self::WRITABLE_STATUSES, true)) {
            return false;
        }

        if (! in_array($user->role, [Role::Klien, Role::Vendor], true)) {
            return false;
        }

        return $this->canView($user, $event);
    }

    /**
     * Bolehkah pengguna mengubah/menghapus item tertentu.
     * Vendor hanya item buatannya sendiri yang tertaut ke pesanannya.
     */
    public function canModifyItem(User $user, RundownItem $item): bool
    {
        $event = $item->event;
        if (! $event || ! $this->canWrite($user, $event)) {
            return false;
        }

        if ($user->role === Role::Klien) {
            return (int) $event->client_id === (int) $user->id;
        }

        if ($user->role === Role::Vendor) {
            if ((int) $item->created_by !== (int) $user->id || $item->order_item_id === null) {
                return false;
            }

            return $this->ownsConfirmedOrderItem($user, $item->orderItem);
        }

        return false;
    }

    /**
     * Daftar rundown acara, urut jam mulai lalu id.
     *
     * @return Collection<int, RundownItem>
     */
    public function items(Event $event): Collection
    {
        return $event->rundownItems()
            ->with(['orderItem.subject', 'creator'])
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * Pesanan acara yang boleh dikaitkan aktor ke item rundown.
     *
     * Vendor: hanya pesanan miliknya yang terkonfirmasi.
     * Klien: seluruh pesanan pada acaranya.
     *
     * @return Collection<int, OrderItem>
     */
    public function linkableOrderItems(Event $event, User $user): Collection
    {
        $query = $event->orderItems()->with('subject');

        if ($user->role === Role::Vendor) {
            $profile = $user->providerProfile();
            $query->where('provider_type', 'vendor_profile')
                ->where('provider_id', $profile?->id)
                ->where('status', OrderItemStatus::Terkonfirmasi);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Tambah item rundown. Melempar ValidationException bila aturan dilanggar.
     *
     * @param  array{start_time: string, end_time?: ?string, activity: string, description?: ?string, order_item_id?: ?int}  $data
     */
    public function add(Event $event, User $actor, array $data, ?string $ip = null): RundownItem
    {
        return DB::transaction(function () use ($event, $actor, $data, $ip): RundownItem {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $this->canWrite($actor, $locked)) {
                throw ValidationException::withMessages([
                    'activity' => $this->writeDeniedMessage($locked),
                ]);
            }

            if ($locked->rundownItems()->count() >= self::MAX_ITEMS) {
                throw ValidationException::withMessages([
                    'activity' => 'Rundown acara maksimal '.self::MAX_ITEMS.' item.',
                ]);
            }

            $this->assertTimeOrder($data);
            $orderItemId = $this->resolveOrderItemId($locked, $actor, $data['order_item_id'] ?? null);

            $item = $locked->rundownItems()->create([
                'created_by' => $actor->id,
                'order_item_id' => $orderItemId,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'activity' => $data['activity'],
                'description' => $data['description'] ?? null,
            ]);

            $this->audit($actor, $locked, 'rundown_item_added', $item, $ip, null);
            $this->notifyChanged($locked, $actor);

            return $item;
        });
    }

    /**
     * Ubah item rundown.
     *
     * @param  array{start_time: string, end_time?: ?string, activity: string, description?: ?string, order_item_id?: ?int}  $data
     */
    public function update(RundownItem $item, User $actor, array $data, ?string $ip = null): RundownItem
    {
        return DB::transaction(function () use ($item, $actor, $data, $ip): RundownItem {
            $eventId = $item->event_id;
            /** @var Event $locked */
            $locked = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            /** @var RundownItem $lockedItem */
            $lockedItem = RundownItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $this->canModifyItem($actor, $lockedItem)) {
                throw ValidationException::withMessages([
                    'activity' => $this->writeDeniedMessage($locked),
                ]);
            }

            $this->assertTimeOrder($data);
            $orderItemId = $this->resolveOrderItemId($locked, $actor, $data['order_item_id'] ?? null);

            $lockedItem->update([
                'order_item_id' => $orderItemId,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'activity' => $data['activity'],
                'description' => $data['description'] ?? null,
            ]);

            $this->audit($actor, $locked, 'rundown_item_updated', $lockedItem, $ip, null);
            $this->notifyChanged($locked, $actor);

            return $lockedItem;
        });
    }

    /**
     * Hapus item rundown.
     */
    public function delete(RundownItem $item, User $actor, ?string $ip = null): void
    {
        DB::transaction(function () use ($item, $actor, $ip): void {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($item->event_id)->lockForUpdate()->firstOrFail();
            /** @var RundownItem $lockedItem */
            $lockedItem = RundownItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $this->canModifyItem($actor, $lockedItem)) {
                throw ValidationException::withMessages([
                    'activity' => $this->writeDeniedMessage($locked),
                ]);
            }

            $snapshot = $lockedItem->activity;
            $lockedItem->delete();

            $this->audit($actor, $locked, 'rundown_item_deleted', null, $ip, $snapshot);
            $this->notifyChanged($locked, $actor);
        });
    }

    /**
     * Acara yang rundown-nya boleh dilihat penyedia login, urut tanggal.
     *
     * @return Collection<int, Event>
     */
    public function eventsForProvider(User $user): Collection
    {
        $profile = $user->providerProfile();
        if ($profile === null) {
            return new Collection;
        }

        $statuses = array_map(fn (OrderItemStatus $status): string => $status->value, self::PROVIDER_ORDER_STATUSES);

        return Event::query()
            ->whereIn('status', array_column(self::READABLE_STATUSES, 'value'))
            ->whereHas('orderItems', function ($query) use ($profile, $statuses): void {
                $query->where('provider_type', $profile->getMorphClass())
                    ->where('provider_id', $profile->id)
                    ->whereIn('status', $statuses);
            })
            ->orderBy('event_date')
            ->get();
    }

    /**
     * Pesan status yang menjelaskan mengapa rundown tidak dapat ditulis.
     */
    public function writeDeniedMessage(Event $event): string
    {
        return match ($event->status) {
            EventStatus::Selesai, EventStatus::Dievaluasi => 'Rundown dikunci karena acara sudah selesai.',
            EventStatus::Dibatalkan => 'Acara dibatalkan; rundown tidak tersedia.',
            EventStatus::Terkonfirmasi => 'Anda tidak berhak mengubah rundown acara ini.',
            default => 'Rundown tersedia setelah pembayaran terkonfirmasi.',
        };
    }

    /**
     * Apakah acara sudah melewati masa tulis rundown.
     */
    public function isLocked(Event $event): bool
    {
        return in_array($event->status, [EventStatus::Selesai, EventStatus::Dievaluasi], true);
    }

    /**
     * Query pesanan penyedia pada acara dengan status yang membuka akses.
     *
     * @return Builder<OrderItem>
     */
    protected function providerOrderQuery(Event $event, object $profile)
    {
        return OrderItem::query()
            ->where('event_id', $event->id)
            ->where('provider_type', $profile->getMorphClass())
            ->where('provider_id', $profile->id)
            ->whereIn('status', array_map(fn (OrderItemStatus $status): string => $status->value, self::PROVIDER_ORDER_STATUSES));
    }

    /**
     * Pesanan vendor milik aktor pada acara yang sama dan berstatus terkonfirmasi.
     */
    protected function ownsConfirmedOrderItem(User $user, ?OrderItem $orderItem): bool
    {
        if ($orderItem === null) {
            return false;
        }

        $profile = $user->vendorProfile;

        return $profile !== null
            && $orderItem->provider_type === 'vendor_profile'
            && (int) $orderItem->provider_id === (int) $profile->id
            && $orderItem->status === OrderItemStatus::Terkonfirmasi;
    }

    /**
     * Validasi dan normalisasi order_item_id sesuai peran aktor.
     */
    protected function resolveOrderItemId(Event $event, User $actor, ?int $orderItemId): ?int
    {
        if ($orderItemId === null) {
            if ($actor->role === Role::Vendor) {
                throw ValidationException::withMessages([
                    'order_item_id' => 'Vendor wajib menautkan item rundown ke pesanan miliknya.',
                ]);
            }

            return null;
        }

        $orderItem = OrderItem::query()->whereKey($orderItemId)->first();

        if ($orderItem === null || (int) $orderItem->event_id !== (int) $event->id) {
            throw ValidationException::withMessages([
                'order_item_id' => 'Pesanan yang dipilih bukan bagian dari acara ini.',
            ]);
        }

        if ($actor->role === Role::Vendor) {
            if (! $this->ownsConfirmedOrderItem($actor, $orderItem)) {
                throw ValidationException::withMessages([
                    'order_item_id' => 'Vendor hanya dapat menautkan item ke pesanannya sendiri yang terkonfirmasi.',
                ]);
            }
        }

        return (int) $orderItem->id;
    }

    /**
     * Kirim notifikasi perubahan rundown (Tahap C6).
     * Klien -> penyedia dengan pesanan terkonfirmasi/selesai pada acara itu;
     * vendor -> klien pemilik. Notifikasi belum dibaca untuk acara yang sama
     * digabung agar tidak berlipat saat klien mengedit banyak item.
     */
    protected function notifyChanged(Event $event, User $actor): void
    {
        if ($actor->role === Role::Klien) {
            $recipients = OrderItem::query()
                ->where('event_id', $event->id)
                ->whereIn('status', array_map(fn (OrderItemStatus $status): string => $status->value, self::PROVIDER_ORDER_STATUSES))
                ->with('provider.user')
                ->get()
                ->map(fn (OrderItem $item) => $item->provider?->user)
                ->filter()
                ->unique('id');

            foreach ($recipients as $recipient) {
                $audience = $recipient->role === Role::Venue ? 'venue' : 'vendor';
                $this->notifyOnce($recipient, $event, new RundownUpdatedNotification($event, $audience));
            }

            return;
        }

        if ($actor->role === Role::Vendor && $event->client !== null) {
            $this->notifyOnce($event->client, $event, new RundownUpdatedNotification($event, 'klien'));
        }
    }

    /**
     * Kirim notifikasi hanya bila belum ada notifikasi sejenis yang belum
     * dibaca untuk acara yang sama.
     */
    protected function notifyOnce(User $user, Event $event, Notification $notification): void
    {
        $exists = $user->unreadNotifications()
            ->where('type', $notification::class)
            ->get()
            ->contains(fn ($stored): bool => (int) ($stored->data['event_id'] ?? 0) === (int) $event->id);

        if (! $exists) {
            $user->notify($notification);
        }
    }

    /**
     * Jam selesai (bila ada) harus setelah jam mulai; format HH:MM sehingga
     * perbandingan teks setara perbandingan waktu.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertTimeOrder(array $data): void
    {
        $start = (string) ($data['start_time'] ?? '');
        $end = $data['end_time'] ?? null;

        if (is_string($end) && $end !== '' && $end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai harus setelah jam mulai.',
            ]);
        }
    }

    /**
     * Catat jejak audit perubahan rundown (bukan data transaksi).
     */
    protected function audit(User $actor, Event $event, string $action, ?RundownItem $item, ?string $ip, ?string $note): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $event->getMorphClass(),
            'subject_id' => $event->id,
            'from_status' => $event->status->value,
            'to_status' => $event->status->value,
            'reason' => $note !== null
                ? 'Rundown: item "'.$note.'" dihapus.'
                : 'Rundown item #'.($item?->id ?? '-').' oleh '.$actor->role->label().'.',
            'ip' => $ip,
        ]);
    }
}
