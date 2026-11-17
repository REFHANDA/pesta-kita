<?php

namespace Tests\Feature\Media;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloorPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_venue_owner_uploads_floor_plan_for_clients_only(): void
    {
        Storage::fake('private');
        $owner = User::factory()->venue()->create();
        $owner->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $venue = Venue::factory()->terverifikasi()->create([
            'venue_profile_id' => $owner->venueProfile->id,
        ]);

        $this->actingAs($owner)->post(route('venue.data.denah.store', $venue), [
            'file' => UploadedFile::fake()->create('denah.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $media = $venue->media()->firstOrFail();
        $this->assertSame(MediaKind::Denah, $media->kind);
        $this->assertSame(MediaVisibility::Publik, $media->visibility);
        Storage::disk('private')->assertExists($media->path);

        $this->actingAs(User::factory()->klien()->create())
            ->get(route('media.show', $media))
            ->assertOk();

        $this->actingAs(User::factory()->vendor()->create())
            ->get(route('media.show', $media))
            ->assertForbidden();
    }
}
