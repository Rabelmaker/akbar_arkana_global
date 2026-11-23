<?php

namespace Database\Factories;

use App\Enums\FinancialAccountType;
use App\Models\Account;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
class FinancialAccountFactory extends Factory
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
            'type' => fake()->randomElement(FinancialAccountType::cases()),
            'institution_name' => fake()->optional()->company(),
            'account_number' => fake()->optional()->numerify('##########'),
            'account_holder_name' => fake()->optional()->name(),
            'opening_balance' => fake()->randomFloat(2, 0, 10000000),
            'current_balance' => fn (array $attributes) => $attributes['opening_balance'],
            'currency' => 'IDR',
            'color' => fake()->randomElement(['#7367F0', '#28C76F', '#00BAD1', '#FF9F43']),
            'icon' => 'ri-wallet-3-line',
            'is_active' => true,
            'include_in_total' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
