<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SwitchActiveAccount
{
    public function handle(User $user, Account $account, Request $request): void
    {
        $previousAccountId = $user->active_account_id;

        if ($previousAccountId === $account->id) {
            return;
        }

        $user->forceFill(['active_account_id' => $account->id])->save();

        activity('account')
            ->causedBy($user)
            ->performedOn($account)
            ->event('active_account_switched')
            ->withProperties([
                'from_account_id' => $previousAccountId,
                'to_account_id' => $account->id,
            ])
            ->tap(function (Activity $activity) use ($account, $request): void {
                $activity->account_id = $account->id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log('Active account switched');
    }
}
