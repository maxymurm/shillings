<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Account permissions
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.delete',

            // Transaction permissions
            'transactions.view',
            'transactions.create',
            'transactions.update',
            'transactions.delete',
            'transactions.post',
            'transactions.void',
            'transactions.reconcile',

            // Report permissions
            'reports.view',
            'reports.export',

            // Company permissions
            'companies.view',
            'companies.update',
            'companies.manage-users',

            // Settings permissions
            'settings.view',
            'settings.update',

            // Currency permissions
            'currencies.view',
            'currencies.create',
            'currencies.update',

            // Account type permissions
            'account-types.view',
            'account-types.create',
            'account-types.update',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        // Owner - full access to everything
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo(Permission::all());

        // Admin - can manage most things except company ownership
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([
            'accounts.view', 'accounts.create', 'accounts.update', 'accounts.delete',
            'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete',
            'transactions.post', 'transactions.void', 'transactions.reconcile',
            'reports.view', 'reports.export',
            'companies.view', 'companies.update', 'companies.manage-users',
            'settings.view', 'settings.update',
            'currencies.view', 'currencies.create', 'currencies.update',
            'account-types.view', 'account-types.create', 'account-types.update',
        ]);

        // Accountant - can manage accounts and transactions, view reports
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountantRole->givePermissionTo([
            'accounts.view', 'accounts.create', 'accounts.update',
            'transactions.view', 'transactions.create', 'transactions.update',
            'transactions.post', 'transactions.reconcile',
            'reports.view', 'reports.export',
            'companies.view',
            'settings.view',
            'currencies.view',
            'account-types.view',
        ]);

        // Bookkeeper - can create and edit transactions, view accounts
        $bookkeeperRole = Role::firstOrCreate(['name' => 'bookkeeper', 'guard_name' => 'web']);
        $bookkeeperRole->givePermissionTo([
            'accounts.view',
            'transactions.view', 'transactions.create', 'transactions.update',
            'transactions.reconcile',
            'reports.view',
            'companies.view',
            'currencies.view',
            'account-types.view',
        ]);

        // Viewer - read-only access
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo([
            'accounts.view',
            'transactions.view',
            'reports.view',
            'companies.view',
            'currencies.view',
            'account-types.view',
        ]);
    }
}
