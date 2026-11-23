<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
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
            'plan_id' => SubscriptionPlan::factory(),
            'start_date' => today(),
            'end_date' => today()->addMonth(),
            'status' => SubscriptionStatus::Active,
            'price' => 99000,
            'payment_status' => PaymentStatus::Paid,
            'auto_renew' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'start_date' => today()->subMonths(2),
            'end_date' => today()->subMonth(),
            'status' => SubscriptionStatus::Expired,
        ]);
    }
}
