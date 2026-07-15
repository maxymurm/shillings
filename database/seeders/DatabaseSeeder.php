<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed core data
        $this->call([
            CurrencySeeder::class,
            AccountTypeSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        // Create demo users for each role (idempotent — safe to run multiple times)
        $this->seedUser('Super Admin', 'superadmin@shillings.test', null, 'super-admin');
        $this->seedUser('Test User', 'test@example.com', null, 'owner');
        $this->seedUser('Admin User', 'admin@shillings.test', null, 'admin');
        $this->seedUser('Accountant User', 'accountant@shillings.test', null, 'accountant');
        $this->seedUser('Bookkeeper User', 'bookkeeper@shillings.test', null, 'bookkeeper');
        $this->seedUser('Viewer User', 'viewer@shillings.test', null, 'viewer');

        // Demo client user
        $this->seedUser('Samone', 'samone@shillings.app', 'Shillings@2026!', 'owner');
    }

    /**
     * Create or update a user and ensure they have a company and role.
     * Idempotent — safe to run multiple times.
     */
    private function seedUser(string $name, string $email, ?string $password, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => bcrypt($password ?? 'password'),
                'remember_token' => Str::random(10),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        if ($user->companies()->count() === 0) {
            $company = Company::factory()->create();
            $user->companies()->attach($company->id, [
                'id' => (string) Str::uuid(),
                'role' => 'member',
            ]);
        }
    }
}
