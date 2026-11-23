<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $account = $request->attributes->get('activeAccount');

        return ApiResponse::success([
            ...$account->only(['id', 'name', 'slug', 'email', 'phone', 'timezone', 'currency', 'locale']),
            'status' => $account->status->value,
        ], 'Account retrieved successfully.');
    }

    public function subscription(Request $request): JsonResponse
    {
        $subscription = $request->attributes->get('activeAccount')
            ->validSubscription()
            ->with('plan')
            ->first();

        return ApiResponse::success($subscription ? [
            'id' => $subscription->id,
            'status' => $subscription->status->value,
            'start_date' => $subscription->start_date->toDateString(),
            'end_date' => $subscription->end_date?->toDateString(),
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'code' => $subscription->plan->code,
                'api_access' => $subscription->plan->api_access,
                'report_export' => $subscription->plan->report_export,
                'max_financial_accounts' => $subscription->plan->max_financial_accounts,
                'max_monthly_transactions' => $subscription->plan->max_monthly_transactions,
            ],
        ] : null, 'Subscription retrieved successfully.');
    }
}
