<?php

namespace App\Enums;

enum TransactionCategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
            self::Both => 'Keduanya',
        };
    }
}
