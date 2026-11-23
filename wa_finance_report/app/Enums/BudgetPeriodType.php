<?php

namespace App\Enums;

enum BudgetPeriodType: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Mingguan',
            self::Monthly => 'Bulanan',
            self::Quarterly => 'Kuartalan',
            self::Yearly => 'Tahunan',
            self::Custom => 'Periode Kustom',
        };
    }
}
