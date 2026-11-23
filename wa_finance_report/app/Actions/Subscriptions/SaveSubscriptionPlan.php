<?php

namespace App\Actions\Subscriptions;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SaveSubscriptionPlan
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        ?SubscriptionPlan $plan,
        array $attributes,
        User $actor,
        Request $request,
    ): SubscriptionPlan {
        $plan ??= new SubscriptionPlan;
        $oldValues = $plan->exists ? $plan->only(array_keys($attributes)) : [];
        $event = $plan->exists ? 'updated' : 'created';

        $plan->fill($attributes)->save();

        activity('subscription')
            ->causedBy($actor)
            ->performedOn($plan)
            ->event("plan_{$event}")
            ->withProperties([
                'old' => $oldValues,
                'attributes' => $plan->only(array_keys($attributes)),
            ])
            ->tap(function (Activity $activity) use ($request): void {
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log("Subscription plan {$event}");

        return $plan->refresh();
    }
}
