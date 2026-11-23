<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ApiMoney
{
    /** @var list<string> */
    private const MONEY_KEYS = [
        'allocated',
        'amount',
        'balance',
        'expense',
        'fee',
        'fees',
        'income',
        'net',
        'remaining',
        'total',
        'total_expense',
        'total_income',
        'transfer',
        'used',
    ];

    public static function normalize(mixed $value, ?string $key = null): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item, string|int $itemKey) => self::normalize(
                    $item,
                    is_string($itemKey) ? $itemKey : null,
                ))
                ->all();
        }

        if ($key !== null && in_array($key, self::MONEY_KEYS, true) && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return $value;
    }
}
