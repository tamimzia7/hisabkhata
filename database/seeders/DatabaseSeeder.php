<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The demo accounts created for every role.
     *
     * @var array<string, array{name: string, email: string}>
     */
    private const ACCOUNTS = [
        User::ROLE_SUPER_ADMIN => ['name' => 'Super Admin', 'email' => 'superadmin@hisabkhata.com'],
        User::ROLE_ADMIN => ['name' => 'Admin', 'email' => 'admin@hisabkhata.com'],
        User::ROLE_DEVELOPER => ['name' => 'Developer', 'email' => 'developer@hisabkhata.com'],
        User::ROLE_CUSTOMER_CARE => ['name' => 'Customer Care', 'email' => 'customercare@hisabkhata.com'],
        User::ROLE_STUDENT => ['name' => 'Student', 'email' => 'student@hisabkhata.com'],
        User::ROLE_FAMILY => ['name' => 'Family', 'email' => 'family@hisabkhata.com'],
        User::ROLE_SHOP => ['name' => 'Shop', 'email' => 'shop@hisabkhata.com'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(SubscriptionPlanSeeder::class);

        User::unguarded(function (): void {
            foreach (self::ACCOUNTS as $role => $account) {
                User::updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'email_verified_at' => now(),
                        'password' => '12345678',
                        'role' => $role,
                    ],
                );
            }
        });
    }
}
