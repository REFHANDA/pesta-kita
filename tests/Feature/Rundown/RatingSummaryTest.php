<?php

namespace Tests\Feature\Rundown;

use App\Enums\EventStatus;
use App\Models\Evaluation;
use App\Models\EvaluationItem;
use App\Models\Event;
use App\Models\User;
use App\Services\RatingSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rating hasil evaluasi (Tahap C5): rata-rata server, isolasi penyedia,
 * dan privasi nama klien.
 */
class RatingSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function ratedEvent(string $targetType, int $targetId, array $ratings, string $clientName = 'Klien Rahasia'): Event
    {
        $client = User::factory()->klien()->create(['name' => $clientName]);
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Dievaluasi,
            'event_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        $evaluation = Evaluation::factory()->create([
            'event_id' => $event->id,
            'system_feedback' => 'Masukan sistem rahasia',
        ]);

        foreach ($ratings as $rating) {
            EvaluationItem::factory()->create([
                'evaluation_id' => $evaluation->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'rating' => $rating,
                'comment' => 'Komentar '.$rating,
            ]);
        }

        return $event;
    }

    public function test_rata_rata_dan_pembulatan_dihitung_server(): void
    {
        $vendor = User::factory()->vendor()->create();
        $profileId = $vendor->vendorProfile->id;

        $this->ratedEvent('vendor', $profileId, [5, 4]);
        $this->ratedEvent('vendor', $profileId, [4]);

        $summary = app(RatingSummary::class)->forSubject('vendor', $profileId);

        // (5 + 4 + 4) / 3 = 4.333... -> 4.3
        $this->assertSame(4.3, $summary['average']);
        $this->assertSame(3, $summary['count']);
    }

    public function test_kosong_menghasilkan_null_dan_nol(): void
    {
        $summary = app(RatingSummary::class)->forSubject('vendor', 999);

        $this->assertNull($summary['average']);
        $this->assertSame(0, $summary['count']);
    }

    public function test_kartu_rating_admin_dan_tab_evaluasi(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->klien()->create(['name' => 'Klien Rahasia']);

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Dievaluasi,
            'event_date' => now()->subDays(2)->format('Y-m-d'),
        ]);
        $evaluation = Evaluation::factory()->create([
            'event_id' => $event->id,
            'system_feedback' => 'Masukan sistem rahasia',
        ]);
        EvaluationItem::factory()->create([
            'evaluation_id' => $evaluation->id,
            'target_type' => 'admin',
            'target_id' => null,
            'rating' => 4,
            'comment' => 'Pelayanan cepat',
        ]);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Rating Admin')
            ->assertSee('4,0')
            ->assertSee('1 penilaian');

        $this->actingAs($admin)->get(route('admin.acara', ['tab' => 'evaluasi']))
            ->assertOk()
            ->assertSee('Acara #'.$event->id);

        $this->actingAs($admin)->get(route('admin.acara.detail', $event))
            ->assertOk()
            ->assertSee('Pelayanan cepat')
            ->assertSee('Masukan sistem rahasia')
            ->assertSee('Klien Rahasia');
    }

    public function test_penyedia_melihat_rating_sendiri_tanpa_nama_klien_dan_tanpa_masukan_sistem(): void
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();

        $this->ratedEvent('vendor', $vendor->vendorProfile->id, [5], 'Klien Rahasia');
        $this->ratedEvent('vendor', $otherVendor->vendorProfile->id, [1], 'Klien Lain');

        $response = $this->actingAs($vendor)->get('/vendor')
            ->assertOk()
            ->assertSee('Rating vendor')
            ->assertSee('5,0')
            ->assertSee('Komentar 5')
            ->assertDontSee('Klien Rahasia')
            ->assertDontSee('Klien Lain')
            ->assertDontSee('Masukan sistem rahasia')
            ->assertDontSee('Komentar 1');
    }

    public function test_venue_melihat_rating_sendiri_saja(): void
    {
        $venue = User::factory()->venue()->create();

        $this->ratedEvent('venue', $venue->venueProfile->id, [3], 'Klien Rahasia');

        $this->actingAs($venue)->get('/venue')
            ->assertOk()
            ->assertSee('Rating venue')
            ->assertSee('3,0')
            ->assertSee('Komentar 3')
            ->assertDontSee('Klien Rahasia')
            ->assertDontSee('Masukan sistem rahasia');
    }

    public function test_empty_state_rating_saat_belum_ada_penilaian(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Belum ada penilaian untuk Admin.');
        $this->actingAs($vendor)->get('/vendor')->assertOk()->assertSee('Belum ada penilaian');
        $this->actingAs($venue)->get('/venue')->assertOk()->assertSee('Belum ada penilaian');
    }
}
