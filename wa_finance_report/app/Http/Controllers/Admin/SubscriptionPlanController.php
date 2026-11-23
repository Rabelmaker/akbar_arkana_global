<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Subscriptions\SaveSubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class SubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SubscriptionPlans/Index', [
            'plans' => SubscriptionPlan::query()
                ->withCount('subscriptions')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (SubscriptionPlan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period->value,
                    'duration_days' => $plan->duration_days,
                    'max_financial_accounts' => $plan->max_financial_accounts,
                    'max_monthly_transactions' => $plan->max_monthly_transactions,
                    'max_users' => $plan->max_users,
                    'api_access' => $plan->api_access,
                    'whatsapp_integration' => $plan->whatsapp_integration,
                    'google_sheets_integration' => $plan->google_sheets_integration,
                    'report_export' => $plan->report_export,
                    'is_active' => $plan->is_active,
                    'sort_order' => $plan->sort_order,
                    'subscriptions_count' => $plan->subscriptions_count,
                ]),
        ]);
    }

    public function store(
        StoreSubscriptionPlanRequest $request,
        SaveSubscriptionPlan $saveSubscriptionPlan,
    ): RedirectResponse {
        $saveSubscriptionPlan->handle(null, $request->validated(), $request->user(), $request);

        return back()->with('success', 'Plan subscription berhasil dibuat.');
    }

    public function update(
        UpdateSubscriptionPlanRequest $request,
        SubscriptionPlan $subscriptionPlan,
        SaveSubscriptionPlan $saveSubscriptionPlan,
    ): RedirectResponse {
        $saveSubscriptionPlan->handle($subscriptionPlan, $request->validated(), $request->user(), $request);

        return back()->with('success', 'Plan subscription berhasil diperbarui.');
    }

    public function destroy(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        if ($subscriptionPlan->subscriptions()->exists()) {
            return back()->with('error', 'Plan yang sudah digunakan tidak dapat dihapus. Nonaktifkan plan sebagai gantinya.');
        }

        $subscriptionPlan->delete();

        activity('subscription')
            ->causedBy($request->user())
            ->performedOn($subscriptionPlan)
            ->event('plan_deleted')
            ->tap(function (Activity $activity) use ($request): void {
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log('Subscription plan deleted');

        return back()->with('success', 'Plan subscription berhasil dihapus.');
    }
}
