<?php

use App\Models\Account;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('creates a primary account and owner membership for a registered user', function () {
    $this->post('/register', [
        'name' => 'Akbar Finance',
        'email' => 'akbar@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'akbar@example.com')->firstOrFail();
    $account = Account::where('created_by', $user->id)->firstOrFail();

    expect($user->hasRole('user'))->toBeTrue()
        ->and($user->active_account_id)->toBe($account->id)
        ->and($user->accounts()->whereKey($account->id)->exists())->toBeTrue()
        ->and((bool) $user->accounts()->firstOrFail()->pivot->is_owner)->toBeTrue()
        ->and($user->accounts()->firstOrFail()->pivot->role)->toBe('account-owner');
});
