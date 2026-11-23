<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

#[Signature('subscriptions:expire')]
#[Description('Mark subscriptions past their end date as expired')]
class ExpireSubscriptions extends Command
{
    public function handle(): int
    {
        $expiredCount = 0;

        Subscription::query()
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::GracePeriod,
            ])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->lazyById(200)
            ->each(function (Subscription $subscription) use (&$expiredCount): void {
                $subscription->update(['status' => SubscriptionStatus::Expired]);

                activity('subscription')
                    ->performedOn($subscription)
                    ->event('subscription_expired')
                    ->withProperties(['account_id' => $subscription->account_id])
                    ->tap(function (Activity $activity) use ($subscription): void {
                        $activity->account_id = $subscription->account_id;
                    })
                    ->log('Subscription expired');

                $expiredCount++;
            });

        $this->info("Expired {$expiredCount} subscription(s).");

        return self::SUCCESS;
    }
}
