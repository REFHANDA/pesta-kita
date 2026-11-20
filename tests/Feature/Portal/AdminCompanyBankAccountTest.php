<?php

namespace Tests\Feature\Portal;

use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyBankAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_bank_account_list(): void
    {
        $admin = User::factory()->admin()->create();

        $bank1 = CompanyBankAccount::factory()->create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'PT Pesta Kita Indonesia',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $bank2 = CompanyBankAccount::factory()->create([
            'bank_name' => 'Bank Mandiri',
            'account_number' => '9876543210',
            'account_name' => 'PT Pesta Kita Indonesia',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.rekening.index'));

        $response->assertOk();
        $response->assertSee('BCA');
        $response->assertSee('1234567890');
        $response->assertSee('Bank Mandiri');
        $response->assertSee('9876543210');
        $response->assertSee('PT Pesta Kita Indonesia');
    }

    public function test_admin_can_create_company_bank_account(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'bank_name' => 'Bank BNI',
            'account_number' => '1122334455',
            'account_name' => 'PT Pesta Kita Indonesia',
            'sort_order' => 3,
            'is_active' => '1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.rekening.store'), $payload);

        $response->assertRedirect(route('admin.rekening.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_bank_accounts', [
            'bank_name' => 'Bank BNI',
            'account_number' => '1122334455',
            'account_name' => 'PT Pesta Kita Indonesia',
            'sort_order' => 3,
            'is_active' => 1,
        ]);

        $bank = CompanyBankAccount::where('account_number', '1122334455')->first();
        $this->assertNotNull($bank);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'company_bank_account_created',
            'subject_type' => 'company_bank_account',
            'subject_id' => $bank->id,
        ]);
    }

    public function test_admin_can_update_company_bank_account(): void
    {
        $admin = User::factory()->admin()->create();

        $bank = CompanyBankAccount::factory()->create([
            'bank_name' => 'BRI Lama',
            'account_number' => '5555444433',
            'account_name' => 'PT Pesta Kita',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.rekening.update', $bank), [
            'bank_name' => 'Bank BRI',
            'account_number' => '5555444433',
            'account_name' => 'PT Pesta Kita Digital',
            'sort_order' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.rekening.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_bank_accounts', [
            'id' => $bank->id,
            'bank_name' => 'Bank BRI',
            'account_name' => 'PT Pesta Kita Digital',
            'sort_order' => 5,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'company_bank_account_updated',
            'subject_type' => 'company_bank_account',
            'subject_id' => $bank->id,
        ]);
    }

    public function test_admin_can_toggle_company_bank_account_status(): void
    {
        $admin = User::factory()->admin()->create();

        $bank = CompanyBankAccount::factory()->create([
            'bank_name' => 'BCA',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.rekening.toggle', $bank));

        $response->assertRedirect(route('admin.rekening.index'));
        $this->assertFalse($bank->fresh()->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'company_bank_account_status_changed',
            'subject_type' => $bank->getMorphClass(),
            'subject_id' => $bank->id,
        ]);

        // Toggle back to active
        $this->actingAs($admin)->patch(route('admin.rekening.toggle', $bank));
        $this->assertTrue($bank->fresh()->is_active);
    }

    public function test_admin_can_delete_company_bank_account(): void
    {
        $admin = User::factory()->admin()->create();

        $bank = CompanyBankAccount::factory()->create([
            'bank_name' => 'Bank Sinarmas',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.rekening.destroy', $bank));

        $response->assertRedirect(route('admin.rekening.index'));
        $this->assertDatabaseMissing('company_bank_accounts', ['id' => $bank->id]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'company_bank_account_deleted',
            'subject_id' => $bank->id,
        ]);
    }

    public function test_inactive_bank_accounts_do_not_appear_on_client_invoice(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $klien->id]);
        $invoice = Invoice::factory()->create(['event_id' => $event->id]);

        CompanyBankAccount::factory()->create([
            'bank_name' => 'BCA Aktif',
            'account_number' => '111122223333',
            'is_active' => true,
        ]);

        CompanyBankAccount::factory()->create([
            'bank_name' => 'Bank Nonaktif',
            'account_number' => '888800007777',
            'is_active' => false,
        ]);

        $response = $this->actingAs($klien)->get(route('klien.tagihan.show', $invoice));

        $response->assertOk();
        $response->assertSee('BCA Aktif');
        $response->assertSee('111122223333');
        $response->assertDontSee('Bank Nonaktif');
        $response->assertDontSee('888800007777');
    }

    public function test_non_admin_cannot_access_bank_account_crud(): void
    {
        $klien = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($klien)->get(route('admin.rekening.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.rekening.index'))->assertForbidden();
    }
}
