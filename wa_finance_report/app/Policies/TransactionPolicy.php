<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->belongsToActiveAccount($user, $transaction);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage transactions') && $user->active_account_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return $transaction->status === TransactionStatus::Draft
            && $this->belongsToActiveAccount($user, $transaction);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $transaction->status === TransactionStatus::Draft
            && $this->belongsToActiveAccount($user, $transaction);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function post(User $user, Transaction $transaction): bool
    {
        return $transaction->status === TransactionStatus::Draft
            && $this->belongsToActiveAccount($user, $transaction);
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $transaction->status === TransactionStatus::Posted
            && $this->belongsToActiveAccount($user, $transaction);
    }

    public function reverse(User $user, Transaction $transaction): bool
    {
        return $this->cancel($user, $transaction);
    }

    private function belongsToActiveAccount(User $user, Transaction $transaction): bool
    {
        return $user->can('manage transactions')
            && $user->active_account_id === $transaction->account_id
            && $user->accounts()->whereKey($transaction->account_id)->exists();
    }
}
