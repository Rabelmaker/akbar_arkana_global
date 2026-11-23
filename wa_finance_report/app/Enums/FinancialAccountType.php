<?php

namespace App\Enums;

enum FinancialAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case CreditCard = 'credit_card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Kas',
            self::Bank => 'Bank',
            self::EWallet => 'E-Wallet',
            self::CreditCard => 'Kartu Kredit',
            self::Other => 'Lainnya',
        };
    }
}
