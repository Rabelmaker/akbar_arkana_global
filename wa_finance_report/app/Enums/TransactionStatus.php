<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Menunggu',
            self::Posted => 'Tercatat',
            self::Cancelled => 'Dibatalkan',
            self::Reversed => 'Dibalik',
        };
    }
}
