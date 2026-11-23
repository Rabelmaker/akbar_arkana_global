<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreFinancialAccountRequest;
use App\Http\Requests\User\UpdateFinancialAccountRequest;
use App\Http\Resources\V1\FinancialAccountResource;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountBalanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FinancialAccountController extends Controller
{
    public function __construct(private FinancialAccountBalanceService $balanceService) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FinancialAccount::class);
        $items = $request->attributes->get('activeAccount')
            ->financialAccounts()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 15), 50));

        return ApiResponse::paginated($items, FinancialAccountResource::class, 'Financial accounts retrieved successfully.');
    }

    public function store(StoreFinancialAccountRequest $request): JsonResponse
    {
        $item = $this->balanceService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return ApiResponse::success((new FinancialAccountResource($item))->resolve(), 'Financial account created successfully.', status: 201);
    }

    public function show(Request $request, FinancialAccount $financialAccount): JsonResponse
    {
        Gate::authorize('view', $financialAccount);

        return ApiResponse::success((new FinancialAccountResource($financialAccount))->resolve(), 'Financial account retrieved successfully.');
    }

    public function update(UpdateFinancialAccountRequest $request, FinancialAccount $financialAccount): JsonResponse
    {
        $item = $this->balanceService->update($financialAccount, $request->user(), $request->validated(), $request);

        return ApiResponse::success((new FinancialAccountResource($item))->resolve(), 'Financial account updated successfully.');
    }

    public function destroy(Request $request, FinancialAccount $financialAccount): JsonResponse
    {
        Gate::authorize('delete', $financialAccount);
        $this->balanceService->delete($financialAccount, $request->user(), $request);

        return ApiResponse::success(message: 'Financial account deleted successfully.');
    }
}
