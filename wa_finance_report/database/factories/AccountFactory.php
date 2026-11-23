<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
            'status' => AccountStatus::Active,
        ];
    }
}
