<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function update(User $user, Account $account): bool
    {
        if (! $user->can('manage account settings')) {
            return false;
        }

        $membership = $user->accounts()
            ->whereKey($account->getKey())
            ->first()
            ?->pivot;

        return $membership !== null
            && ((bool) $membership->is_owner
                || in_array($membership->role, ['account-owner', 'account-admin'], true));
    }

    public function switch(User $user, Account $account): bool
    {
        return $account->status === AccountStatus::Active
            && $user->accounts()->whereKey($account->getKey())->exists();
    }
}
