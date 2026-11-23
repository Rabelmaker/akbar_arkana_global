<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreApiTokenRequest;
use App\Support\ApiTokenAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\Models\Activity;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('access API token settings');
        $account = $request->attributes->get('activeAccount');

        return Inertia::render('User/ApiTokens/Index', [
            'tokens' => $request->user()->tokens()
                ->latest()
                ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
                ->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities ?? [],
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toDateString(),
                    'created_at' => $token->created_at->toIso8601String(),
                    'expired' => $token->expires_at?->isPast() ?? false,
                ]),
            'abilities' => collect(ApiTokenAbility::options())->map(
                fn (string $label, string $value) => ['value' => $value, 'label' => $label],
            )->values(),
            'apiAccess' => $account->validSubscription()
                ->whereHas('plan', fn ($query) => $query->where('api_access', true))
                ->exists(),
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $account = $request->attributes->get('activeAccount');
        abort_unless(
            $account->validSubscription()
                ->whereHas('plan', fn ($query) => $query->where('api_access', true))
                ->exists(),
            403,
            'Paket aktif tidak mendukung akses API.',
        );

        $token = $request->user()->createToken(
            $request->validated('name'),
            $request->validated('abilities'),
            $request->date('expires_at'),
        );

        activity('api_token')
            ->causedBy($request->user())
            ->event('created')
            ->tap(function (Activity $activity) use ($account): void {
                $activity->account_id = $account->id;
            })
            ->withProperties([
                'account_id' => $account->id,
                'token_id' => $token->accessToken->id,
                'abilities' => $request->validated('abilities'),
            ])
            ->log('API token created');

        return back()
            ->with('success', 'API token berhasil dibuat.')
            ->with('new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        Gate::authorize('access API token settings');
        abort_unless($token->tokenable_id === $request->user()->id && $token->tokenable_type === $request->user()::class, 404);
        $tokenId = $token->id;
        $token->delete();

        activity('api_token')
            ->causedBy($request->user())
            ->event('revoked')
            ->tap(function (Activity $activity) use ($request): void {
                $activity->account_id = $request->attributes->get('activeAccount')->id;
            })
            ->withProperties([
                'account_id' => $request->attributes->get('activeAccount')->id,
                'token_id' => $tokenId,
            ])
            ->log('API token revoked');

        return back()->with('success', 'API token berhasil dicabut.');
    }
}
