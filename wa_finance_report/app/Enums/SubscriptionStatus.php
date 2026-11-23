<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case GracePeriod = 'grace_period';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function grantsAccess(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::GracePeriod], true);
    }
}
