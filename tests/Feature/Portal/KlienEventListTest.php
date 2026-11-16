<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring read-only daftar acara Klien: halaman `acara` dan `riwayat` membaca
 * tabel `events` milik akun login (sumber data yang sama dengan beranda).
 * Kartu/baris contoh prototipe tidak boleh muncul lagi, dan acara klien lain
 * tidak boleh bocor.
 */
class KlienEventListTest extends TestCase
{
    use RefreshDatabase;

    public function test_acara_page_lists_own_upcoming_events_only(): void
    {
        $user = User::factory()->klien()->create();

        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Arisan Kantor Berjalan',
            'status' => EventStatus::MenungguPembayaran,
            'guest_count' => 77,
        ]);
        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Ulang Tahun Tersusun Draft',
            'status' => EventStatus::Draft,
        ]);
        // Bukan agenda mendatang -> tidak boleh tampil di halaman ini.
        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Piknik Yang Sudah Selesai',
            'status' => EventStatus::Selesai,
        ]);
        // Milik klien lain -> tidak boleh tampil.
        Event::factory()->create(['name' => 'Acara Klien Lain Rahasia']);

        $this->actingAs($user)->get('/klien/acara')
            ->assertOk()
            ->assertSee('Arisan Kantor Berjalan')
            ->assertSee('Menunggu pembayaran')
            ->assertSee('77 tamu')
            ->assertSee('Ulang Tahun Tersusun Draft')
            ->assertDontSee('Piknik Yang Sudah Selesai')
            ->assertDontSee('Acara Klien Lain Rahasia')
            ->assertDontSee('Ulang Tahun ke-7 Nadia')
            ->assertDontSee('Syukuran Opa 70 Tahun')
            ->assertDontSee('3 pesanan');
    }

    public function test_riwayat_page_lists_own_finished_events_only(): void
    {
        $user = User::factory()->klien()->create();

        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Wedding Sudah Dinilai',
            'status' => EventStatus::Dinilai,
        ]);
        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Seminar Batal Dibatalkan',
            'status' => EventStatus::Dibatalkan,
        ]);
        Event::factory()->create([
            'client_id' => $user->id,
            'name' => 'Undangan Masih Draft',
            'status' => EventStatus::Draft,
        ]);
        Event::factory()->create([
            'name' => 'Riwayat Klien Lain Rahasia',
            'status' => EventStatus::Selesai,
        ]);

        $this->actingAs($user)->get('/klien/riwayat')
            ->assertOk()
            ->assertSee('Wedding Sudah Dinilai')
            ->assertSee('Dinilai')
            ->assertSee('Seminar Batal Dibatalkan')
            ->assertSee('Dibatalkan')
            ->assertDontSee('Undangan Masih Draft')
            ->assertDontSee('Riwayat Klien Lain Rahasia')
            ->assertDontSee('Ulang Tahun Kayla')
            ->assertDontSee('Syukuran Mama')
            ->assertDontSee('Beri penilaian');
    }

    public function test_both_lists_render_empty_state_without_fake_events(): void
    {
        $user = User::factory()->klien()->create();

        $this->actingAs($user)->get('/klien/acara')
            ->assertOk()
            ->assertSee('Belum ada acara mendatang tercatat untuk akun Anda.');

        $this->actingAs($user)->get('/klien/riwayat')
            ->assertOk()
            ->assertSee('Belum ada acara yang selesai, dinilai, atau dibatalkan.');
    }

    public function test_event_lists_are_role_guarded(): void
    {
        foreach (['/klien/acara', '/klien/riwayat'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $vendor = User::factory()->vendor()->create();
        $this->actingAs($vendor)->get('/klien/acara')->assertForbidden();
        $this->actingAs($vendor)->get('/klien/riwayat')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/klien/acara')->assertForbidden();
    }
}
