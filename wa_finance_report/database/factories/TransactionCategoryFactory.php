<?php

namespace Database\Factories;

use App\Enums\TransactionCategoryType;
use App\Models\Account;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'account_id' => Account::factory(),
            'parent_id' => null,
            'name' => str($name)->title()->toString(),
            'slug' => str($name)->slug()->append('-'.fake()->unique()->numberBetween(1, 999999))->toString(),
            'type' => fake()->randomElement(TransactionCategoryType::cases()),
            'icon' => 'ri-price-tag-3-line',
            'color' => '#7367F0',
            'description' => fake()->optional()->sentence(),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
