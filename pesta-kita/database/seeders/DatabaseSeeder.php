<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo accounts can only be seeded in local or testing environments.');
        }

        $demoPassword = 'password123';
        $accounts = [
            [
                'name' => 'Demo Klien',
                'email' => 'client@pestakita.test',
                'previous_email' => 'client.demo@pestakita.test',
                'phone' => '081200000001',
                'role' => 'client',
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'admin@pestakita.test',
                'previous_email' => 'admin.demo@pestakita.test',
                'phone' => '081200000002',
                'role' => 'admin',
            ],
            [
                'name' => 'Demo Vendor',
                'email' => 'vendor@pestakita.test',
                'previous_email' => 'vendor.demo@pestakita.test',
                'phone' => '081200000003',
                'role' => 'vendor',
                'business_name' => 'Pesta Kita Dekorasi',
                'category' => 'Dekorasi',
                'address' => 'Jakarta',
            ],
            [
                'name' => 'Demo Venue',
                'email' => 'venue@pestakita.test',
                'previous_email' => 'venue.demo@pestakita.test',
                'phone' => '081200000004',
                'role' => 'venue',
                'business_name' => 'Pesta Kita Venue',
                'category' => 'Venue',
                'address' => 'Bandung',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->where('email', $account['previous_email'])->first()
                ?? User::query()->where('email', $account['email'])->first()
                ?? new User;

            $user->fill([
                'name' => $account['name'],
                'email' => $account['email'],
                'phone' => $account['phone'],
                'role' => $account['role'],
                'password' => $demoPassword,
            ])->save();

            if (in_array($user->role, ['vendor', 'venue'], true)) {
                VendorProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'business_name' => $account['business_name'],
                        'category' => $account['category'],
                        'address' => $account['address'],
                    ],
                );
            }
        }
    }
}
