<?php

namespace Database\Seeders;

use App\Actions\Accounts\CreateAccountOwner;
use App\Actions\Subscriptions\ProvisionDefaultTrial;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    public function __construct(private ProvisionDefaultTrial $provisionDefaultTrial) {}

    public function run(): void
    {
        $this->createSuperAdmin();
        $this->createDemoUser();
    }

    private function createSuperAdmin(): void
    {
        $password = config('development.super_admin.password');

        if (! is_string($password) || $password === '') {
            $this->command?->warn('SUPER_ADMIN_PASSWORD is empty; super admin seeding was skipped.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => config('development.super_admin.email')],
            [
                'name' => config('development.super_admin.name'),
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles('super-admin');
    }

    private function createDemoUser(): void
    {
        $password = config('development.demo_user.password');

        if (! is_string($password) || $password === '') {
            return;
        }

        $user = User::where('email', config('development.demo_user.email'))->first();

        if (! $user) {
            $user = app(CreateAccountOwner::class)->handle([
                'name' => config('development.demo_user.name'),
                'email' => config('development.demo_user.email'),
                'password' => $password,
                'email_verified_at' => now(),
            ]);
        }

        if ($user->activeAccount) {
            $this->provisionDefaultTrial->handle($user->activeAccount, $user);
        }
    }
}
