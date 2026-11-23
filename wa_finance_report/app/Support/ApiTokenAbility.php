<?php

namespace App\Support;

class ApiTokenAbility
{
    public const TransactionsRead = 'transactions:read';

    public const TransactionsWrite = 'transactions:write';

    public const ReportsRead = 'reports:read';

    public const FinancialAccountsRead = 'financial-accounts:read';

    public const FinancialAccountsWrite = 'financial-accounts:write';

    public const CategoriesRead = 'categories:read';

    public const CategoriesWrite = 'categories:write';

    public const BudgetsRead = 'budgets:read';

    public const BudgetsWrite = 'budgets:write';

    public const IntegrationsWrite = 'integrations:write';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::TransactionsRead => 'Baca transaksi',
            self::TransactionsWrite => 'Kelola transaksi',
            self::ReportsRead => 'Baca laporan',
            self::FinancialAccountsRead => 'Baca rekening',
            self::FinancialAccountsWrite => 'Kelola rekening',
            self::CategoriesRead => 'Baca kategori',
            self::CategoriesWrite => 'Kelola kategori',
            self::BudgetsRead => 'Baca budget',
            self::BudgetsWrite => 'Kelola budget',
            self::IntegrationsWrite => 'Kirim data integrasi',
        ];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_keys(self::options());
    }
}
