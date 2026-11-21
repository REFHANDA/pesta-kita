<?php

namespace Tests\Feature\Simplification;

use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol "Tanya Admin" lewat WhatsApp: hanya untuk Klien, nomor dari
 * pengaturan, pesan kontekstual dibangun server, tanpa kontak penyedia.
 */
class WhatsappHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function waLink(string $html): string
    {
        preg_match('/href="(https:\/\/wa\.me\/[^"]+)"/', $html, $m);
        $this->assertNotEmpty($m, 'Tautan WhatsApp tidak ditemukan.');

        return html_entity_decode($m[1]);
    }

    protected function waText(string $html): string
    {
        parse_str((string) parse_url($this->waLink($html), PHP_URL_QUERY), $query);

        return (string) $query['text'];
    }

    public function test_tombol_hanya_tampil_di_portal_klien(): void
    {
        Setting::set('admin_whatsapp', '6281200000000');

        $klien = User::factory()->klien()->create(['name' => 'Sari Klien']);
        $this->actingAs($klien)->get('/klien')
            ->assertOk()
            ->assertSee('data-whatsapp-help', false)
            ->assertSee('aria-label="Tanya Admin lewat WhatsApp"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('Tanya Admin Pesta Kita. Semua pertanyaan tentang vendor dan venue lewat Admin.');

        foreach (['vendor', 'venue', 'admin'] as $role) {
            $this->actingAs(User::factory()->{$role}()->create())->get("/{$role}")
                ->assertOk()
                ->assertDontSee('data-whatsapp-help', false)
                ->assertDontSee('wa.me');
        }
    }

    public function test_tombol_disembunyikan_bila_nomor_kosong(): void
    {
        Setting::set('admin_whatsapp', '');

        $this->actingAs(User::factory()->klien()->create())->get('/klien')
            ->assertOk()
            ->assertDontSee('data-whatsapp-help', false);
    }

    public function test_pesan_umum_dan_penutup(): void
    {
        Setting::set('admin_whatsapp', '6281200000000');
        $klien = User::factory()->klien()->create(['name' => 'Sari Klien']);

        $html = $this->actingAs($klien)->get('/klien')->getContent();

        $this->assertStringStartsWith('https://wa.me/6281200000000?text=', $this->waLink($html));
        $this->assertSame(
            'Halo Admin Pesta Kita, saya Sari Klien. Saya ingin bertanya. Keputusan resmi (pesanan, pembayaran, pembatalan) hanya melalui aplikasi.',
            $this->waText($html)
        );
    }

    public function test_pesan_di_detail_acara_menyebut_id_dan_nama_acara(): void
    {
        Setting::set('admin_whatsapp', '6281200000000');
        $klien = User::factory()->klien()->create(['name' => 'Sari Klien']);
        $event = Event::factory()->create(['client_id' => $klien->id, 'name' => 'Ultah Bima & Kawan']);

        $text = $this->waText($this->actingAs($klien)->get('/klien/acara-detail/'.$event->id)->assertOk()->getContent());

        $this->assertStringContainsString("Acara #{$event->id} (Ultah Bima & Kawan).", $text);
        $this->assertStringEndsWith('Keputusan resmi (pesanan, pembayaran, pembatalan) hanya melalui aplikasi.', $text);
    }

    public function test_pesan_di_detail_katalog_tanpa_kontak_penyedia(): void
    {
        Setting::set('admin_whatsapp', '6281200000000');
        $vendor = User::factory()->vendor()->create(['phone' => '081299998888']);
        $vendor->vendorProfile->update(['business_name' => 'Dapur Uji', 'verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id, 'name' => 'Paket Prasmanan Uji']);

        $response = $this->actingAs(User::factory()->klien()->create())
            ->get('/klien/katalog-vendor-detail/'.$item->id)
            ->assertOk()
            ->assertDontSee('081299998888')
            ->assertDontSee($vendor->email);

        $this->assertStringContainsString(
            'Saya ingin bertanya tentang Paket Prasmanan Uji dari Dapur Uji.',
            $this->waText($response->getContent())
        );
        $this->assertStringStartsWith('https://wa.me/6281200000000?', $this->waLink($response->getContent()));
    }

    public function test_normalisasi_nomor_whatsapp(): void
    {
        $this->assertSame('6281234567890', Setting::normalizeWhatsapp('0812-3456-7890'));
        $this->assertSame('6281234567890', Setting::normalizeWhatsapp('+62 812 3456 7890'));
        $this->assertSame('6281234567890', Setting::normalizeWhatsapp('(62) 812.3456.7890'));
        $this->assertSame('', Setting::normalizeWhatsapp(null));
        $this->assertMatchesRegularExpression(Setting::ADMIN_WHATSAPP_PATTERN, Setting::normalizeWhatsapp('0812-3456-7890'));
        $this->assertDoesNotMatchRegularExpression(Setting::ADMIN_WHATSAPP_PATTERN, Setting::normalizeWhatsapp('12345'));
    }
}
