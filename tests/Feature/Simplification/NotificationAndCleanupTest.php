<?php

namespace Tests\Feature\Simplification;

use App\Enums\CancellationStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Cancellation;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderItemReviewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Notifikasi nyata per pengguna, lencana belum dibaca, privasi penyedia,
 * serta pembersihan data contoh/prototipe dari UI.
 */
class NotificationAndCleanupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function notifyRaw(User $user, array $data, bool $read = false): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\InvoiceIssuedNotification',
            'data' => $data,
            'read_at' => $read ? now() : null,
        ]);

        return $id;
    }

    public function test_halaman_notifikasi_membaca_data_nyata_milik_sendiri(): void
    {
        foreach (['klien', 'vendor', 'venue', 'admin'] as $role) {
            $user = User::factory()->{$role}()->create();
            $other = User::factory()->{$role}()->create();
            $this->notifyRaw($user, ['message' => "Pesan untuk {$role} sendiri"]);
            $this->notifyRaw($other, ['message' => 'Pesan rahasia orang lain']);

            $this->actingAs($user)->get("/{$role}/notifikasi")
                ->assertOk()
                ->assertSee("Pesan untuk {$role} sendiri")
                ->assertSee('Tagihan terbit')
                ->assertDontSee('Pesan rahasia orang lain')
                ->assertDontSee('Nadia');
        }
    }

    public function test_empty_state_tanpa_notifikasi(): void
    {
        $this->actingAs(User::factory()->vendor()->create())->get('/vendor/notifikasi')
            ->assertOk()
            ->assertSee('Belum ada notifikasi.')
            ->assertDontSee('Tandai semua sudah dibaca');
    }

    public function test_membuka_notifikasi_menandai_dibaca_dan_redirect_ke_url(): void
    {
        $user = User::factory()->klien()->create();
        $id = $this->notifyRaw($user, ['message' => 'Halo', 'url' => '/klien/acara']);

        $this->actingAs($user)->get(route('klien.notifikasi.buka', $id))->assertRedirect('/klien/acara');
        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    public function test_tanpa_url_diarahkan_dari_id_payload(): void
    {
        $vendor = User::factory()->vendor()->create();
        $id = $this->notifyRaw($vendor, ['message' => 'Pesanan', 'order_item_id' => 77]);

        $this->actingAs($vendor)->get(route('vendor.notifikasi.buka', $id))
            ->assertRedirect(route('vendor.pesanan.show', 77));
    }

    public function test_notifikasi_orang_lain_tidak_dapat_dibuka(): void
    {
        $owner = User::factory()->klien()->create();
        $intruder = User::factory()->klien()->create();
        $id = $this->notifyRaw($owner, ['message' => 'Rahasia']);

        $this->actingAs($intruder)->get(route('klien.notifikasi.buka', $id))->assertNotFound();
        $this->assertNull($owner->notifications()->find($id)->read_at);
    }

    public function test_lencana_belum_dibaca_dan_tandai_semua(): void
    {
        $user = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->notifyRaw($user, ['message' => 'Satu']);
        $this->notifyRaw($user, ['message' => 'Dua']);
        $this->notifyRaw($user, ['message' => 'Lama'], read: true);
        $this->notifyRaw($other, ['message' => 'Punya admin lain']);

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee('data-unread-count="2"', false)
            ->assertSee('aria-label="Notifikasi, 2 belum dibaca"', false);

        $this->actingAs($user)->post(route('admin.notifikasi.baca-semua'))
            ->assertRedirect(route('admin.notifikasi'));

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->actingAs($user)->get('/admin')->assertDontSee('data-unread-count', false);
    }

    public function test_notifikasi_penyedia_tanpa_nama_klien_dan_menyebut_acara_id(): void
    {
        $client = User::factory()->klien()->create(['name' => 'Klien Rahasia Sekali', 'phone' => '081277776666']);
        $event = Event::factory()->create(['client_id' => $client->id, 'name' => 'Pesta Pribadi Rahasia']);
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id, 'name' => 'Paket Uji']);
        $orderItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'status' => OrderItemStatus::Disetujui,
        ]);

        foreach ([new OrderItemReviewedNotification($orderItem, 'approved'), new OrderConfirmedNotification($orderItem)] as $notification) {
            $payload = json_encode($notification->toArray($vendor));

            $this->assertStringContainsString("Acara #{$event->id}", $payload);
            $this->assertStringNotContainsString('Klien Rahasia Sekali', $payload);
            $this->assertStringNotContainsString('Pesta Pribadi Rahasia', $payload);
            $this->assertStringNotContainsString('081277776666', $payload);
            $this->assertStringNotContainsString($client->email, $payload);
        }
    }

    public function test_pendapatan_perusahaan_dari_tagihan_lunas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Rp 0');

        Invoice::factory()->lunas()->create(['total_commission' => 1_000_000, 'cleaning_fee' => 250_000]);
        Invoice::factory()->lunas()->create(['total_commission' => 500_000, 'cleaning_fee' => 0]);
        Invoice::factory()->create(['total_commission' => 9_000_000, 'cleaning_fee' => 900_000]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Rp 1.750.000');

        // Tahap B10: tagihan lunas milik acara dibatalkan tidak dihitung; diganti company_income refund selesai.
        $cancelled = Invoice::factory()->lunas()->create(['total_commission' => 3_000_000, 'cleaning_fee' => 1_500_000]);
        $cancelled->event->update(['status' => EventStatus::Dibatalkan]);
        Cancellation::factory()->create([
            'event_id' => $cancelled->event_id,
            'invoice_id' => $cancelled->id,
            'status' => CancellationStatus::RefundSelesai,
            'company_income' => 1_350_000,
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Rp 3.100.000')->assertDontSee('Rp 6.250.000');
    }

    public function test_tidak_ada_string_prototipe_atau_data_contoh_di_view(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/BE-\d|prototipe|belum terhubung|data-note|PK-026|Nadia/i',
                file_get_contents($file->getPathname()),
                "String prototipe/data contoh masih ada di {$file->getPathname()}"
            );
        }
    }

    public function test_halaman_tahap_berikutnya_jujur_tanpa_nilai_palsu(): void
    {
        $pages = [
            'admin' => ['/admin/pembatalan', '/admin/pencairan', '/admin/acara'],
            'vendor' => ['/vendor/pencairan'],
            'venue' => ['/venue/pencairan'],
        ];

        foreach ($pages as $role => $paths) {
            $user = User::factory()->{$role}()->create();

            foreach ($paths as $path) {
                $response = $this->actingAs($user)->get($path)->assertOk()->assertSee('Belum ada data.');

                $this->assertDoesNotMatchRegularExpression('/Sound system|Graha Melati|Dapur Nusantara|onsubmit="return false"/i', $response->getContent(), $path);
            }
        }
    }

    public function test_rundown_venue_hanya_baca(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'venue/rundown')) {
                $this->assertSame(['GET', 'HEAD'], $route->methods(), "Route tulis rundown venue: {$route->uri()}");
            }
        }

        $html = $this->actingAs(User::factory()->venue()->create())->get('/venue/rundown')
            ->assertOk()
            ->assertDontSee('Tambah item rundown')
            ->getContent();

        // Satu-satunya form adalah tombol Keluar di menu akun.
        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertStringContainsString('action="'.url('/logout').'"', $html);
    }

    public function test_tombol_tambah_akun_penyedia_dihapus(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/pengguna')
            ->assertOk()
            ->assertDontSee('Tambah akun penyedia')
            ->assertDontSee('pengguna-form');

        $this->assertFalse(Route::has('admin.pengguna.create'));
        $this->assertFalse(Route::has('admin.pengguna.store'));
    }
}
