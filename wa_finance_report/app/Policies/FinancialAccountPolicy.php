<?php

namespace App\Policies;

use App\Models\FinancialAccount;
use App\Models\User;

class FinancialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage financial accounts');
    }

    public function view(User $user, FinancialAccount $financialAccount): bool
    {
        return $this->belongsToActiveAccount($user, $financialAccount);
    }

    public function create(User $user): bool
    {
        return $user->can('manage financial accounts') && $user->active_account_id !== null;
    }

    public function update(User $user, FinancialAccount $financialAccount): bool
    {
        return $this->belongsToActiveAccount($user, $financialAccount);
    }

    public function delete(User $user, FinancialAccount $financialAccount): bool
    {
        return $this->belongsToActiveAccount($user, $financialAccount);
    }

    public function restore(User $user, FinancialAccount $financialAccount): bool
    {
        return false;
    }

    public function forceDelete(User $user, FinancialAccount $financialAccount): bool
    {
        return false;
    }

    private function belongsToActiveAccount(User $user, FinancialAccount $financialAccount): bool
    {
        return $user->can('manage financial accounts')
            && $user->active_account_id === $financialAccount->account_id
            && $user->accounts()->whereKey($financialAccount->account_id)->exists();
    }
}
