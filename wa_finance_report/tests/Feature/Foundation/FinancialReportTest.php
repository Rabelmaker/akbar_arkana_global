<?php

use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function reportOwner(Account $account, bool $subscribed = true): User
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

function reportTransaction(Account $account, FinancialAccount $financialAccount, array $overrides = []): Transaction
{
    return Transaction::factory()->for($account)->create([
        'financial_account_id' => $financialAccount->id,
        'transaction_date' => now(),
        'status' => 'posted',
        'type' => 'expense',
        'amount' => 100000,
        'fee_amount' => 5000,
        'total_amount' => 105000,
        'created_by' => $account->created_by,
        ...$overrides,
    ]);
}

it('builds tenant isolated financial summaries from the selected period', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = reportOwner($account);
    $financialAccount = FinancialAccount::factory()->for($account)->create(['current_balance' => 2500000]);
    $foreignFinancialAccount = FinancialAccount::factory()->for($otherAccount)->create();

    reportTransaction($account, $financialAccount, [
        'type' => 'income',
        'amount' => 500000,
        'fee_amount' => 0,
        'total_amount' => 500000,
        'description' => 'Pendapatan laporan',
    ]);
    reportTransaction($account, $financialAccount, ['description' => 'Biaya laporan']);
    reportTransaction($account, $financialAccount, ['status' => 'draft', 'description' => 'Draft tidak dihitung']);
    reportTransaction($account, $financialAccount, ['transaction_date' => now()->subMonths(2), 'description' => 'Di luar periode']);
    reportTransaction($otherAccount, $foreignFinancialAccount, ['description' => 'Tenant lain']);

    $this->actingAs($owner)
        ->get(route('reports.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Reports/Index')
            ->where('report.summary.income', 500000)
            ->where('report.summary.expense', 105000)
            ->where('report.summary.net', 395000)
            ->where('report.summary.transaction_count', 2)
            ->where('report.balances.0.balance', 2500000)
            ->has('transactions.data', 2)
            ->where('canExport', true));
});

it('supports report filters and rejects resources from another tenant', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $owner = reportOwner($account);
    $financialAccount = FinancialAccount::factory()->for($account)->create();
    $foreignFinancialAccount = FinancialAccount::factory()->for($otherAccount)->create();
    reportTransaction($account, $financialAccount);

    $this->actingAs($owner)
        ->get(route('reports.index', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'status' => 'posted',
            'type' => 'income',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.summary.transaction_count', 0)
            ->where('report.summary.expense', 0));

    $this->actingAs($owner)
        ->get(route('reports.index', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'status' => 'posted',
            'financial_account_id' => $foreignFinancialAccount->id,
        ]))
        ->assertSessionHasErrors('financial_account_id');
});

it('downloads csv and pdf exports and serves xlsx source data', function () {
    $account = Account::factory()->create(['name' => 'Tenant Laporan']);
    $owner = reportOwner($account);
    $financialAccount = FinancialAccount::factory()->for($account)->create();
    reportTransaction($account, $financialAccount, ['description' => 'Ekspor operasional']);
    $filters = [
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->endOfMonth()->toDateString(),
        'status' => 'posted',
    ];

    $csv = $this->actingAs($owner)->get(route('reports.export.csv', $filters));
    $csv->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('Ekspor operasional');

    $pdf = $this->actingAs($owner)->get(route('reports.export.pdf', $filters));
    $pdf->assertSuccessful()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF');

    $this->actingAs($owner)
        ->getJson(route('reports.export.xlsx', $filters))
        ->assertSuccessful()
        ->assertJsonPath('account', 'Tenant Laporan')
        ->assertJsonPath('summary.expense', 105000)
        ->assertJsonPath('transactions.0.description', 'Ekspor operasional');
});

it('keeps reports readable while blocking exports without an eligible active plan', function () {
    $account = Account::factory()->create();
    $owner = reportOwner($account);
    $account->subscriptions()->firstOrFail()->plan()->update(['report_export' => false]);

    $this->actingAs($owner)
        ->get(route('reports.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('canExport', false));

    $this->actingAs($owner)
        ->get(route('reports.export.csv'))
        ->assertForbidden();
});

it('requires the report permission', function () {
    $account = Account::factory()->create();
    $owner = reportOwner($account);
    $owner->removeRole('user');

    $this->actingAs($owner)->get(route('reports.index'))->assertForbidden();
});
