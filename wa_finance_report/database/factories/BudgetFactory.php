<?php

namespace Database\Factories;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use App\Models\Account;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(2, true),
            'category_id' => null,
            'financial_account_id' => null,
            'period_type' => BudgetPeriodType::Monthly,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'amount' => fake()->numberBetween(500000, 10000000),
            'alert_percentage' => 80,
            'rollover_enabled' => false,
            'status' => BudgetStatus::Active,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
