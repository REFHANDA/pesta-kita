<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\RundownItem;
use App\Models\User;
use App\Services\RundownService;

/**
 * Otorisasi rundown acara (Tahap C1). Seluruh aturan peran dan status
 * diserahkan ke RundownService agar tidak ada dua sumber kebenaran.
 */
class RundownItemPolicy
{
    public function __construct(protected RundownService $rundown) {}

    /**
     * Akses baca daftar rundown satu acara.
     */
    public function viewAny(User $user, Event $event): bool
    {
        return $this->rundown->canView($user, $event);
    }

    /**
     * Akses baca satu item rundown.
     */
    public function view(User $user, RundownItem $item): bool
    {
        $event = $item->event;

        return $event !== null && $this->rundown->canView($user, $event);
    }

    /**
     * Tambah item pada acara.
     */
    public function create(User $user, Event $event): bool
    {
        return $this->rundown->canWrite($user, $event);
    }

    /**
     * Ubah item (vendor hanya item miliknya yang tertaut pesanannya).
     */
    public function update(User $user, RundownItem $item): bool
    {
        return $this->rundown->canModifyItem($user, $item);
    }

    /**
     * Hapus item (vendor hanya item miliknya yang tertaut pesanannya).
     */
    public function delete(User $user, RundownItem $item): bool
    {
        return $this->rundown->canModifyItem($user, $item);
    }
}
