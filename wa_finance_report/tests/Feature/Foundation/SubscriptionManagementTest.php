<?php

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubscriptionPlanSeeder;

beforeEach(function () {
    $this->seed([
        RolePermissionSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);
});

function subscriptionAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    return $admin;
}

it('provisions the default trial when a user registers', function () {
    $this->post('/register', [
        'name' => 'Pemilik Trial',
        'email' => 'trial@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $account = User::where('email', 'trial@example.com')->firstOrFail()->activeAccount;
    $subscription = $account->subscriptions()->with('plan')->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Trial)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Waived)
        ->and($subscription->plan->code)->toBe('trial-14')
        ->and($subscription->start_date->diffInDays($subscription->end_date))->toBe(13.0)
        ->and($account->hasValidSubscription())->toBeTrue();
});

it('allows a super admin to create and update a plan', function () {
    $admin = subscriptionAdmin();

    $this->actingAs($admin)
        ->post(route('admin.subscription-plans.store'), [
            'name' => 'Professional',
            'code' => 'Professional Plan',
            'description' => 'Plan untuk bisnis berkembang.',
            'price' => 249000,
            'billing_period' => 'monthly',
            'duration_days' => 30,
            'max_financial_accounts' => 10,
            'max_monthly_transactions' => 5000,
            'max_users' => 10,
            'api_access' => true,
            'whatsapp_integration' => false,
            'google_sheets_integration' => false,
            'report_export' => true,
            'is_active' => true,
            'sort_order' => 20,
        ])
        ->assertRedirect();

    $plan = SubscriptionPlan::where('code', 'professional-plan')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.subscription-plans.update', $plan), [
            'name' => 'Professional Plus',
            'code' => 'professional-plan',
            'description' => 'Plan diperbarui.',
            'price' => 299000,
            'billing_period' => 'monthly',
            'duration_days' => 30,
            'max_financial_accounts' => 15,
            'max_monthly_transactions' => 7500,
            'max_users' => 15,
            'api_access' => true,
            'whatsapp_integration' => false,
            'google_sheets_integration' => false,
            'report_export' => true,
            'is_active' => true,
            'sort_order' => 20,
        ])
        ->assertRedirect();

    expect($plan->fresh()->name)->toBe('Professional Plus')
        ->and($plan->fresh()->price)->toBe('299000.00');

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $admin->id,
        'subject_id' => $plan->id,
        'event' => 'plan_updated',
    ]);
});

it('forbids a regular user from managing subscription plans', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.subscription-plans.index'))
        ->assertForbidden();
});

it('assigns a subscription and cancels the previous entitlement atomically', function () {
    $admin = subscriptionAdmin();
    $account = Account::factory()->create();
    $trialPlan = SubscriptionPlan::where('code', 'trial-14')->firstOrFail();
    $basicPlan = SubscriptionPlan::where('code', 'basic-monthly')->firstOrFail();
    $oldSubscription = Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $trialPlan->id,
        'status' => SubscriptionStatus::Trial,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.subscriptions.store'), [
            'account_id' => $account->id,
            'plan_id' => $basicPlan->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(29)->toDateString(),
            'status' => 'active',
            'price' => 99000,
            'payment_status' => 'paid',
            'auto_renew' => true,
            'notes' => 'Upgrade ke paket basic.',
        ])
        ->assertRedirect();

    expect($oldSubscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($account->subscriptions()->count())->toBe(2)
        ->and($account->hasValidSubscription())->toBeTrue();

    $newSubscription = $account->subscriptions()->latest('id')->firstOrFail();

    expect($newSubscription->status)->toBe(SubscriptionStatus::Active)
        ->and($newSubscription->created_by)->toBe($admin->id);

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'event' => 'subscription_created',
        'subject_id' => $newSubscription->id,
    ]);
});

it('preserves a used plan by rejecting deletion', function () {
    $admin = subscriptionAdmin();
    $plan = SubscriptionPlan::where('code', 'basic-monthly')->firstOrFail();
    Subscription::factory()->create(['plan_id' => $plan->id]);

    $this->actingAs($admin)
        ->delete(route('admin.subscription-plans.destroy', $plan))
        ->assertSessionHas('error');

    $this->assertModelExists($plan);
});

it('expires ended subscriptions through the scheduled command', function () {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'start_date' => today()->subMonth(),
        'end_date' => today()->subDay(),
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutput('Expired 1 subscription(s).')
        ->assertSuccessful();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $subscription->account_id,
        'event' => 'subscription_expired',
        'subject_id' => $subscription->id,
    ]);
});
