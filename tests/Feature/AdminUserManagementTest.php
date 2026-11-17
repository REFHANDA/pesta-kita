<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_account_status_without_changing_role(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->klien()->create();

        $this->actingAs($admin)->post("/admin/pengguna/{$client->id}/status", [
            'status' => UserStatus::Nonaktif->value,
        ])->assertRedirect(route('admin.users.detail', $client));

        $this->assertSame(UserStatus::Nonaktif, $client->fresh()->status);
        $this->assertSame(Role::Klien, $client->fresh()->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_account_status_changed',
            'subject_id' => $client->id,
            'to_status' => UserStatus::Nonaktif->value,
        ]);
    }

    public function test_admin_cannot_deactivate_its_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/pengguna/{$admin->id}/status", [
            'status' => UserStatus::Nonaktif->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(UserStatus::Aktif, $admin->fresh()->status);
    }
}
