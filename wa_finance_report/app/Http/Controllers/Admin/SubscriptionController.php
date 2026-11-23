<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Subscriptions\SaveSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => Subscription::query()
                ->select([
                    'id',
                    'account_id',
                    'plan_id',
                    'start_date',
                    'end_date',
                    'status',
                    'price',
                    'payment_status',
                    'auto_renew',
                    'notes',
                    'created_at',
                ])
                ->with([
                    'account:id,name',
                    'plan:id,name,code,billing_period,duration_days',
                ])
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'account_id' => $subscription->account_id,
                    'plan_id' => $subscription->plan_id,
                    'account' => $subscription->account ? [
                        'id' => $subscription->account->id,
                        'name' => $subscription->account->name,
                    ] : null,
                    'plan' => $subscription->plan ? [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'code' => $subscription->plan->code,
                        'billing_period' => $subscription->plan->billing_period->value,
                        'duration_days' => $subscription->plan->duration_days,
                    ] : null,
                    'start_date' => $subscription->start_date->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'status' => $subscription->status->value,
                    'price' => $subscription->price,
                    'payment_status' => $subscription->payment_status->value,
                    'auto_renew' => $subscription->auto_renew,
                    'notes' => $subscription->notes,
                    'created_at' => $subscription->created_at->toISOString(),
                ]),
            'accounts' => Account::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'plans' => SubscriptionPlan::query()
                ->select(['id', 'name', 'code', 'price', 'billing_period', 'duration_days'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (SubscriptionPlan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period->value,
                    'duration_days' => $plan->duration_days,
                ]),
        ]);
    }

    public function store(
        StoreSubscriptionRequest $request,
        SaveSubscription $saveSubscription,
    ): RedirectResponse {
        $saveSubscription->handle(null, $request->validated(), $request->user(), $request);

        return back()->with('success', 'Subscription berhasil diberikan.');
    }

    public function update(
        UpdateSubscriptionRequest $request,
        Subscription $subscription,
        SaveSubscription $saveSubscription,
    ): RedirectResponse {
        $saveSubscription->handle($subscription, $request->validated(), $request->user(), $request);

        return back()->with('success', 'Subscription berhasil diperbarui.');
    }
}
