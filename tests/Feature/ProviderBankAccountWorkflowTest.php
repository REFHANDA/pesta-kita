<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\ProviderBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderBankAccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_payout_account_requires_admin_approval_and_notifies_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->vendor()->create();

        $this->actingAs($provider)->post('/vendor/rekening-pencairan', [
            'bank_name' => 'Bank Contoh',
            'account_number' => '1234567890',
            'account_name' => 'Nama Penyedia',
        ])->assertRedirect(route('vendor.bank-accounts'));

        $account = ProviderBankAccount::query()->firstOrFail();
        $this->assertSame(VerificationStatus::Menunggu, $account->verification_status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => 'user',
        ]);

        $this->actingAs($admin)->post("/admin/rekening-penyedia/{$account->id}", [
            'action' => 'approve',
        ])->assertRedirect(route('admin.provider-bank-accounts'));

        $this->assertSame(VerificationStatus::Terverifikasi, $account->fresh()->verification_status);
        $this->assertSame($admin->id, $account->fresh()->reviewed_by);

        $this->actingAs($provider)->get('/vendor/rekening-pencairan')
            ->assertOk()
            ->assertSee('1234567890');
    }

    public function test_provider_sees_only_its_own_payout_accounts(): void
    {
        $otherProvider = User::factory()->vendor()->create();
        ProviderBankAccount::query()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $otherProvider->vendorProfile->id,
            'bank_name' => 'Bank Contoh',
            'account_number' => '9876543210',
            'account_name' => 'Penyedia Lain',
            'verification_status' => VerificationStatus::Menunggu,
        ]);

        $this->actingAs(User::factory()->vendor()->create())
            ->get('/vendor/rekening-pencairan')
            ->assertOk()
            ->assertDontSee('9876543210');

        $this->assertDatabaseCount('provider_bank_accounts', 1);
    }
}
