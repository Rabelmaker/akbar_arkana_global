<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListBudgetsRequest;
use App\Http\Requests\User\StoreBudgetRequest;
use App\Http\Requests\User\UpdateBudgetRequest;
use App\Http\Resources\V1\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index(ListBudgetsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Budget::query()
            ->where('account_id', $request->attributes->get('activeAccount')->id)
            ->withUsage()
            ->with(['category:id,name', 'financialAccount:id,name'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_type'] ?? null, fn (Builder $query, string $period) => $query->where('period_type', $period))
            ->latest('start_date')
            ->latest('id');

        return ApiResponse::paginated(
            $query->paginate($filters['per_page'] ?? 15),
            BudgetResource::class,
            'Budgets retrieved successfully.',
        );
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $item = $this->budgetService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        )->load(['category:id,name', 'financialAccount:id,name']);
        $item->used_amount = 0;

        return ApiResponse::success((new BudgetResource($item))->resolve(), 'Budget created successfully.', status: 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        Gate::authorize('view', $budget);
        $item = Budget::query()->whereKey($budget)->withUsage()->with(['category:id,name', 'financialAccount:id,name'])->firstOrFail();

        return ApiResponse::success((new BudgetResource($item))->resolve(), 'Budget retrieved successfully.');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $item = $this->budgetService
            ->update($budget, $request->user(), $request->validated(), $request)
            ->load(['category:id,name', 'financialAccount:id,name']);
        $item->used_amount = Budget::query()->whereKey($item)->withUsage()->value('used_amount');

        return ApiResponse::success((new BudgetResource($item))->resolve(), 'Budget updated successfully.');
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        Gate::authorize('delete', $budget);
        $this->budgetService->delete($budget, $request->user(), $request);

        return ApiResponse::success(message: 'Budget deleted successfully.');
    }
}
