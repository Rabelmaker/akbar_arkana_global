<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles, permissions, and default users.
     */
    public function run(): void
    {
        Config::set('cache.default', 'array');
        Config::set('permission.cache.store', 'array');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $adminRole = Role::findOrCreate('Admin', 'web');
        $userRole = Role::findOrCreate('User', 'web');

        $superAdminRole->syncPermissions($permissions);
        $adminRole->syncPermissions([
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
        ]);
        $userRole->syncPermissions([
            'dashboard.view',
        ]);

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@officecms.test'],
            [
                'name' => 'SUPER ADMIN',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
            ],
        );
        $superAdmin->syncRoles([$superAdminRole]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@officecms.test'],
            [
                'name' => 'ADMIN',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
            ],
        );
        $admin->syncRoles([$adminRole]);

        $user = User::updateOrCreate(
            ['email' => 'user@officecms.test'],
            [
                'name' => 'USER',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
            ],
        );
        $user->syncRoles([$userRole]);

        collect([
            [
                'name' => 'Akbar Manager',
                'email' => 'manager@officecms.test',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
                'roles' => [$adminRole],
            ],
            [
                'name' => 'Rani Staff',
                'email' => 'staff1@officecms.test',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
                'roles' => [$userRole],
            ],
            [
                'name' => 'Budi Staff',
                'email' => 'staff2@officecms.test',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
                'roles' => [$userRole],
            ],
            [
                'name' => 'Nina Support',
                'email' => 'support@officecms.test',
                'password' => 'password',
                'email_verified_at' => null,
                'roles' => [$userRole],
            ],
            [
                'name' => 'Dewi Editor',
                'email' => 'editor@officecms.test',
                'password' => 'password',
                'email_verified_at' => Carbon::now(),
                'roles' => [$adminRole],
            ],
        ])->each(function (array $seedUser): void {
            $user = User::updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'name' => $seedUser['name'],
                    'password' => $seedUser['password'],
                    'email_verified_at' => $seedUser['email_verified_at'],
                ],
            );

            $user->syncRoles($seedUser['roles']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
