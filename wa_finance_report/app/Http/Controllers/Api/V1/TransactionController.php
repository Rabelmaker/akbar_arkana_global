<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListTransactionsRequest;
use App\Http\Requests\User\StoreTransactionRequest;
use App\Http\Requests\User\TransactionLifecycleRequest;
use App\Http\Requests\User\UpdateTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(ListTransactionsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Transaction::query()
            ->where('account_id', $request->attributes->get('activeAccount')->id)
            ->with(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);
        $this->applyFilters($query, $filters);

        return ApiResponse::paginated(
            $query->latest('transaction_date')->latest('id')->paginate($filters['per_page'] ?? 15),
            TransactionResource::class,
            'Transactions retrieved successfully.',
        );
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $item = $this->transactionService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
            TransactionSource::Api,
        )->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);

        return ApiResponse::success((new TransactionResource($item))->resolve(), 'Transaction created successfully.', status: 201);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('view', $transaction);

        return ApiResponse::success(
            (new TransactionResource($transaction->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name'])))->resolve(),
            'Transaction retrieved successfully.',
        );
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $item = $this->transactionService
            ->update($transaction, $request->user(), $request->validated(), $request)
            ->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);

        return ApiResponse::success((new TransactionResource($item))->resolve(), 'Transaction updated successfully.');
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('delete', $transaction);
        $this->transactionService->delete($transaction, $request->user(), $request);

        return ApiResponse::success(message: 'Transaction deleted successfully.');
    }

    public function post(TransactionLifecycleRequest $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('post', $transaction);
        $item = $this->transactionService
            ->post($transaction, $request->user(), $request)
            ->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);

        return ApiResponse::success((new TransactionResource($item))->resolve(), 'Transaction posted successfully.');
    }

    public function cancel(TransactionLifecycleRequest $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('cancel', $transaction);
        $item = $this->transactionService
            ->cancel($transaction, $request->user(), $request, $request->validated('reason'))
            ->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);

        return ApiResponse::success((new TransactionResource($item))->resolve(), 'Transaction cancelled successfully.');
    }

    public function reverse(TransactionLifecycleRequest $request, Transaction $transaction): JsonResponse
    {
        Gate::authorize('reverse', $transaction);
        $item = $this->transactionService
            ->reverse($transaction, $request->user(), $request, $request->validated('reason'))
            ->load(['financialAccount:id,name', 'destinationFinancialAccount:id,name', 'category:id,name']);

        return ApiResponse::success((new TransactionResource($item))->resolve(), 'Transaction reversed successfully.', status: 201);
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('merchant', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%"),
            ))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['financial_account_id'] ?? null, fn (Builder $query, int $id) => $query->where('financial_account_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['amount_min'] ?? null, fn (Builder $query, mixed $amount) => $query->where('amount', '>=', $amount))
            ->when($filters['amount_max'] ?? null, fn (Builder $query, mixed $amount) => $query->where('amount', '<=', $amount));
    }
}
