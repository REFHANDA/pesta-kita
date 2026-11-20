<?php

namespace Tests\Feature\Database;

use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueFacility;
use App\Models\VenueRoom;
use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-2 instruksi §7/§11: local demo seeder tetap local-only, idempoten,
 * dan menghasilkan data domain yang saling terhubung tanpa menggandakan
 * data demo BE-1.
 */
class LocalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_skipped_outside_local_environment(): void
    {
        $this->assertTrue(app()->environment('testing'));

        $this->seed(LocalDemoSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, CatalogItem::count());
    }

    public function test_seeder_creates_interconnected_demo_data_in_local(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->seed(LocalDemoSeeder::class);

        // Akun demo BE-1 tetap empat, tidak bertambah.
        $this->assertSame(4, User::count());
        $this->assertSame(2, CatalogItem::count());
        $this->assertSame(1, Venue::count());
        $this->assertSame(2, VenueRoom::count());
        $this->assertSame(4, VenueFacility::count());
        $this->assertSame(3, AvailabilityDay::count());
        $this->assertSame(1, Event::count());
        $this->assertSame(3, OrderItem::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->seed(LocalDemoSeeder::class);
        $before = $this->domainCounts();

        $this->seed(LocalDemoSeeder::class);

        $this->assertSame($before, $this->domainCounts());
    }

    /**
     * Jumlah record domain untuk membandingkan idempotensi.
     *
     * @return array<string, int>
     */
    private function domainCounts(): array
    {
        return [
            'users' => User::count(),
            'catalog_items' => CatalogItem::count(),
            'venues' => Venue::count(),
            'venue_rooms' => VenueRoom::count(),
            'venue_facilities' => VenueFacility::count(),
            'availability_days' => AvailabilityDay::count(),
            'events' => Event::count(),
            'order_items' => OrderItem::count(),
            'settings' => Setting::count(),
        ];
    }
}
