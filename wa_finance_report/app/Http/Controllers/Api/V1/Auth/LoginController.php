<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\ApiTokenAbility;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();
        if (! $user || ! Hash::check($request->validated('password'), $user->password) || $user->status !== UserStatus::Active) {
            return ApiResponse::error('The provided credentials are invalid.', status: 422);
        }

        $account = $user->activeAccount;
        if (! $account || $account->status !== AccountStatus::Active || ! $user->accounts()->whereKey($account)->exists()) {
            return ApiResponse::error('No active account is available.', status: 403);
        }

        $subscription = $account->validSubscription()->with('plan')->first();
        if (! $subscription?->plan?->api_access) {
            return ApiResponse::error('API access is not available on the active plan.', status: 403);
        }

        $abilities = $request->validated('abilities') ?: ApiTokenAbility::values();
        $token = $user->createToken(
            $request->validated('device_name'),
            $abilities,
            $request->date('expires_at'),
        );

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ], 'Authentication successful.');
    }
}
