<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $account = $user->activeAccount;

        if (! $account || ! $user->accounts()->whereKey($account->id)->exists()) {
            $account = $user->accounts()->where('accounts.status', AccountStatus::Active)->oldest('accounts.id')->first();
        }

        abort_unless($account && $account->status === AccountStatus::Active, 403, 'No active account is available.');

        if ($user->active_account_id !== $account->id) {
            $user->forceFill(['active_account_id' => $account->id])->save();
        }

        $request->attributes->set('activeAccount', $account);

        return $next($request);
    }
}
