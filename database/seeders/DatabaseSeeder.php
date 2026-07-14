<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        // Create demo users for each role
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@shillings.test',
        ]);
        $superAdmin->assignRole('super-admin');

        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $owner->assignRole('owner');

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@shillings.test',
        ]);
        $admin->assignRole('admin');

        $accountant = User::factory()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@shillings.test',
        ]);
        $accountant->assignRole('accountant');

        $bookkeeper = User::factory()->create([
            'name' => 'Bookkeeper User',
            'email' => 'bookkeeper@shillings.test',
        ]);
        $bookkeeper->assignRole('bookkeeper');

        $viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@shillings.test',
        ]);
        $viewer->assignRole('viewer');

        // Demo client user
        $demo = User::factory()->create([
            'name' => 'Samone',
            'email' => 'samone@shillings.app',
            'password' => bcrypt('Shillings@2026!'),
        ]);
        $demo->assignRole('owner');
    }
}
