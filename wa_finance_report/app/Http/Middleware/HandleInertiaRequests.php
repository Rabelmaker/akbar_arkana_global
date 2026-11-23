<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeAccount = $request->attributes->get('activeAccount') ?? $user?->activeAccount;
        $validSubscription = $activeAccount?->subscriptions()
            ->valid()
            ->with('plan:id,name,code')
            ->latest('start_date')
            ->latest('id')
            ->first();
        $displaySubscription = $validSubscription ?? $activeAccount?->subscriptions()
            ->with('plan:id,name,code')
            ->latest('start_date')
            ->latest('id')
            ->first();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                ] : null,
                'account' => $activeAccount ? [
                    'id' => $activeAccount->id,
                    'name' => $activeAccount->name,
                    'currency' => $activeAccount->currency,
                    'can_manage' => $user?->can('update', $activeAccount) ?? false,
                ] : null,
                'subscription' => $displaySubscription ? [
                    'id' => $displaySubscription->id,
                    'plan' => $displaySubscription->plan?->name,
                    'plan_code' => $displaySubscription->plan?->code,
                    'status' => $displaySubscription->status->value,
                    'start_date' => $displaySubscription->start_date->toDateString(),
                    'end_date' => $displaySubscription->end_date?->toDateString(),
                    'can_write' => $validSubscription !== null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'new_token' => fn () => $request->session()->get('new_token'),
            ],
        ];
    }
}
