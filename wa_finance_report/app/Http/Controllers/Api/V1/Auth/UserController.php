<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: [
                ...$request->user()->only(['id', 'name', 'email', 'phone', 'status']),
                'active_account' => $request->attributes->get('activeAccount')?->only(['id', 'name', 'currency', 'timezone']),
                'token_abilities' => $request->user()->currentAccessToken()?->abilities ?? [],
            ],
            message: 'Authenticated user retrieved successfully.',
        );
    }
}
