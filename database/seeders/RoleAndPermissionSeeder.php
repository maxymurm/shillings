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

            // Contact permissions
            'contacts.view',
            'contacts.create',
            'contacts.update',
            'contacts.delete',

            // Document / Invoice permissions
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.send',
            'documents.approve',

            // Budget permissions
            'budgets.view',
            'budgets.create',
            'budgets.update',
            'budgets.delete',

            // Tax permissions
            'taxes.view',
            'taxes.create',
            'taxes.update',
            'taxes.delete',

            // Scheduled transaction permissions
            'scheduled-transactions.view',
            'scheduled-transactions.create',
            'scheduled-transactions.update',
            'scheduled-transactions.delete',

            // Banking / import permissions
            'banking.view',
            'banking.import',
            'banking.match',

            // User management permissions
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Audit log permissions
            'audit.view',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin - unrestricted access, can manage system-level settings
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // Owner - full access to their company
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->syncPermissions(Permission::all());

        // Admin - can manage most things except company ownership transfer
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'accounts.view', 'accounts.create', 'accounts.update', 'accounts.delete',
            'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete',
            'transactions.post', 'transactions.void', 'transactions.reconcile',
            'reports.view', 'reports.export',
            'companies.view', 'companies.update', 'companies.manage-users',
            'settings.view', 'settings.update',
            'currencies.view', 'currencies.create', 'currencies.update',
            'account-types.view', 'account-types.create', 'account-types.update',
            'contacts.view', 'contacts.create', 'contacts.update', 'contacts.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'documents.send', 'documents.approve',
            'budgets.view', 'budgets.create', 'budgets.update', 'budgets.delete',
            'taxes.view', 'taxes.create', 'taxes.update', 'taxes.delete',
            'scheduled-transactions.view', 'scheduled-transactions.create',
            'scheduled-transactions.update', 'scheduled-transactions.delete',
            'banking.view', 'banking.import', 'banking.match',
            'users.view', 'users.create', 'users.update',
            'audit.view',
        ]);

        // Accountant - can manage accounts, transactions, reports, documents, taxes
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountantRole->syncPermissions([
            'accounts.view', 'accounts.create', 'accounts.update',
            'transactions.view', 'transactions.create', 'transactions.update',
            'transactions.post', 'transactions.reconcile',
            'reports.view', 'reports.export',
            'companies.view',
            'settings.view',
            'currencies.view',
            'account-types.view',
            'contacts.view', 'contacts.create', 'contacts.update',
            'documents.view', 'documents.create', 'documents.update',
            'documents.send', 'documents.approve',
            'budgets.view', 'budgets.create', 'budgets.update',
            'taxes.view', 'taxes.create', 'taxes.update',
            'scheduled-transactions.view', 'scheduled-transactions.create',
            'scheduled-transactions.update',
            'banking.view', 'banking.import', 'banking.match',
            'audit.view',
        ]);

        // Bookkeeper - data entry, reconciliation, basic document handling
        $bookkeeperRole = Role::firstOrCreate(['name' => 'bookkeeper', 'guard_name' => 'web']);
        $bookkeeperRole->syncPermissions([
            'accounts.view',
            'transactions.view', 'transactions.create', 'transactions.update',
            'transactions.reconcile',
            'reports.view',
            'companies.view',
            'currencies.view',
            'account-types.view',
            'contacts.view', 'contacts.create',
            'documents.view', 'documents.create', 'documents.update',
            'budgets.view',
            'taxes.view',
            'scheduled-transactions.view',
            'banking.view', 'banking.import', 'banking.match',
        ]);

        // Viewer - read-only access across the system
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions([
            'accounts.view',
            'transactions.view',
            'reports.view',
            'companies.view',
            'currencies.view',
            'account-types.view',
            'contacts.view',
            'documents.view',
            'budgets.view',
            'taxes.view',
            'scheduled-transactions.view',
            'banking.view',
            'audit.view',
        ]);
    }
}
