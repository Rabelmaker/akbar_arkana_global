<?php

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SaveSubscription
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        ?Subscription $subscription,
        array $attributes,
        User $actor,
        Request $request,
    ): Subscription {
        return DB::transaction(function () use ($subscription, $attributes, $actor, $request): Subscription {
            $subscription ??= new Subscription;
            $isNew = ! $subscription->exists;
            $oldValues = $isNew ? [] : $subscription->only(array_keys($attributes));

            if ($isNew && SubscriptionStatus::from($attributes['status'])->grantsAccess()) {
                Subscription::query()
                    ->where('account_id', $attributes['account_id'])
                    ->valid()
                    ->lockForUpdate()
                    ->get()
                    ->each(function (Subscription $current) use ($actor, $request): void {
                        $current->update(['status' => SubscriptionStatus::Cancelled]);
                        $this->logCancellation($current, $actor, $request);
                    });
            }

            $subscription->fill([
                ...$attributes,
                'created_by' => $subscription->created_by ?? $actor->id,
            ])->save();

            $event = $isNew ? 'created' : 'updated';

            activity('subscription')
                ->causedBy($actor)
                ->performedOn($subscription)
                ->event("subscription_{$event}")
                ->withProperties([
                    'account_id' => $subscription->account_id,
                    'old' => $oldValues,
                    'attributes' => $subscription->only(array_keys($attributes)),
                ])
                ->tap(function (Activity $activity) use ($subscription, $request): void {
                    $activity->account_id = $subscription->account_id;
                    $activity->ip_address = $request->ip();
                    $activity->user_agent = $request->userAgent();
                })
                ->log("Subscription {$event}");

            return $subscription->refresh();
        });
    }

    private function logCancellation(Subscription $subscription, User $actor, Request $request): void
    {
        activity('subscription')
            ->causedBy($actor)
            ->performedOn($subscription)
            ->event('subscription_cancelled')
            ->withProperties([
                'account_id' => $subscription->account_id,
                'reason' => 'replaced_by_new_subscription',
            ])
            ->tap(function (Activity $activity) use ($subscription, $request): void {
                $activity->account_id = $subscription->account_id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log('Subscription cancelled');
    }
}
