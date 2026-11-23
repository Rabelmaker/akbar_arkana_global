<?php

namespace App\Enums;

enum BillingPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';
    case Custom = 'custom';
}
