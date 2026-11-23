<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\FinancialAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function budgetOwner(Account $account, bool $subscribed = true): User
{
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();

    if ($subscribed) {
        Subscription::factory()->for($account)->create();
    }

    return $user;
}

function budgetPayload(array $overrides = []): array
{
    return [
        'name' => 'Operasional Bulanan',
        'category_id' => null,
        'financial_account_id' => null,
        'period_type' => 'monthly',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
        'amount' => 1000000,
        'alert_percentage' => 80,
        'rollover_enabled' => false,
        'status' => 'active',
        'notes' => 'Budget pengeluaran operasional.',
        ...$overrides,
    ];
}

function expenseTransaction(Account $account, FinancialAccount $source, array $overrides = []): Transaction
{
    return Transaction::factory()->for($account)->create([
        'financial_account_id' => $source->id,
        'type' => 'expense',
        'transaction_date' => now(),
        'amount' => 100000,
        'fee_amount' => 0,
        'total_amount' => 100000,
        'status' => 'posted',
        ...$overrides,
    ]);
}

function budgetTransactionPayload(FinancialAccount $source, array $overrides = []): array
{
    return [
        'type' => 'expense',
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'financial_account_id' => $source->id,
        'destination_financial_account_id' => null,
        'category_id' => null,
        'amount' => 100000,
        'fee_amount' => 0,
        'description' => 'Pengeluaran budget',
        'merchant' => null,
        'reference_number' => null,
        'payment_method' => 'transfer',
        'notes' => null,
        'status' => 'posted',
        'idempotency_key' => 'budget-'.fake()->uuid(),
        ...$overrides,
    ];
}

it('lists only budgets from the active tenant with calculated usage', function () {
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    $owner = budgetOwner($account);
    Budget::factory()->for($account)->create(['name' => 'Budget Saya']);
    Budget::factory()->for($other)->create(['name' => 'Budget Tenant Lain']);

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Budgets/Index')
            ->has('budgets.data', 1)
            ->where('budgets.data.0.name', 'Budget Saya')
            ->where('budgets.data.0.used_amount', 0));
});

it('creates and audits a tenant budget', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);

    $this->actingAs($owner)
        ->post(route('budgets.store'), budgetPayload())
        ->assertRedirect();

    $budget = $account->budgets()->firstOrFail();
    expect($budget->name)->toBe('Operasional Bulanan')
        ->and($budget->amount)->toBe('1000000.00')
        ->and($budget->created_by)->toBe($owner->id);
    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'causer_id' => $owner->id,
        'event' => 'created',
        'description' => 'Budget created',
    ]);
});

it('calculates usage only from matching posted expenses', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();
    $otherSource = FinancialAccount::factory()->for($account)->create();
    $category = TransactionCategory::factory()->for($account)->create(['type' => 'expense']);
    $otherCategory = TransactionCategory::factory()->for($account)->create(['type' => 'expense']);
    $budget = Budget::factory()->for($account)->create([
        'category_id' => $category->id,
        'financial_account_id' => $source->id,
        'amount' => 1000000,
        'alert_percentage' => 80,
    ]);

    expenseTransaction($account, $source, ['category_id' => $category->id, 'amount' => 600000, 'fee_amount' => 50000, 'total_amount' => 650000]);
    expenseTransaction($account, $source, ['category_id' => $category->id, 'status' => 'draft']);
    expenseTransaction($account, $source, ['category_id' => $category->id, 'type' => 'income']);
    expenseTransaction($account, $source, ['category_id' => $category->id, 'transaction_date' => now()->subMonths(2)]);
    expenseTransaction($account, $source, ['category_id' => $otherCategory->id]);
    expenseTransaction($account, $otherSource, ['category_id' => $category->id]);
    expenseTransaction($account, $source, ['category_id' => $category->id, 'status' => 'cancelled']);
    $original = expenseTransaction($account, $source, ['category_id' => $category->id, 'status' => 'reversed']);
    expenseTransaction($account, $source, ['category_id' => $category->id, 'reversed_transaction_id' => $original->id]);

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('budgets.data.0.id', $budget->id)
            ->where('budgets.data.0.used_amount', 650000)
            ->where('budgets.data.0.remaining_amount', 350000)
            ->where('budgets.data.0.usage_percentage', 65)
            ->where('budgets.data.0.health_status', 'safe'));
});

it('derives warning and exceeded health from the configured threshold', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();
    Budget::factory()->for($account)->create(['amount' => 1000000, 'alert_percentage' => 75]);
    expenseTransaction($account, $source, ['amount' => 800000, 'total_amount' => 800000]);

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('budgets.data.0.health_status', 'warning')
            ->where('summary.warning', 1)
            ->where('summary.exceeded', 0));

    expenseTransaction($account, $source, ['amount' => 300000, 'total_amount' => 300000]);

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('budgets.data.0.health_status', 'exceeded')
            ->where('budgets.data.0.remaining_amount', -100000)
            ->where('summary.exceeded', 1));
});

it('updates budget usage immediately after a posted expense is cancelled', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);
    Budget::factory()->for($account)->create(['amount' => 500000]);

    $this->actingAs($owner)->post(route('transactions.store'), budgetTransactionPayload($source, [
        'amount' => 200000,
        'fee_amount' => 0,
        'idempotency_key' => 'budget-live-expense',
    ]))->assertRedirect();

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page->where('budgets.data.0.used_amount', 200000));

    $transaction = $account->transactions()->firstOrFail();
    $this->actingAs($owner)->post(route('transactions.cancel', $transaction), ['reason' => 'Dibatalkan'])->assertRedirect();

    $this->actingAs($owner)
        ->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page->where('budgets.data.0.used_amount', 0));
});

it('rejects income categories and overlapping budgets with identical scope', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);
    $incomeCategory = TransactionCategory::factory()->for($account)->create(['type' => 'income']);
    Budget::factory()->for($account)->create();

    $this->actingAs($owner)
        ->post(route('budgets.store'), budgetPayload(['category_id' => $incomeCategory->id]))
        ->assertSessionHasErrors('category_id');

    $this->actingAs($owner)
        ->post(route('budgets.store'), budgetPayload(['name' => 'Budget Tumpang Tindih']))
        ->assertSessionHasErrors('start_date');
});

it('forbids modifying budgets from another tenant', function () {
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    $owner = budgetOwner($account);
    $foreignBudget = Budget::factory()->for($other)->create();

    $this->actingAs($owner)
        ->patch(route('budgets.update', $foreignBudget), budgetPayload())
        ->assertForbidden();
    $this->actingAs($owner)
        ->delete(route('budgets.destroy', $foreignBudget))
        ->assertForbidden();
});

it('keeps budgets readable but blocks writes without an active subscription', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account, false);

    $this->actingAs($owner)->get(route('budgets.index'))->assertSuccessful();
    $this->actingAs($owner)
        ->post(route('budgets.store'), budgetPayload())
        ->assertStatus(402);
});

it('shows current budget totals on the user dashboard', function () {
    $account = Account::factory()->create();
    $owner = budgetOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();
    Budget::factory()->for($account)->create(['amount' => 1000000]);
    expenseTransaction($account, $source, ['amount' => 250000, 'total_amount' => 250000]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('budgetSummary.allocated', 1000000)
            ->where('budgetSummary.used', 250000)
            ->where('budgetSummary.remaining', 750000)
            ->where('budgetSummary.active', 1));
});
