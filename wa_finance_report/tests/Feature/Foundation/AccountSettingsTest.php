<?php

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function accountUser(Account $account, string $role = 'account-owner', bool $isOwner = true): User
{
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => $role,
        'is_owner' => $isOwner,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();

    return $user;
}

it('allows an owner to view and update the active account', function () {
    $account = Account::factory()->create();
    $owner = accountUser($account);

    $this->actingAs($owner)
        ->get(route('account.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Account')
            ->where('account.id', $account->id)
            ->where('account.name', $account->name)
            ->has('accounts', 1));

    $this->actingAs($owner)
        ->patch(route('account.settings.update'), [
            'name' => 'PT Laporan Keuangan',
            'email' => 'finance@example.com',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'currency' => 'IDR',
            'locale' => 'id',
        ])
        ->assertRedirect(route('account.settings.edit'));

    $account->refresh();

    expect($account->name)->toBe('PT Laporan Keuangan')
        ->and($account->timezone)->toBe('Asia/Makassar');

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'causer_id' => $owner->id,
        'event' => 'updated',
        'description' => 'Account profile updated',
    ]);
});

it('allows an account admin to update account settings', function () {
    $account = Account::factory()->create();
    $admin = accountUser($account, 'account-admin', false);

    $this->actingAs($admin)
        ->patch(route('account.settings.update'), [
            'name' => 'Account Dikelola Admin',
            'email' => null,
            'phone' => null,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
        ])
        ->assertRedirect(route('account.settings.edit'));

    expect($account->fresh()->name)->toBe('Account Dikelola Admin');
});

it('forbids a regular member from changing account settings', function () {
    $account = Account::factory()->create();
    $member = accountUser($account, 'account-member', false);

    $this->actingAs($member)
        ->get(route('account.settings.edit'))
        ->assertForbidden();

    $this->actingAs($member)
        ->patch(route('account.settings.update'), [
            'name' => 'Tidak Boleh Berubah',
            'email' => null,
            'phone' => null,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
        ])
        ->assertForbidden();

    expect($account->fresh()->name)->not->toBe('Tidak Boleh Berubah');
});

it('switches to another active account in the users membership', function () {
    $firstAccount = Account::factory()->create();
    $secondAccount = Account::factory()->create();
    $user = accountUser($firstAccount);
    $user->accounts()->attach($secondAccount, [
        'role' => 'account-member',
        'is_owner' => false,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->put(route('account.active.update', $secondAccount))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->active_account_id)->toBe($secondAccount->id);

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $secondAccount->id,
        'causer_id' => $user->id,
        'event' => 'active_account_switched',
    ]);
});

it('forbids switching to an account outside the users membership', function () {
    $ownedAccount = Account::factory()->create();
    $foreignAccount = Account::factory()->create();
    $user = accountUser($ownedAccount);

    $this->actingAs($user)
        ->put(route('account.active.update', $foreignAccount))
        ->assertForbidden();

    expect($user->fresh()->active_account_id)->toBe($ownedAccount->id);
});

it('forbids switching to a suspended account', function () {
    $activeAccount = Account::factory()->create();
    $suspendedAccount = Account::factory()->create(['status' => AccountStatus::Suspended]);
    $user = accountUser($activeAccount);
    $user->accounts()->attach($suspendedAccount, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->put(route('account.active.update', $suspendedAccount))
        ->assertForbidden();

    expect($user->fresh()->active_account_id)->toBe($activeAccount->id);
});

it('never updates a different account when saving active account settings', function () {
    $activeAccount = Account::factory()->create(['name' => 'Account A']);
    $otherAccount = Account::factory()->create(['name' => 'Account B']);
    $owner = accountUser($activeAccount);
    $owner->accounts()->attach($otherAccount, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->patch(route('account.settings.update'), [
            'name' => 'Account A Baru',
            'email' => null,
            'phone' => null,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
        ])
        ->assertRedirect(route('account.settings.edit'));

    expect($activeAccount->fresh()->name)->toBe('Account A Baru')
        ->and($otherAccount->fresh()->name)->toBe('Account B');
});
