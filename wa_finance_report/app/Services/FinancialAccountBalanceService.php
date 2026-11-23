<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class FinancialAccountBalanceService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Account $account, User $user, array $attributes, Request $request): FinancialAccount
    {
        return DB::transaction(function () use ($account, $user, $attributes, $request): FinancialAccount {
            $openingBalance = (string) ($attributes['opening_balance'] ?? 0);
            $financialAccount = $account->financialAccounts()->create([
                ...$attributes,
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'currency' => $account->currency,
            ]);

            $this->log($financialAccount, $user, $request, 'created', 'Financial account created');

            return $financialAccount;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(FinancialAccount $financialAccount, User $user, array $attributes, Request $request): FinancialAccount
    {
        return DB::transaction(function () use ($financialAccount, $user, $attributes, $request): FinancialAccount {
            $lockedAccount = FinancialAccount::query()->lockForUpdate()->findOrFail($financialAccount->id);
            $oldValues = $lockedAccount->only(array_keys($attributes));

            if (array_key_exists('opening_balance', $attributes)) {
                $difference = (float) $attributes['opening_balance'] - (float) $lockedAccount->opening_balance;
                $attributes['current_balance'] = (float) $lockedAccount->current_balance + $difference;
            }

            $lockedAccount->update($attributes);

            if ($lockedAccount->getChanges() !== []) {
                $this->log(
                    $lockedAccount,
                    $user,
                    $request,
                    'updated',
                    'Financial account updated',
                    ['old' => $oldValues, 'attributes' => $lockedAccount->only(array_keys($attributes))],
                );
            }

            return $lockedAccount->refresh();
        });
    }

    public function delete(FinancialAccount $financialAccount, User $user, Request $request): void
    {
        DB::transaction(function () use ($financialAccount, $user, $request): void {
            $this->log($financialAccount, $user, $request, 'deleted', 'Financial account deleted');
            $financialAccount->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(
        FinancialAccount $financialAccount,
        User $user,
        Request $request,
        string $event,
        string $description,
        array $properties = [],
    ): void {
        activity('financial_account')
            ->causedBy($user)
            ->performedOn($financialAccount)
            ->event($event)
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($financialAccount, $request): void {
                $activity->account_id = $financialAccount->account_id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log($description);
    }
}
