<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
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
            'name' => Str::title($name),
            'code' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([0, 49000, 99000, 199000]),
            'billing_period' => BillingPeriod::Monthly,
            'duration_days' => 30,
            'max_financial_accounts' => 5,
            'max_monthly_transactions' => 1000,
            'max_users' => 3,
            'api_access' => true,
            'whatsapp_integration' => false,
            'google_sheets_integration' => false,
            'report_export' => true,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
