<?php

use App\Enums\SubscriptionStatus;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    Route::middleware(['auth', 'active.account', 'active.subscription'])
        ->post('/_test/subscription-write', fn () => response()->json(['allowed' => true]));
});

function userWithSubscriptionAccount(Account $account): User
{
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();

    return $user;
}

it('allows mutations with an active subscription', function () {
    $account = Account::factory()->create();
    $user = userWithSubscriptionAccount($account);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'start_date' => today()->subDay(),
        'end_date' => today()->addMonth(),
    ]);

    $this->actingAs($user)
        ->postJson('/_test/subscription-write')
        ->assertSuccessful()
        ->assertJson(['allowed' => true]);
});

it('blocks mutations when the subscription is expired', function () {
    $account = Account::factory()->create();
    $user = userWithSubscriptionAccount($account);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->expired()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
    ]);

    $this->actingAs($user)
        ->postJson('/_test/subscription-write')
        ->assertPaymentRequired()
        ->assertJson([
            'message' => 'Subscription aktif diperlukan untuk melakukan perubahan.',
        ]);

    $this->actingAs($user)->get(route('dashboard'))->assertSuccessful();
});

it('blocks mutations when no subscription exists', function () {
    $account = Account::factory()->create();
    $user = userWithSubscriptionAccount($account);

    $this->actingAs($user)
        ->postJson('/_test/subscription-write')
        ->assertPaymentRequired();
});

it('allows a super admin to bypass subscription middleware', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->postJson('/_test/subscription-write')
        ->assertSuccessful();
});
