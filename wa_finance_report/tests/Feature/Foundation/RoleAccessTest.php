<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('prevents a regular user from opening the admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows a super admin to open the admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful();
});

it('redirects super admins away from tenant-only pages', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('reports.index'))
        ->assertRedirect(route('admin.dashboard'));
});

it('excludes super admins from customer activation metrics', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $customer = User::factory()->create();
    $customer->assignRole('user');

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('metrics.users', 1)
            ->where('metrics.active_users', 1));
});

it('rejects the API user endpoint without a token', function () {
    $this->getJson('/api/v1/auth/user')
        ->assertUnauthorized()
        ->assertExactJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'errors' => [],
        ]);
});

it('returns a consistent envelope for an authenticated API user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/user')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email', 'phone', 'status'],
            'meta',
        ]);
});
