<?php

namespace Database\Factories;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'account_id' => Account::factory(),
            'transaction_number' => 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
            'type' => TransactionType::Expense,
            'transaction_date' => now(),
            'financial_account_id' => FinancialAccount::factory(),
            'amount' => 100000,
            'fee_amount' => 0,
            'total_amount' => 100000,
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'description' => fake()->sentence(4),
            'status' => TransactionStatus::Draft,
            'source' => TransactionSource::Web,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
