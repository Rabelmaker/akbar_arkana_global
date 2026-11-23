<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class UpdateAccountProfile
{
    /**
     * @param  array{name: string, email: ?string, phone: ?string, timezone: string, currency: string, locale: string}  $attributes
     */
    public function handle(Account $account, User $user, array $attributes, Request $request): Account
    {
        $oldValues = $account->only(array_keys($attributes));

        $account->update($attributes);

        $changes = $account->getChanges();

        if ($changes !== []) {
            activity('account')
                ->causedBy($user)
                ->performedOn($account)
                ->event('updated')
                ->withProperties([
                    'old' => array_intersect_key($oldValues, $changes),
                    'attributes' => array_intersect_key($account->only(array_keys($attributes)), $changes),
                ])
                ->tap(function (Activity $activity) use ($account, $request): void {
                    $activity->account_id = $account->id;
                    $activity->ip_address = $request->ip();
                    $activity->user_agent = $request->userAgent();
                })
                ->log('Account profile updated');
        }

        return $account->refresh();
    }
}
