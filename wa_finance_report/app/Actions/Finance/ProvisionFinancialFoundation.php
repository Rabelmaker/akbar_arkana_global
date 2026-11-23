<?php

namespace App\Actions\Finance;

use App\Enums\FinancialAccountType;
use App\Enums\TransactionCategoryType;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionFinancialFoundation
{
    /**
     * @var array<int, array{name: string, type: TransactionCategoryType, icon: string, color: string}>
     */
    private const DEFAULT_CATEGORIES = [
        ['name' => 'Penjualan', 'type' => TransactionCategoryType::Income, 'icon' => 'ri-shopping-bag-3-line', 'color' => '#28C76F'],
        ['name' => 'Pendapatan Lain', 'type' => TransactionCategoryType::Income, 'icon' => 'ri-add-circle-line', 'color' => '#00BAD1'],
        ['name' => 'Belanja Operasional', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-store-2-line', 'color' => '#FF9F43'],
        ['name' => 'Gaji dan Honor', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-team-line', 'color' => '#EA5455'],
        ['name' => 'Transportasi', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-car-line', 'color' => '#7367F0'],
        ['name' => 'Utilitas', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-flashlight-line', 'color' => '#FFB400'],
    ];

    public function handle(Account $account): void
    {
        DB::transaction(function () use ($account): void {
            $account->financialAccounts()->firstOrCreate(
                ['name' => 'Kas Utama'],
                [
                    'type' => FinancialAccountType::Cash,
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'currency' => $account->currency,
                    'color' => '#7367F0',
                    'icon' => 'ri-wallet-3-line',
                    'is_active' => true,
                    'include_in_total' => true,
                ],
            );

            foreach (self::DEFAULT_CATEGORIES as $order => $category) {
                $account->transactionCategories()->firstOrCreate(
                    ['slug' => Str::slug($category['name'])],
                    [
                        ...$category,
                        'is_default' => true,
                        'is_active' => true,
                        'sort_order' => $order + 1,
                    ],
                );
            }
        });
    }
}
