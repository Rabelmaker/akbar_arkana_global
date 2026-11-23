<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
            self::Completed => 'Selesai',
        };
    }
}
