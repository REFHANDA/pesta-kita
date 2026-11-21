<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Models\Cancellation;
use App\Models\Disbursement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap G2–G4: perbaikan tampilan panel Admin (tab status acara, padding
 * kartu rekening perusahaan, dan tabel Pencairan/Pembatalan). Tes ini
 * mengunci markup yang dipakai CSS nyata layout admin.
 */
class AdminUiLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // ---- G2: tab status Admin → Acara ----

    public function test_tab_status_acara_memakai_subnav_bukan_pill(): void
    {
        Event::factory()->create(['status' => EventStatus::Diajukan, 'event_date' => '2026-12-12']);

        $response = $this->actingAs($this->admin)->get(route('admin.acara'))->assertOk();

        $response->assertSee('class="tabs card subnav"', false);
        $response->assertDontSee('class="pill"', false);
        $response->assertDontSee('pill-row', false);
        $response->assertSee('aria-current="page"', false);
        // Hitungan per status dari satu query groupBy.
        $response->assertSee('<span class="n">1</span>', false);
    }

    public function test_tab_aktif_mengikuti_query_status_dan_evaluasi(): void
    {
        Event::factory()->create(['status' => EventStatus::Diajukan, 'event_date' => '2026-12-12']);

        $byStatus = $this->actingAs($this->admin)->get(route('admin.acara', ['status' => 'diajukan']))->assertOk();
        $byStatus->assertSee('aria-current="page" >Diajukan', false);
        $byStatus->assertDontSee('aria-current="page" >Semua', false);

        $evaluasi = $this->actingAs($this->admin)->get(route('admin.acara', ['tab' => 'evaluasi']))->assertOk();
        $evaluasi->assertSee('aria-current="page" >Evaluasi', false);
    }

    // ---- G3: kartu Rekening perusahaan ----

    public function test_kartu_rekening_perusahaan_memakai_kelas_halaman_dan_tanpa_gaya_inline(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.rekening.index'))->assertOk();

        $response->assertSee('<main class="content page-refined page-bank-accounts"', false);
        $response->assertDontSee('style="background:#dcfce7', false);
        $response->assertDontSee('style="margin-top:16px;display:flex;gap:8px;"', false);
    }

    // ---- G4: tabel Pencairan dan Pembatalan Admin ----

    /**
     * `<small>` polos di dalam konten halaman (di luar layout/topbar) harus
     * memakai kelas sub-t agar tampil sebagai blok redup, bukan menempel.
     */
    private function assertNoUnclassedSmall(string $html): void
    {
        $main = (string) substr($html, (int) strpos($html, '<main'));
        $this->assertStringNotContainsString('</b><small>', $main);
        $this->assertStringNotContainsString('</span><small>', $main);
        $this->assertSame(substr_count($main, '<small'), substr_count($main, '<small class='));
    }

    public function test_tabel_pencairan_dan_pembatalan_tanpa_small_tanpa_kelas(): void
    {
        Cancellation::factory()->create();
        Disbursement::factory()->create();

        $pencairan = $this->actingAs($this->admin)->get(route('admin.pencairan'))->assertOk();
        $pencairan->assertSee('<small class="sub-t">', false);
        $pencairan->assertSee('class="col-layanan"', false);
        $pencairan->assertSee('class="col-rekening"', false);
        $this->assertNoUnclassedSmall($pencairan->getContent());

        $pembatalan = $this->actingAs($this->admin)->get(route('admin.pembatalan'))->assertOk();
        $pembatalan->assertSee('<small class="sub-t">', false);
        $this->assertNoUnclassedSmall($pembatalan->getContent());
    }
}
