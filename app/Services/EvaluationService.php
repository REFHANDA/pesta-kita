<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Notifications\EvaluationSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Evaluasi acara oleh Klien (Tahap C4).
 *
 * - Subjek dinilai dihitung server dari pesanan acara berstatus selesai:
 *   satu venue, satu per vendor (bukan per layanan), dan satu Admin.
 * - Satu evaluasi per acara, final (tidak dapat diubah/dihapus).
 * - Tidak menyentuh data uang apa pun.
 */
class EvaluationService
{
    /**
     * Subjek penilaian yang dihitung server untuk acara ini.
     *
     * @return array<int, array{key: string, type: string, id: int|null, name: string}>
     */
    public function subjectsFor(Event $event): array
    {
        $items = OrderItem::query()
            ->where('event_id', $event->id)
            ->where('status', OrderItemStatus::Selesai)
            ->whereIn('provider_type', ['vendor_profile', 'venue_profile'])
            ->with('provider')
            ->orderBy('id')
            ->get();

        $subjects = [];
        $venueSeen = false;
        $vendorsSeen = [];

        foreach ($items as $item) {
            if ($item->provider_type === 'venue_profile') {
                if ($venueSeen) {
                    continue;
                }

                $venueSeen = true;
                $subjects[] = [
                    'key' => 'venue',
                    'type' => 'venue',
                    'id' => (int) $item->provider_id,
                    'name' => $item->provider?->business_name ?? 'Venue',
                ];

                continue;
            }

            $vendorId = (int) $item->provider_id;
            if (isset($vendorsSeen[$vendorId])) {
                continue;
            }

            $vendorsSeen[$vendorId] = true;
            $subjects[] = [
                'key' => 'vendor:'.$vendorId,
                'type' => 'vendor',
                'id' => $vendorId,
                'name' => $item->provider?->business_name ?? 'Vendor',
            ];
        }

        $subjects[] = [
            'key' => 'admin',
            'type' => 'admin',
            'id' => null,
            'name' => 'Pesta Kita (Admin)',
        ];

        return $subjects;
    }

    /**
     * Evaluasi yang sudah tersimpan untuk acara (null bila belum ada).
     */
    public function existing(Event $event): ?Evaluation
    {
        return $event->evaluation()->with('items')->first();
    }

    /**
     * Bolehkah pengguna mengisi evaluasi acara ini.
     */
    public function canSubmit(User $user, Event $event): bool
    {
        return (int) $event->client_id === (int) $user->id
            && $event->status === EventStatus::Selesai
            && ! $event->evaluation()->exists();
    }

    /**
     * Simpan evaluasi final. Semua subjek wajib dinilai; subjek asing ditolak.
     *
     * @param  array<string, array{rating: int|string, comment?: ?string}>  $ratings
     *
     * @throws RuntimeException kode 409 bila evaluasi sudah ada.
     */
    public function submit(Event $event, User $client, array $ratings, ?string $systemFeedback, ?string $ip = null): Evaluation
    {
        return DB::transaction(function () use ($event, $client, $ratings, $systemFeedback, $ip): Evaluation {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->client_id !== (int) $client->id) {
                throw new RuntimeException('Hanya pemilik acara yang dapat mengisi evaluasi.', 403);
            }

            // Evaluasi ganda selalu 409, termasuk saat status sudah `dievaluasi`.
            if ($locked->evaluation()->exists()) {
                throw new RuntimeException('Evaluasi acara ini sudah dikirim dan tidak dapat diubah.', 409);
            }

            if ($locked->status !== EventStatus::Selesai) {
                throw new RuntimeException('Evaluasi hanya dapat diisi setelah acara selesai.', 422);
            }

            $subjects = $this->subjectsFor($locked);
            $expectedKeys = array_column($subjects, 'key');
            $givenKeys = array_keys($ratings);

            sort($expectedKeys);
            sort($givenKeys);

            if ($expectedKeys !== $givenKeys) {
                throw ValidationException::withMessages([
                    'ratings' => 'Penilaian harus diisi untuk seluruh subjek acara ini dan tidak boleh memuat subjek lain.',
                ]);
            }

            $evaluation = Evaluation::create([
                'event_id' => $locked->id,
                'system_feedback' => $systemFeedback,
            ]);

            foreach ($subjects as $subject) {
                $entry = $ratings[$subject['key']];
                $rating = (int) $entry['rating'];

                if ($rating < 1 || $rating > 5) {
                    throw ValidationException::withMessages([
                        'ratings.'.$subject['key'].'.rating' => 'Rating harus bilangan bulat 1 sampai 5.',
                    ]);
                }

                $evaluation->items()->create([
                    'target_type' => $subject['type'],
                    'target_id' => $subject['id'],
                    'rating' => $rating,
                    'comment' => $entry['comment'] ?? null,
                ]);
            }

            $locked->update(['status' => EventStatus::Dievaluasi]);

            AuditLog::create([
                'actor_id' => $client->id,
                'action' => 'event_evaluated',
                'subject_type' => $locked->getMorphClass(),
                'subject_id' => $locked->id,
                'from_status' => EventStatus::Selesai->value,
                'to_status' => EventStatus::Dievaluasi->value,
                'reason' => 'Evaluasi acara dikirim ('.$evaluation->items()->count().' subjek).',
                'ip' => $ip,
            ]);

            $this->notifySubmitted($locked, $subjects);

            return $evaluation->load('items');
        });
    }

    /**
     * Notifikasi evaluasi (Tahap C6): Admin dan tiap penyedia yang dinilai.
     * Payload penyedia tanpa nama klien dan tanpa isi komentar.
     *
     * @param  array<int, array{key: string, type: string, id: int|null, name: string}>  $subjects
     */
    protected function notifySubmitted(Event $event, array $subjects): void
    {
        $admins = User::query()->where('role', Role::Admin->value)->get();
        foreach ($admins as $admin) {
            $admin->notify(new EvaluationSubmittedNotification($event, 'admin'));
        }

        foreach ($subjects as $subject) {
            if ($subject['type'] === 'admin' || $subject['id'] === null) {
                continue;
            }

            $profile = $subject['type'] === 'vendor'
                ? VendorProfile::query()->find($subject['id'])
                : VenueProfile::query()->find($subject['id']);

            $profile?->user?->notify(new EvaluationSubmittedNotification($event, 'provider'));
        }
    }
}
