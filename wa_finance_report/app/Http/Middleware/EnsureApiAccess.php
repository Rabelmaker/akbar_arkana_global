<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->attributes->get('activeAccount');

        abort_unless(
            $account?->validSubscription()
                ->whereHas('plan', fn ($query) => $query->where('api_access', true))
                ->exists(),
            403,
            'API access is not available on the active plan.',
        );

        return $next($request);
    }
}
