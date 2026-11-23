<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
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

        $account = $request->attributes->get('activeAccount') ?? $user->activeAccount;

        abort_unless(
            $account && $account->hasValidSubscription(),
            402,
            'Subscription aktif diperlukan untuk melakukan perubahan.',
        );

        return $next($request);
    }
}
