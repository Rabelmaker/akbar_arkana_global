<?php

use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function financialAccountOwner(Account $account, bool $withSubscription = true): User
{
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();

    if ($withSubscription) {
        Subscription::factory()->for($account)->create();
    }

    return $user;
}

function financialAccountPayload(array $overrides = []): array
{
    return [
        'name' => 'Bank Operasional',
        'type' => 'bank',
        'institution_name' => 'Bank Nusantara',
        'account_number' => '1234567890',
        'account_holder_name' => 'PT Demo',
        'opening_balance' => 1500000,
        'color' => '#7367F0',
        'icon' => 'ri-bank-line',
        'is_active' => true,
        'include_in_total' => true,
        'notes' => 'Rekening operasional utama.',
        ...$overrides,
    ];
}

it('lists only financial accounts from the active tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = financialAccountOwner($account);
    FinancialAccount::factory()->for($account)->create(['name' => 'Kas Milik Saya']);
    FinancialAccount::factory()->for($otherAccount)->create(['name' => 'Kas Tenant Lain']);

    $this->actingAs($owner)
        ->get(route('financial-accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/FinancialAccounts/Index')
            ->has('financialAccounts', 1)
            ->where('financialAccounts.0.name', 'Kas Milik Saya'));
});

it('creates an account with matching opening and current balances', function () {
    $account = Account::factory()->create();
    $owner = financialAccountOwner($account);

    $this->actingAs($owner)
        ->post(route('financial-accounts.store'), financialAccountPayload())
        ->assertRedirect();

    $financialAccount = FinancialAccount::query()->where('account_id', $account->id)->firstOrFail();

    expect($financialAccount->opening_balance)->toBe('1500000.00')
        ->and($financialAccount->current_balance)->toBe('1500000.00')
        ->and($financialAccount->currency)->toBe('IDR');

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'causer_id' => $owner->id,
        'event' => 'created',
        'description' => 'Financial account created',
    ]);
});

it('adjusts current balance only by the opening balance difference', function () {
    $account = Account::factory()->create();
    $owner = financialAccountOwner($account);
    $financialAccount = FinancialAccount::factory()->for($account)->create([
        'opening_balance' => 1000000,
        'current_balance' => 1250000,
    ]);

    $this->actingAs($owner)
        ->patch(route('financial-accounts.update', $financialAccount), financialAccountPayload([
            'name' => $financialAccount->name,
            'opening_balance' => 1200000,
        ]))
        ->assertRedirect();

    expect($financialAccount->fresh()->current_balance)->toBe('1450000.00');
});

it('forbids modifying a financial account from another tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = financialAccountOwner($account);
    $foreignFinancialAccount = FinancialAccount::factory()->for($otherAccount)->create();

    $this->actingAs($owner)
        ->patch(route('financial-accounts.update', $foreignFinancialAccount), financialAccountPayload())
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('financial-accounts.destroy', $foreignFinancialAccount))
        ->assertForbidden();
});

it('keeps financial accounts readable but blocks writes without an active subscription', function () {
    $account = Account::factory()->create();
    $owner = financialAccountOwner($account, false);

    $this->actingAs($owner)
        ->get(route('financial-accounts.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->post(route('financial-accounts.store'), financialAccountPayload())
        ->assertStatus(402);
});

it('validates account names uniquely within the same tenant', function () {
    $account = Account::factory()->create();
    $owner = financialAccountOwner($account);
    FinancialAccount::factory()->for($account)->create(['name' => 'Bank Operasional']);

    $this->actingAs($owner)
        ->post(route('financial-accounts.store'), financialAccountPayload())
        ->assertSessionHasErrors('name');
});

it('shows the real active account balance on the dashboard', function () {
    $account = Account::factory()->create();
    $owner = financialAccountOwner($account);
    FinancialAccount::factory()->for($account)->create(['current_balance' => 2500000, 'include_in_total' => true]);
    FinancialAccount::factory()->for($account)->create(['current_balance' => 500000, 'include_in_total' => false]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Dashboard')
            ->where('metrics.balance', '2500000')
            ->where('metrics.financial_account_count', 2));
});
