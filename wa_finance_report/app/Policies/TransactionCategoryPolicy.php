<?php

namespace App\Policies;

use App\Models\TransactionCategory;
use App\Models\User;

class TransactionCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage transaction categories');
    }

    public function view(User $user, TransactionCategory $transactionCategory): bool
    {
        return $this->belongsToActiveAccount($user, $transactionCategory);
    }

    public function create(User $user): bool
    {
        return $user->can('manage transaction categories') && $user->active_account_id !== null;
    }

    public function update(User $user, TransactionCategory $transactionCategory): bool
    {
        return $this->belongsToActiveAccount($user, $transactionCategory);
    }

    public function delete(User $user, TransactionCategory $transactionCategory): bool
    {
        return ! $transactionCategory->is_default
            && $this->belongsToActiveAccount($user, $transactionCategory);
    }

    public function restore(User $user, TransactionCategory $transactionCategory): bool
    {
        return false;
    }

    public function forceDelete(User $user, TransactionCategory $transactionCategory): bool
    {
        return false;
    }

    private function belongsToActiveAccount(User $user, TransactionCategory $transactionCategory): bool
    {
        return $user->can('manage transaction categories')
            && $user->active_account_id === $transactionCategory->account_id
            && $user->accounts()->whereKey($transactionCategory->account_id)->exists();
    }
}
