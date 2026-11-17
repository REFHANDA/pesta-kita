<?php

namespace Tests\Feature;

use App\Enums\AvailabilityStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendations_apply_category_area_date_and_budget_filters(): void
    {
        $date = now()->addWeek()->toDateString();
        $matchingProfile = VendorProfile::factory()->terverifikasi()->create([
            'service_area' => 'Bandung',
        ]);
        $matchingItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $matchingProfile->id,
            'name' => 'Katering Bandung',
            'category' => 'Katering',
            'catalog_price' => 200_000,
        ]);
        $matchingItem->availabilityDays()->create([
            'date' => $date,
            'status' => AvailabilityStatus::Tersedia,
        ]);
        $bookedItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $matchingProfile->id,
            'name' => 'Katering Sudah Dipesan',
            'category' => 'Katering',
            'catalog_price' => 150_000,
        ]);
        $bookedItem->availabilityDays()->create([
            'date' => $date,
            'status' => AvailabilityStatus::Tersedia,
        ]);
        $bookedEvent = Event::factory()->create([
            'event_date' => $date,
            'status' => EventStatus::Terkonfirmasi,
        ]);
        $bookedEvent->orderItems()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $matchingProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $bookedItem->id,
            'catalog_price_snapshot' => 150_000,
            'status' => OrderItemStatus::Mengikat,
        ]);

        $otherProfile = VendorProfile::factory()->terverifikasi()->create([
            'service_area' => 'Jakarta',
        ]);
        $otherItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $otherProfile->id,
            'name' => 'Katering Jakarta',
            'category' => 'Katering',
            'catalog_price' => 300_000,
        ]);
        $otherItem->availabilityDays()->create([
            'date' => $date,
            'status' => AvailabilityStatus::Tersedia,
        ]);

        $this->actingAs(User::factory()->klien()->create())
            ->get('/klien/rekomendasi?category=Katering&area=Bandung&event_date='.$date.'&budget=220000')
            ->assertOk()
            ->assertSee('Katering Bandung')
            ->assertDontSee('Katering Sudah Dipesan')
            ->assertDontSee('Katering Jakarta');
    }

    public function test_recommendation_date_filter_requires_explicit_availability(): void
    {
        $profile = VendorProfile::factory()->terverifikasi()->create();
        CatalogItem::factory()->create([
            'vendor_profile_id' => $profile->id,
            'name' => 'Tanpa kalender',
        ]);

        $this->actingAs(User::factory()->klien()->create())
            ->get('/klien/rekomendasi?event_date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertDontSee('Tanpa kalender');
    }
}
