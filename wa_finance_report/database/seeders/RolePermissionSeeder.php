<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view admin dashboard',
            'manage users',
            'create users',
            'update users',
            'suspend users',
            'manage plans',
            'manage subscriptions',
            'view all accounts',
            'manage accounts',
            'view system activity logs',
            'view user dashboard',
            'manage financial accounts',
            'manage transaction categories',
            'manage transactions',
            'manage budgets',
            'view reports',
            'export reports',
            'manage profile',
            'manage account settings',
            'access API token settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $user = Role::findOrCreate('user', 'web');

        foreach (['account-owner', 'account-admin', 'account-member', 'account-viewer'] as $futureRole) {
            Role::findOrCreate($futureRole, 'web');
        }

        $superAdmin->syncPermissions(Permission::all());
        $user->syncPermissions([
            'view user dashboard',
            'manage financial accounts',
            'manage transaction categories',
            'manage transactions',
            'manage budgets',
            'view reports',
            'export reports',
            'manage profile',
            'manage account settings',
            'access API token settings',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
