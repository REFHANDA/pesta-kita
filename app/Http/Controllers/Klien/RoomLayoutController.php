<?php

namespace App\Http\Controllers\Klien;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Klien\UpdateRoomLayoutRequest;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Susunan ruangan pilihan klien (Tahap D2). Dapat diubah selama acara draft
 * sampai terkonfirmasi; terkunci setelah selesai/dibatalkan.
 */
class RoomLayoutController extends Controller
{
    /**
     * Status acara yang masih memperbolehkan perubahan susunan.
     *
     * @var array<int, EventStatus>
     */
    public const EDITABLE = [
        EventStatus::Draft,
        EventStatus::Diajukan,
        EventStatus::MenungguPembayaran,
        EventStatus::Terkonfirmasi,
    ];

    public function update(UpdateRoomLayoutRequest $request, Event $event): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);
        abort_if(! in_array($event->status, self::EDITABLE, true), 403, 'Susunan ruangan terkunci karena acara sudah selesai atau dibatalkan.');

        DB::transaction(function () use ($request, $event): void {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $before = $locked->room_layout?->value;

            $locked->update([
                'room_layout' => $request->validated('room_layout'),
                'room_layout_notes' => $request->validated('room_layout_notes'),
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'event_room_layout_updated',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => $locked->status->value,
                'to_status' => $locked->status->value,
                'reason' => 'Susunan ruangan: '.($before ?? 'kosong').' -> '.($locked->room_layout?->value ?? 'kosong').'.',
                'ip' => $request->ip(),
            ]);
        });

        return redirect()
            ->to(route('klien.acara.detail', $event).'#susunan-ruangan')
            ->with('success', 'Susunan ruangan berhasil disimpan.');
    }
}
