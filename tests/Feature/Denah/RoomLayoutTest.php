<?php

namespace Tests\Feature\Denah;

use App\Enums\EventStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tahap D2: denah per ruangan (maks 3) dan susunan ruangan acara.
 */
class RoomLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * @return array{owner: User, venue: Venue, room: VenueRoom}
     */
    protected function venueFixture(VerificationStatus $status = VerificationStatus::Terverifikasi): array
    {
        $owner = User::factory()->venue()->create();
        $owner->venueProfile->update(['verification_status' => $status]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $owner->venueProfile->id,
            'verification_status' => $status,
        ]);

        $room = $venue->rooms()->create(['name' => 'Aula Utama', 'capacity' => 300]);

        return compact('owner', 'venue', 'room');
    }

    public function test_pemilik_mengunggah_maksimal_tiga_denah(): void
    {
        $f = $this->venueFixture();

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($f['owner'])
                ->post(route('venue.ruangan.denah.store', $f['room']), [
                    'photo' => UploadedFile::fake()->image("denah-{$i}.png", 800, 600),
                ])
                ->assertRedirect();
        }

        $this->assertSame(3, Media::where('kind', MediaKind::Denah)->where('owner_id', $f['room']->id)->count());

        $this->actingAs($f['owner'])
            ->post(route('venue.ruangan.denah.store', $f['room']), [
                'photo' => UploadedFile::fake()->image('denah-4.png'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertSame(3, Media::where('kind', MediaKind::Denah)->count());

        $media = Media::where('kind', MediaKind::Denah)->firstOrFail();
        Storage::disk('private')->assertExists($media->path);
        $this->assertSame(MediaVisibility::Publik, $media->visibility);
    }

    public function test_bukan_pemilik_dan_bukan_gambar_ditolak(): void
    {
        $f = $this->venueFixture();
        $other = User::factory()->venue()->create();

        $this->actingAs($other)
            ->post(route('venue.ruangan.denah.store', $f['room']), [
                'photo' => UploadedFile::fake()->image('denah.png'),
            ])
            ->assertForbidden();

        $client = User::factory()->klien()->create();
        $this->actingAs($client)
            ->post(route('venue.ruangan.denah.store', $f['room']), [
                'photo' => UploadedFile::fake()->image('denah.png'),
            ])
            ->assertForbidden();

        $this->actingAs($f['owner'])
            ->post(route('venue.ruangan.denah.store', $f['room']), [
                'photo' => UploadedFile::fake()->create('denah.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, Media::where('kind', MediaKind::Denah)->count());
    }

    public function test_denah_privat_sebelum_terverifikasi_dan_publik_setelah_persetujuan_admin(): void
    {
        $f = $this->venueFixture(VerificationStatus::Menunggu);

        $this->actingAs($f['owner'])->post(route('venue.ruangan.denah.store', $f['room']), [
            'photo' => UploadedFile::fake()->image('denah.png'),
        ])->assertRedirect();

        $media = Media::where('kind', MediaKind::Denah)->firstOrFail();
        $this->assertSame(MediaVisibility::Privat, $media->visibility);

        $client = User::factory()->klien()->create();
        $this->actingAs($client)->get(route('media.show', $media))->assertForbidden();
        $this->actingAs($client)->get(route('klien.katalog.venue.detail', $f['venue']))->assertNotFound();

        // F1: satu-satunya jalan publikasi adalah aksi persetujuan Admin.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post(route('admin.penyedia.verifikasi.approve', [
                'type' => 'venue_profile',
                'id' => $f['owner']->venueProfile->id,
            ]))
            ->assertRedirect(route('admin.penyedia.index'));

        $this->assertSame(MediaVisibility::Publik, $media->fresh()->visibility);
        $this->assertSame(VerificationStatus::Terverifikasi, $f['venue']->fresh()->verification_status);

        $this->actingAs($client)->get(route('media.show', $media))->assertOk();
        $this->actingAs($client)->get(route('klien.katalog.venue.detail', $f['venue']))
            ->assertOk()
            ->assertSee('Denah Aula Utama di '.$f['venue']->name);
    }

    public function test_denah_venue_nonaktif_tetap_privat_setelah_profil_disetujui(): void
    {
        $f = $this->venueFixture(VerificationStatus::Menunggu);
        $active = Venue::factory()->create([
            'venue_profile_id' => $f['owner']->venueProfile->id,
            'verification_status' => VerificationStatus::Menunggu,
        ]);
        $activeRoom = $active->rooms()->create(['name' => 'Ruang Aktif', 'capacity' => 100]);

        foreach ([$f['room'], $activeRoom] as $room) {
            $this->actingAs($f['owner'])->post(route('venue.ruangan.denah.store', $room), [
                'photo' => UploadedFile::fake()->image('denah-'.$room->id.'.png'),
            ])->assertRedirect();
        }

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post(route('admin.venue.update', $f['venue']), [
                'verification_status' => VerificationStatus::Nonaktif->value,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.penyedia.verifikasi.approve', [
                'type' => 'venue_profile',
                'id' => $f['owner']->venueProfile->id,
            ]))
            ->assertRedirect(route('admin.penyedia.index'));

        $nonaktif = Media::where('owner_id', $f['room']->id)->where('kind', MediaKind::Denah)->firstOrFail();
        $tetapAktif = Media::where('owner_id', $activeRoom->id)->where('kind', MediaKind::Denah)->firstOrFail();

        $this->assertSame(VerificationStatus::Nonaktif, $f['venue']->fresh()->verification_status);
        $this->assertSame(MediaVisibility::Privat, $nonaktif->visibility);
        $this->assertSame(MediaVisibility::Publik, $tetapAktif->visibility);
    }

    public function test_denah_penyedia_lain_tidak_ikut_publik(): void
    {
        $f = $this->venueFixture(VerificationStatus::Menunggu);
        $other = $this->venueFixture(VerificationStatus::Menunggu);

        foreach ([[$f['owner'], $f['room']], [$other['owner'], $other['room']]] as [$owner, $room]) {
            $this->actingAs($owner)->post(route('venue.ruangan.denah.store', $room), [
                'photo' => UploadedFile::fake()->image('denah-'.$room->id.'.png'),
            ])->assertRedirect();
        }

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.penyedia.verifikasi.approve', [
                'type' => 'venue_profile',
                'id' => $f['owner']->venueProfile->id,
            ]))
            ->assertRedirect(route('admin.penyedia.index'));

        $milikSendiri = Media::where('owner_id', $f['room']->id)->where('kind', MediaKind::Denah)->firstOrFail();
        $milikLain = Media::where('owner_id', $other['room']->id)->where('kind', MediaKind::Denah)->firstOrFail();

        $this->assertSame(MediaVisibility::Publik, $milikSendiri->visibility);
        $this->assertSame(MediaVisibility::Privat, $milikLain->visibility);
        $this->assertSame(VerificationStatus::Menunggu, $other['owner']->venueProfile->fresh()->verification_status);
    }

    public function test_persetujuan_dua_kali_tidak_menggandakan_efek(): void
    {
        $f = $this->venueFixture(VerificationStatus::Menunggu);

        $this->actingAs($f['owner'])->post(route('venue.ruangan.denah.store', $f['room']), [
            'photo' => UploadedFile::fake()->image('denah.png'),
        ])->assertRedirect();

        $admin = User::factory()->admin()->create();
        $url = route('admin.penyedia.verifikasi.approve', [
            'type' => 'venue_profile',
            'id' => $f['owner']->venueProfile->id,
        ]);

        $this->actingAs($admin)->post($url)->assertRedirect(route('admin.penyedia.index'));

        $publik = Media::where('kind', MediaKind::Denah)->firstOrFail();
        $this->assertSame(MediaVisibility::Publik, $publik->visibility);

        $this->actingAs($admin)->post($url)->assertSessionHasErrors('verification');

        $this->assertSame(1, Media::where('kind', MediaKind::Denah)->count());
        $this->assertSame(MediaVisibility::Publik, $publik->fresh()->visibility);
        $this->assertSame(1, AuditLog::where('action', 'verification_approved')->count());
    }

    public function test_penolakan_verifikasi_tidak_mengubah_visibilitas_foto_maupun_denah(): void
    {
        $f = $this->venueFixture(VerificationStatus::Menunggu);

        $this->actingAs($f['owner'])->post(route('venue.ruangan.denah.store', $f['room']), [
            'photo' => UploadedFile::fake()->image('denah.png'),
        ])->assertRedirect();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.penyedia.verifikasi.reject', [
                'type' => 'venue_profile',
                'id' => $f['owner']->venueProfile->id,
            ]), ['reason' => 'Dokumen belum lengkap.'])
            ->assertRedirect(route('admin.penyedia.index'));

        $media = Media::where('kind', MediaKind::Denah)->firstOrFail();

        $this->assertSame(VerificationStatus::Ditolak, $f['owner']->venueProfile->fresh()->verification_status);
        $this->assertSame(MediaVisibility::Privat, $media->visibility);
    }

    public function test_klien_mengubah_susunan_ruangan(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Draft,
        ]);

        $this->actingAs($client)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'meja_bundar',
            'room_layout_notes' => '10 meja bundar',
        ])->assertRedirect(route('klien.acara.detail', $event).'#susunan-ruangan');

        $event->refresh();
        $this->assertSame('meja_bundar', $event->room_layout->value);
        $this->assertSame('10 meja bundar', $event->room_layout_notes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'event_room_layout_updated', 'subject_id' => $event->id]);

        $event->update(['status' => EventStatus::Terkonfirmasi]);
        $this->actingAs($client)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'teater',
        ])->assertRedirect();

        $this->assertSame('teater', $event->fresh()->room_layout->value);
    }

    public function test_susunan_tidak_dikenal_dan_bukan_pemilik_ditolak(): void
    {
        $client = User::factory()->klien()->create();
        $other = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id, 'status' => EventStatus::Draft]);

        $this->actingAs($client)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'bebas_kreatif',
        ])->assertSessionHasErrors('room_layout');

        $this->assertNull($event->fresh()->room_layout);

        $this->actingAs($other)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'teater',
        ])->assertForbidden();
    }

    public function test_susunan_terkunci_setelah_selesai_dan_dibaca_venue_serta_admin(): void
    {
        $client = User::factory()->klien()->create();
        $venueUser = User::factory()->venue()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Selesai,
            'event_date' => now()->subDay()->format('Y-m-d'),
            'room_layout' => 'huruf_u',
            'room_layout_notes' => 'Panggung di tengah',
        ]);

        $this->actingAs($client)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'teater',
        ])->assertForbidden();

        $this->assertSame('huruf_u', $event->fresh()->room_layout->value);

        OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueUser->venueProfile->id,
            'status' => OrderItemStatus::Selesai,
        ]);

        $this->actingAs($venueUser)->get(route('venue.rundown.show', $event))
            ->assertOk()
            ->assertSee('Huruf U')
            ->assertSee('Panggung di tengah');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.acara.detail', $event))
            ->assertOk()
            ->assertSee('Huruf U')
            ->assertSee('Panggung di tengah');
    }

    public function test_susunan_ruangan_tidak_mengubah_data_uang(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id, 'status' => EventStatus::Draft]);

        $before = [
            'invoices' => Invoice::count(),
            'payments' => Payment::count(),
            'orders' => OrderItem::count(),
        ];

        $this->actingAs($client)->put(route('klien.acara.layout.update', $event), [
            'room_layout' => 'kelas',
        ]);

        $this->assertSame($before['invoices'], Invoice::count());
        $this->assertSame($before['payments'], Payment::count());
        $this->assertSame($before['orders'], OrderItem::count());
    }
}
