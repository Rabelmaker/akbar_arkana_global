<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'users' => User::role('user')->count(),
                'accounts' => Account::count(),
                'active_users' => User::role('user')->where('status', 'active')->count(),
                'suspended_users' => User::role('user')->where('status', 'suspended')->count(),
                'active_subscriptions' => Subscription::query()->valid()->count(),
                'expiring_subscriptions' => Subscription::query()
                    ->valid()
                    ->whereBetween('end_date', [today(), today()->addDays(7)])
                    ->count(),
                'expired_subscriptions' => Subscription::where('status', 'expired')
                    ->orWhere(function ($query): void {
                        $query->whereNotNull('end_date')->whereDate('end_date', '<', today());
                    })
                    ->count(),
            ],
            'recentUsers' => User::query()
                ->select(['id', 'name', 'email', 'status', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentSubscriptions' => Subscription::query()
                ->select(['id', 'account_id', 'plan_id', 'status', 'start_date', 'end_date'])
                ->with(['account:id,name', 'plan:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'account' => $subscription->account ? [
                        'id' => $subscription->account->id,
                        'name' => $subscription->account->name,
                    ] : null,
                    'plan' => $subscription->plan ? [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                    ] : null,
                    'status' => $subscription->status->value,
                    'start_date' => $subscription->start_date->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                ]),
        ]);
    }
}
