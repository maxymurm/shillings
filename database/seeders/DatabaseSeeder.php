<?php

namespace Database\Seeders;

use App\Models\Company;
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
        $this->attachUserToCompany($superAdmin);

        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $owner->assignRole('owner');
        $this->attachUserToCompany($owner);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@shillings.test',
        ]);
        $admin->assignRole('admin');
        $this->attachUserToCompany($admin);

        $accountant = User::factory()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@shillings.test',
        ]);
        $accountant->assignRole('accountant');
        $this->attachUserToCompany($accountant);

        $bookkeeper = User::factory()->create([
            'name' => 'Bookkeeper User',
            'email' => 'bookkeeper@shillings.test',
        ]);
        $bookkeeper->assignRole('bookkeeper');
        $this->attachUserToCompany($bookkeeper);

        $viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@shillings.test',
        ]);
        $viewer->assignRole('viewer');
        $this->attachUserToCompany($viewer);

        // Demo client user
        $demo = User::factory()->create([
            'name' => 'Samone',
            'email' => 'samone@shillings.app',
            'password' => bcrypt('Shillings@2026!'),
        ]);
        $demo->assignRole('owner');
        $this->attachUserToCompany($demo);
    }

    /**
     * Attach a user to a company via the many-to-many relationship.
     */
    private function attachUserToCompany(User $user): void
    {
        $company = Company::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'member']);
    }
}
