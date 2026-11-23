<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreTransactionCategoryRequest;
use App\Http\Requests\User\UpdateTransactionCategoryRequest;
use App\Http\Resources\V1\TransactionCategoryResource;
use App\Models\TransactionCategory;
use App\Services\TransactionCategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionCategoryController extends Controller
{
    public function __construct(private TransactionCategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', TransactionCategory::class);
        $items = $request->attributes->get('activeAccount')
            ->transactionCategories()
            ->with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 25), 50));

        return ApiResponse::paginated($items, TransactionCategoryResource::class, 'Categories retrieved successfully.');
    }

    public function store(StoreTransactionCategoryRequest $request): JsonResponse
    {
        $item = $this->categoryService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        )->load('parent:id,name');

        return ApiResponse::success((new TransactionCategoryResource($item))->resolve(), 'Category created successfully.', status: 201);
    }

    public function show(Request $request, TransactionCategory $transactionCategory): JsonResponse
    {
        Gate::authorize('view', $transactionCategory);

        return ApiResponse::success(
            (new TransactionCategoryResource($transactionCategory->load('parent:id,name')))->resolve(),
            'Category retrieved successfully.',
        );
    }

    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): JsonResponse
    {
        $item = $this->categoryService
            ->update($transactionCategory, $request->user(), $request->validated(), $request)
            ->load('parent:id,name');

        return ApiResponse::success((new TransactionCategoryResource($item))->resolve(), 'Category updated successfully.');
    }

    public function destroy(Request $request, TransactionCategory $transactionCategory): JsonResponse
    {
        Gate::authorize('delete', $transactionCategory);
        $this->categoryService->delete($transactionCategory, $request->user(), $request);

        return ApiResponse::success(message: 'Category deleted successfully.');
    }
}
