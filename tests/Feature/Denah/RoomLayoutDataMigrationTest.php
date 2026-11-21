<?php

namespace Tests\Feature\Denah;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationStatus;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F1: migrasi data denah — denah privat milik penyedia terverifikasi menjadi
 * publik, milik penyedia menunggu tetap privat, dan migrasi idempoten.
 */
class RoomLayoutDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function migration(): object
    {
        return require database_path('migrations/2026_11_21_000003_publish_verified_venue_room_layouts.php');
    }

    /**
     * @return array{profile: VenueProfile, venue: Venue, denahPath: string}
     */
    protected function venueWithDenah(VerificationStatus $profileStatus, VerificationStatus $venueStatus): array
    {
        $profile = VenueProfile::factory()->create(['verification_status' => $profileStatus]);

        $venue = Venue::factory()->create([
            'venue_profile_id' => $profile->id,
            'verification_status' => $venueStatus,
        ]);

        $room = $venue->rooms()->create(['name' => 'Aula Utama', 'capacity' => 300]);

        $denah = $room->media()->create([
            'kind' => MediaKind::Denah,
            'path' => 'media/venue_room/'.$room->id.'/denah/denah.png',
            'disk' => 'private',
            'mime_type' => 'image/png',
            'size' => 1024,
            'original_name' => 'denah.png',
            'visibility' => MediaVisibility::Privat,
            'sort_order' => 0,
        ]);

        return ['profile' => $profile, 'venue' => $venue, 'denahPath' => $denah->path];
    }

    public function test_migrasi_data_menaikkan_denah_penyedia_terverifikasi_dan_idempoten(): void
    {
        $terverifikasi = $this->venueWithDenah(VerificationStatus::Terverifikasi, VerificationStatus::Terverifikasi);
        $menunggu = $this->venueWithDenah(VerificationStatus::Menunggu, VerificationStatus::Menunggu);
        $nonaktif = $this->venueWithDenah(VerificationStatus::Terverifikasi, VerificationStatus::Nonaktif);

        // Media berjenis lain tidak boleh tersentuh.
        $foto = $terverifikasi['venue']->media()->create([
            'kind' => MediaKind::Foto,
            'path' => 'media/venue/'.$terverifikasi['venue']->id.'/foto.png',
            'disk' => 'private',
            'mime_type' => 'image/png',
            'size' => 1024,
            'original_name' => 'foto.png',
            'visibility' => MediaVisibility::Privat,
            'sort_order' => 0,
        ]);

        $this->migration()->up();

        $this->assertSame(
            'publik',
            DB::table('media')->where('path', $terverifikasi['denahPath'])->value('visibility')
        );
        $this->assertSame(
            'privat',
            DB::table('media')->where('path', $menunggu['denahPath'])->value('visibility')
        );
        $this->assertSame(
            'privat',
            DB::table('media')->where('path', $nonaktif['denahPath'])->value('visibility')
        );
        $this->assertSame('privat', DB::table('media')->where('path', $foto->path)->value('visibility'));

        // Tandai waktu lama: bila migrasi menulis ulang, updated_at berubah.
        DB::table('media')->update(['updated_at' => '2020-01-01 00:00:00']);

        $this->migration()->up();

        $this->assertSame(
            DB::table('media')->count(),
            DB::table('media')->where('updated_at', '2020-01-01 00:00:00')->count(),
            'Migrasi kedua tidak boleh menulis ulang baris media.'
        );
        $this->assertSame(
            'publik',
            DB::table('media')->where('path', $terverifikasi['denahPath'])->value('visibility')
        );
        $this->assertSame(
            'privat',
            DB::table('media')->where('path', $menunggu['denahPath'])->value('visibility')
        );
    }
}
