<?php

namespace App\Actions\Subscriptions;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ProvisionDefaultTrial
{
    public function handle(Account $account, User $actor): ?Subscription
    {
        if ($account->subscriptions()->exists()) {
            return $account->latestSubscription;
        }

        $plan = SubscriptionPlan::query()
            ->where('code', 'trial-14')
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return null;
        }

        $subscription = $account->subscriptions()->create([
            'plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(($plan->duration_days ?? 14) - 1),
            'status' => SubscriptionStatus::Trial,
            'price' => 0,
            'payment_status' => PaymentStatus::Waived,
            'auto_renew' => false,
            'notes' => 'Default trial provisioned automatically.',
            'created_by' => $actor->id,
        ]);

        activity('subscription')
            ->causedBy($actor)
            ->performedOn($subscription)
            ->event('trial_provisioned')
            ->withProperties(['account_id' => $account->id, 'plan_id' => $plan->id])
            ->tap(function (Activity $activity) use ($account): void {
                $activity->account_id = $account->id;
            })
            ->log('Default trial provisioned');

        return $subscription;
    }
}
