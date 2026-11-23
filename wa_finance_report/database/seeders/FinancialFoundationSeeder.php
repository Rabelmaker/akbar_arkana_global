<?php

namespace Database\Seeders;

use App\Actions\Finance\ProvisionFinancialFoundation;
use App\Models\Account;
use Illuminate\Database\Seeder;

class FinancialFoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::query()->each(
            fn (Account $account) => app(ProvisionFinancialFoundation::class)->handle($account),
        );
    }
}
