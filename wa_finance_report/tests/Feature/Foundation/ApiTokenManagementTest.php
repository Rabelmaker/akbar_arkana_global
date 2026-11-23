<?php

use App\Models\Account;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ApiTokenAbility;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function apiTokenOwner(bool $apiAccess = true): array
{
    $account = Account::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->accounts()->attach($account, [
        'role' => 'account-owner',
        'is_owner' => true,
        'joined_at' => now(),
    ]);
    $user->forceFill(['active_account_id' => $account->id])->save();
    $subscription = Subscription::factory()->for($account)->create();
    $subscription->plan()->update(['api_access' => $apiAccess]);

    return [$user, $account];
}

it('shows only tokens owned by the signed in user', function () {
    [$user] = apiTokenOwner();
    $ownToken = $user->createToken('Automation WA', [ApiTokenAbility::IntegrationsWrite])->accessToken;
    User::factory()->create()->createToken('Token Orang Lain', ['*']);

    $this->actingAs($user)
        ->get(route('api-tokens.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/ApiTokens/Index')
            ->where('apiAccess', true)
            ->has('tokens', 1)
            ->where('tokens.0.id', $ownToken->id)
            ->where('tokens.0.name', 'Automation WA')
            ->where('tokens.0.abilities.0', ApiTokenAbility::IntegrationsWrite));
});

it('creates a scoped token, reveals it once, and writes an account audit record', function () {
    [$user, $account] = apiTokenOwner();

    $response = $this->actingAs($user)->post(route('api-tokens.store'), [
        'name' => 'n8n Production',
        'abilities' => [
            ApiTokenAbility::TransactionsRead,
            ApiTokenAbility::IntegrationsWrite,
        ],
        'expires_at' => today()->addMonth()->toDateString(),
    ]);

    $response->assertRedirect()->assertSessionHas('new_token');
    $plainToken = session('new_token');
    $storedToken = $user->tokens()->sole();

    expect($plainToken)->toBeString()->toContain('|')
        ->and($storedToken->name)->toBe('n8n Production')
        ->and($storedToken->abilities)->toBe([
            ApiTokenAbility::TransactionsRead,
            ApiTokenAbility::IntegrationsWrite,
        ])
        ->and($storedToken->token)->not->toBe($plainToken);

    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'causer_id' => $user->id,
        'event' => 'created',
        'description' => 'API token created',
    ]);
});

it('revokes only a token belonging to the signed in user', function () {
    [$user, $account] = apiTokenOwner();
    $ownToken = $user->createToken('Temporary', [ApiTokenAbility::ReportsRead])->accessToken;
    $otherToken = User::factory()->create()->createToken('Foreign', ['*'])->accessToken;

    $this->actingAs($user)
        ->delete(route('api-tokens.destroy', $otherToken))
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('api-tokens.destroy', $ownToken))
        ->assertRedirect();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ownToken->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
    $this->assertDatabaseHas('activity_log', [
        'account_id' => $account->id,
        'event' => 'revoked',
        'description' => 'API token revoked',
    ]);
});

it('keeps token settings readable but blocks token creation when the plan has no api access', function () {
    [$user] = apiTokenOwner(false);

    $this->actingAs($user)
        ->get(route('api-tokens.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('apiAccess', false));

    $this->actingAs($user)
        ->post(route('api-tokens.store'), [
            'name' => 'Blocked token',
            'abilities' => [ApiTokenAbility::ReportsRead],
        ])
        ->assertForbidden();
});
