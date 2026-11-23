<?php

use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function transactionOwner(Account $account, bool $subscribed = true): User
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

function transactionPayload(FinancialAccount $source, array $overrides = []): array
{
    return [
        'type' => 'expense',
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'financial_account_id' => $source->id,
        'destination_financial_account_id' => null,
        'category_id' => null,
        'amount' => 100000,
        'fee_amount' => 2500,
        'description' => 'Pembelian operasional',
        'merchant' => 'Toko Demo',
        'reference_number' => 'REF-001',
        'payment_method' => 'transfer',
        'notes' => 'Catatan transaksi',
        'status' => 'posted',
        'idempotency_key' => 'idem-'.fake()->uuid(),
        ...$overrides,
    ];
}

it('lists only transactions from the active tenant with server pagination', function () {
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();
    $foreignSource = FinancialAccount::factory()->for($other)->create();
    Transaction::factory()->for($account)->create(['financial_account_id' => $source->id, 'description' => 'Milik Saya']);
    Transaction::factory()->for($other)->create(['financial_account_id' => $foreignSource->id, 'description' => 'Tenant Lain']);

    $this->actingAs($owner)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Milik Saya'));
});

it('keeps draft transactions from affecting balances and allows posting later', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);

    $this->actingAs($owner)
        ->post(route('transactions.store'), transactionPayload($source, ['status' => 'draft']))
        ->assertRedirect();

    $transaction = $account->transactions()->firstOrFail();
    expect($source->fresh()->current_balance)->toBe('1000000.00')
        ->and($transaction->status)->toBe(TransactionStatus::Draft);

    $this->actingAs($owner)->post(route('transactions.post', $transaction))->assertRedirect();

    expect($source->fresh()->current_balance)->toBe('897500.00')
        ->and($transaction->fresh()->status)->toBe(TransactionStatus::Posted);
});

it('posts income and expense with the correct balance effects', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);

    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'type' => 'income',
        'amount' => 250000,
        'fee_amount' => 0,
        'description' => 'Pendapatan proyek',
    ]))->assertRedirect();
    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'amount' => 100000,
        'fee_amount' => 5000,
        'idempotency_key' => 'expense-two',
    ]))->assertRedirect();

    expect($source->fresh()->current_balance)->toBe('1145000.00');

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('metrics.income_today', '250000')
            ->where('metrics.expense_today', '105000')
            ->where('metrics.net_cash_flow', '145000'));
});

it('moves money between accounts atomically and charges fees to the source', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);
    $destination = FinancialAccount::factory()->for($account)->create(['current_balance' => 200000]);

    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'type' => 'transfer',
        'destination_financial_account_id' => $destination->id,
        'amount' => 300000,
        'fee_amount' => 2500,
        'description' => 'Pindah dana',
    ]))->assertRedirect();

    expect($source->fresh()->current_balance)->toBe('697500.00')
        ->and($destination->fresh()->current_balance)->toBe('500000.00');
});

it('cancels a posted transaction and restores its balance', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);
    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source))->assertRedirect();
    $transaction = $account->transactions()->firstOrFail();

    $this->actingAs($owner)->post(route('transactions.cancel', $transaction), ['reason' => 'Salah input'])->assertRedirect();

    expect($source->fresh()->current_balance)->toBe('1000000.00')
        ->and($transaction->fresh()->status)->toBe(TransactionStatus::Cancelled)
        ->and($transaction->fresh()->cancellation_reason)->toBe('Salah input');
});

it('creates an auditable reversal transaction and restores balances', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);
    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source))->assertRedirect();
    $original = $account->transactions()->firstOrFail();

    $this->actingAs($owner)->post(route('transactions.reverse', $original), ['reason' => 'Dokumen duplikat'])->assertRedirect();
    $reversal = $account->transactions()->where('reversed_transaction_id', $original->id)->firstOrFail();

    expect($source->fresh()->current_balance)->toBe('1000000.00')
        ->and($original->fresh()->status)->toBe(TransactionStatus::Reversed)
        ->and($reversal->status)->toBe(TransactionStatus::Posted)
        ->and($reversal->source->value)->toBe('system');
});

it('uses idempotency keys to prevent duplicate posted balance effects', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);
    $payload = transactionPayload($source, ['idempotency_key' => 'checkout-001']);

    $this->actingAs($owner)->post(route('transactions.store'), $payload)->assertRedirect();
    $this->actingAs($owner)->post(route('transactions.store'), $payload)->assertRedirect();

    expect($account->transactions()->count())->toBe(1)
        ->and($source->fresh()->current_balance)->toBe('897500.00');
});

it('enforces the active plans monthly transaction quota when posting', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $account->subscriptions()->firstOrFail()->plan()->update(['max_monthly_transactions' => 1]);
    $source = FinancialAccount::factory()->for($account)->create(['current_balance' => 1000000]);

    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'amount' => 100000,
        'fee_amount' => 0,
        'idempotency_key' => 'quota-first',
    ]))->assertRedirect();
    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'amount' => 200000,
        'fee_amount' => 0,
        'idempotency_key' => 'quota-second',
    ]))->assertSessionHasErrors('status');

    expect($source->fresh()->current_balance)->toBe('900000.00')
        ->and($account->transactions()->where('status', 'posted')->count())->toBe(1)
        ->and($account->transactions()->where('status', 'draft')->count())->toBe(0);
});

it('stores transaction attachments privately', function () {
    Storage::fake('local');
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();

    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source, [
        'status' => 'draft',
        'attachments' => [UploadedFile::fake()->image('receipt.jpg')],
    ]))->assertRedirect();

    $attachment = $account->transactions()->firstOrFail()->attachments()->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);
});

it('forbids tenant crossover and all writes without an active subscription', function () {
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    $owner = transactionOwner($account);
    $readOnlyOwner = transactionOwner(Account::factory()->create(), false);
    $foreignSource = FinancialAccount::factory()->for($other)->create();
    $foreignTransaction = Transaction::factory()->for($other)->create(['financial_account_id' => $foreignSource->id]);

    $this->actingAs($owner)->post(route('transactions.post', $foreignTransaction))->assertForbidden();
    $this->actingAs($readOnlyOwner)
        ->post(route('transactions.store'), transactionPayload($foreignSource))
        ->assertStatus(402);
});

it('does not permit editing or deleting posted transactions', function () {
    $account = Account::factory()->create();
    $owner = transactionOwner($account);
    $source = FinancialAccount::factory()->for($account)->create();
    $this->actingAs($owner)->post(route('transactions.store'), transactionPayload($source))->assertRedirect();
    $transaction = $account->transactions()->firstOrFail();

    $this->actingAs($owner)->patch(route('transactions.update', $transaction), transactionPayload($source, ['status' => 'draft']))->assertForbidden();
    $this->actingAs($owner)->delete(route('transactions.destroy', $transaction))->assertForbidden();
});
