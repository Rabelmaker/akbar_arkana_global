<?php

use App\Actions\Finance\ProvisionFinancialFoundation;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function categoryOwner(Account $account, bool $withSubscription = true): User
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

function categoryPayload(array $overrides = []): array
{
    return [
        'name' => 'Pemasaran Digital',
        'type' => 'expense',
        'parent_id' => null,
        'icon' => 'ri-megaphone-line',
        'color' => '#EA5455',
        'description' => 'Biaya iklan dan kampanye.',
        'is_active' => true,
        'sort_order' => 10,
        ...$overrides,
    ];
}

it('provisions the default financial foundation idempotently', function () {
    $account = Account::factory()->create();
    $provision = app(ProvisionFinancialFoundation::class);

    $provision->handle($account);
    $provision->handle($account);

    expect($account->financialAccounts()->count())->toBe(1)
        ->and($account->transactionCategories()->count())->toBe(6)
        ->and($account->transactionCategories()->where('is_default', true)->count())->toBe(6);
});

it('lists only categories from the active tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = categoryOwner($account);
    TransactionCategory::factory()->for($account)->create(['name' => 'Kategori Saya']);
    TransactionCategory::factory()->for($otherAccount)->create(['name' => 'Kategori Asing']);

    $this->actingAs($owner)
        ->get(route('transaction-categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/TransactionCategories/Index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Kategori Saya'));
});

it('creates a custom category with a tenant-safe slug and audit log', function () {
    $account = Account::factory()->create();
    $owner = categoryOwner($account);

    $this->actingAs($owner)
        ->post(route('transaction-categories.store'), categoryPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('transaction_categories', [
        'account_id' => $account->id,
        'name' => 'Pemasaran Digital',
        'slug' => 'pemasaran-digital',
        'is_default' => false,
    ]);
    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'causer_id' => $owner->id,
        'description' => 'Transaction category created',
    ]);
});

it('accepts only a parent category from the active tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = categoryOwner($account);
    $foreignParent = TransactionCategory::factory()->for($otherAccount)->create();

    $this->actingAs($owner)
        ->post(route('transaction-categories.store'), categoryPayload(['parent_id' => $foreignParent->id]))
        ->assertSessionHasErrors('parent_id');
});

it('rejects a circular category hierarchy', function () {
    $account = Account::factory()->create();
    $owner = categoryOwner($account);
    $parent = TransactionCategory::factory()->for($account)->create();
    $child = TransactionCategory::factory()->for($account)->create(['parent_id' => $parent->id]);

    $this->actingAs($owner)
        ->patch(route('transaction-categories.update', $parent), categoryPayload([
            'name' => $parent->name,
            'parent_id' => $child->id,
        ]))
        ->assertSessionHasErrors('parent_id');
});

it('protects default categories from deletion', function () {
    $account = Account::factory()->create();
    $owner = categoryOwner($account);
    $category = TransactionCategory::factory()->for($account)->create(['is_default' => true]);

    $this->actingAs($owner)
        ->delete(route('transaction-categories.destroy', $category))
        ->assertForbidden();

    expect($category->fresh())->not->toBeNull();
});

it('forbids modifying a category from another tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = categoryOwner($account);
    $foreignCategory = TransactionCategory::factory()->for($otherAccount)->create();

    $this->actingAs($owner)
        ->patch(route('transaction-categories.update', $foreignCategory), categoryPayload())
        ->assertForbidden();
});

it('keeps categories readable but blocks writes without an active subscription', function () {
    $account = Account::factory()->create();
    $owner = categoryOwner($account, false);

    $this->actingAs($owner)
        ->get(route('transaction-categories.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->post(route('transaction-categories.store'), categoryPayload())
        ->assertStatus(402);
});
