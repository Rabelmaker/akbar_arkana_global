<?php

use App\Enums\TransactionSource;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\ApiTokenAbility;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function apiFinanceOwner(array $abilities = ['*'], bool $apiAccess = true): array
{
    $account = Account::factory()->create();
    $user = User::factory()->create(['password' => bcrypt('password-api')]);
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();
    $subscription = Subscription::factory()->for($account)->create();
    $subscription->plan()->update(['api_access' => $apiAccess]);
    $token = $user->createToken('Pest API', $abilities)->plainTextToken;

    return [$user, $account, $token];
}

function apiTransactionPayload(FinancialAccount $financialAccount, array $overrides = []): array
{
    return [
        'type' => 'expense',
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'financial_account_id' => $financialAccount->id,
        'destination_financial_account_id' => null,
        'category_id' => null,
        'amount' => 100000,
        'fee_amount' => 2500,
        'description' => 'Pembelian melalui API',
        'merchant' => 'Vendor API',
        'reference_number' => 'API-REF-001',
        'payment_method' => 'transfer',
        'notes' => null,
        'status' => 'draft',
        'idempotency_key' => 'api-'.fake()->uuid(),
        ...$overrides,
    ];
}

function integrationPayload(array $overrides = []): array
{
    return [
        'external_id' => 'wamid.'.fake()->uuid(),
        'idempotency_key' => 'wa-'.fake()->uuid(),
        'source' => TransactionSource::Whatsapp->value,
        'input_type' => 'text',
        'transaction' => [
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'amount' => 75000,
            'fee_amount' => 0,
            'category' => 'Makan',
            'financial_account' => 'Kas Utama',
            'destination_financial_account' => null,
            'description' => 'Makan siang tim',
            'merchant' => 'Warung Nusantara',
            'reference_number' => null,
            'payment_method' => 'cash',
            'notes' => null,
            'status' => 'posted',
        ],
        'sender' => ['phone' => '+6281234567890', 'name' => 'Akbar'],
        'ai' => [
            'model' => 'extractor-v1',
            'confidence_score' => 0.96,
            'raw_result' => ['intent' => 'expense'],
        ],
        ...$overrides,
    ];
}

it('returns a consistent json error for unauthenticated API requests', function () {
    $this->getJson('/api/v1/financial-accounts')
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ])
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('authenticates eligible users and allows logout even after api entitlement is removed', function () {
    [$user, $account] = apiFinanceOwner();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password-api',
        'device_name' => 'n8n workflow',
        'abilities' => [ApiTokenAbility::ReportsRead],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.abilities.0', ApiTokenAbility::ReportsRead);

    $token = $login->json('data.token');
    $account->validSubscription()->firstOrFail()->plan()->update(['api_access' => false]);

    $this->withToken($token)
        ->getJson('/api/v1/auth/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->withToken($token)
        ->getJson('/api/v1/account')
        ->assertForbidden();

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();
});

it('rejects invalid credentials and plans without api access', function () {
    [$user] = apiFinanceOwner(apiAccess: false);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'test',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password-api',
        'device_name' => 'test',
    ])->assertForbidden()
        ->assertJsonPath('success', false);
});

it('enforces token abilities and tenant isolation', function () {
    [, $account, $token] = apiFinanceOwner([ApiTokenAbility::FinancialAccountsRead]);
    $own = FinancialAccount::factory()->for($account)->create(['name' => 'Kas Sendiri']);
    $foreign = FinancialAccount::factory()->for(Account::factory()->create())->create();

    $this->withToken($token)
        ->getJson('/api/v1/financial-accounts')
        ->assertOk()
        ->assertJsonPath('data.0.id', $own->id);

    $this->withToken($token)
        ->postJson('/api/v1/financial-accounts', [])
        ->assertForbidden();

    $this->withToken($token)
        ->getJson("/api/v1/financial-accounts/{$foreign->id}")
        ->assertForbidden();
});

it('provides scoped CRUD endpoints for financial accounts, categories, and budgets', function () {
    [, , $token] = apiFinanceOwner([
        ApiTokenAbility::FinancialAccountsRead,
        ApiTokenAbility::FinancialAccountsWrite,
        ApiTokenAbility::CategoriesRead,
        ApiTokenAbility::CategoriesWrite,
        ApiTokenAbility::BudgetsRead,
        ApiTokenAbility::BudgetsWrite,
    ]);

    $financialAccount = $this->withToken($token)->postJson('/api/v1/financial-accounts', [
        'name' => 'Bank API',
        'type' => 'bank',
        'institution_name' => 'Bank Demo',
        'account_number' => '99887766',
        'account_holder_name' => 'Akbar',
        'opening_balance' => 1000000,
        'color' => '#7367F0',
        'icon' => 'ri-bank-line',
        'is_active' => true,
        'include_in_total' => true,
        'notes' => null,
    ])->assertCreated()->json('data');

    $category = $this->withToken($token)->postJson('/api/v1/categories', [
        'name' => 'Operasional API',
        'type' => 'expense',
        'parent_id' => null,
        'icon' => 'ri-price-tag-3-line',
        'color' => '#7367F0',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ])->assertCreated()->json('data');

    $this->withToken($token)->postJson('/api/v1/budgets', [
        'name' => 'Budget API',
        'category_id' => $category['id'],
        'financial_account_id' => $financialAccount['id'],
        'period_type' => 'monthly',
        'start_date' => today()->startOfMonth()->toDateString(),
        'end_date' => today()->endOfMonth()->toDateString(),
        'amount' => 500000,
        'alert_percentage' => 80,
        'rollover_enabled' => false,
        'status' => 'active',
        'notes' => null,
    ])->assertCreated()
        ->assertJsonPath('data.amount', '500000.00');

    $this->withToken($token)->getJson('/api/v1/budgets')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('creates and posts API transactions with the correct source and balance effect', function () {
    [, $account, $token] = apiFinanceOwner([
        ApiTokenAbility::TransactionsRead,
        ApiTokenAbility::TransactionsWrite,
    ]);
    $financialAccount = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);

    $created = $this->withToken($token)
        ->postJson('/api/v1/transactions', apiTransactionPayload($financialAccount))
        ->assertCreated()
        ->assertJsonPath('data.source', TransactionSource::Api->value)
        ->assertJsonPath('data.status', 'draft');

    $transactionId = $created->json('data.id');
    $this->withToken($token)
        ->postJson("/api/v1/transactions/{$transactionId}/post")
        ->assertOk()
        ->assertJsonPath('data.status', 'posted');

    expect($financialAccount->fresh()->current_balance)->toBe('897500.00')
        ->and(Transaction::findOrFail($transactionId)->source)->toBe(TransactionSource::Api);
});

it('returns report data through the reports scope', function () {
    [, $account, $token] = apiFinanceOwner([ApiTokenAbility::ReportsRead]);
    $financialAccount = FinancialAccount::factory()->for($account)->create();
    Transaction::factory()->for($account)->create([
        'financial_account_id' => $financialAccount->id,
        'type' => 'income',
        'status' => 'posted',
        'amount' => 250000,
        'fee_amount' => 0,
        'total_amount' => 250000,
        'transaction_date' => now(),
    ]);

    $this->withToken($token)
        ->getJson('/api/v1/reports/summary?date_from='.today()->toDateString().'&date_to='.today()->toDateString().'&status=posted')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.transaction_count', 1);
});

it('processes integration transactions idempotently and sanitizes operational logs', function () {
    [, $account, $token] = apiFinanceOwner([ApiTokenAbility::IntegrationsWrite]);
    FinancialAccount::factory()->for($account)->create(['name' => 'Kas Utama', 'current_balance' => 500000]);
    TransactionCategory::factory()->for($account)->create(['name' => 'Makan', 'type' => 'expense']);
    $payload = integrationPayload(['idempotency_key' => 'wa-idempotent-001']);

    $this->withToken($token)
        ->withHeader('X-Workflow-Secret', 'safe-to-log')
        ->postJson('/api/v1/integrations/transactions', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'completed');

    $this->withToken($token)
        ->postJson('/api/v1/integrations/transactions', $payload)
        ->assertOk()
        ->assertJsonPath('data.duplicate', true);

    expect($account->transactions()->count())->toBe(1)
        ->and($account->webhookLogs()->count())->toBe(1)
        ->and($account->aiProcessingLogs()->count())->toBe(1);

    $webhook = $account->webhookLogs()->sole();
    $aiLog = $account->aiProcessingLogs()->sole();
    expect($webhook->headers)->not->toHaveKey('authorization')
        ->and(data_get($webhook->payload, 'sender.phone'))->toContain('*')
        ->and(data_get($aiLog->raw_input, 'ai_raw_result.intent'))->toBe('expense');
});

it('returns needs review without creating categories or accounts when integration mapping is missing', function () {
    [, $account, $token] = apiFinanceOwner([ApiTokenAbility::IntegrationsWrite]);

    $this->withToken($token)
        ->postJson('/api/v1/integrations/transactions', integrationPayload())
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'needs_review')
        ->assertJsonPath('data.missing_fields.0', 'transaction.financial_account');

    expect($account->transactions()->count())->toBe(0)
        ->and($account->financialAccounts()->count())->toBe(0)
        ->and($account->transactionCategories()->count())->toBe(0);
});
