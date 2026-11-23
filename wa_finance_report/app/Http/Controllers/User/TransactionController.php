<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListTransactionsRequest;
use App\Http\Requests\User\StoreTransactionRequest;
use App\Http\Requests\User\TransactionLifecycleRequest;
use App\Http\Requests\User\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(ListTransactionsRequest $request): Response
    {
        $account = $request->attributes->get('activeAccount');
        $filters = $request->validated();
        $baseQuery = Transaction::query()
            ->where('account_id', $account->id)
            ->with([
                'financialAccount:id,name,color,icon',
                'destinationFinancialAccount:id,name,color,icon',
                'category:id,name,color,icon',
                'attachments:id,transaction_id,original_name,mime_type,size',
            ]);
        $this->applyFilters($baseQuery, $filters);
        $transactions = $baseQuery
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => $this->serialize($transaction));

        $monthQuery = $account->transactions()
            ->where('status', 'posted')
            ->whereNull('reversed_transaction_id')
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()]);

        return Inertia::render('User/Transactions/Index', [
            'transactions' => $transactions,
            'financialAccounts' => $account->financialAccounts()
                ->where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'current_balance', 'color', 'icon']),
            'categories' => $account->transactionCategories()
                ->where('is_active', true)->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'type', 'color', 'icon']),
            'filters' => $filters,
            'summary' => [
                'income' => (clone $monthQuery)->where('type', 'income')->sum('amount'),
                'expense' => (clone $monthQuery)->where('type', 'expense')->sum('total_amount'),
                'transfer' => (clone $monthQuery)->where('type', 'transfer')->sum('amount'),
                'drafts' => $account->transactions()->where('status', 'draft')->count(),
            ],
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $this->transactionService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return back()->with('success', 'Transaksi berhasil disimpan.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->update($transaction, $request->user(), $request->validated(), $request);

        return back()->with('success', 'Draft transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);
        $this->transactionService->delete($transaction, $request->user(), $request);

        return back()->with('success', 'Draft transaksi berhasil dihapus.');
    }

    public function post(TransactionLifecycleRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('post', $transaction);
        $this->transactionService->post($transaction, $request->user(), $request);

        return back()->with('success', 'Transaksi diposting dan saldo telah diperbarui.');
    }

    public function cancel(TransactionLifecycleRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('cancel', $transaction);
        $this->transactionService->cancel($transaction, $request->user(), $request, $request->validated('reason'));

        return back()->with('success', 'Transaksi dibatalkan dan saldo dikembalikan.');
    }

    public function reverse(TransactionLifecycleRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('reverse', $transaction);
        $this->transactionService->reverse($transaction, $request->user(), $request, $request->validated('reason'));

        return back()->with('success', 'Transaksi reversal berhasil dibuat.');
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(
                fn (Builder $inner) => $inner
                    ->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('merchant', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%"),
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $q, string $source) => $q->where('source', $source))
            ->when($filters['financial_account_id'] ?? null, fn (Builder $q, int $id) => $q->where('financial_account_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $id) => $q->where('category_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $date) => $q->whereDate('transaction_date', '<=', $date))
            ->when($filters['amount_min'] ?? null, fn (Builder $q, mixed $amount) => $q->where('amount', '>=', $amount))
            ->when($filters['amount_max'] ?? null, fn (Builder $q, mixed $amount) => $q->where('amount', '<=', $amount));
    }

    /** @return array<string, mixed> */
    private function serialize(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'transaction_number' => $transaction->transaction_number,
            'type' => $transaction->type->value,
            'type_label' => $transaction->type->label(),
            'transaction_date' => $transaction->transaction_date->toIso8601String(),
            'financial_account_id' => $transaction->financial_account_id,
            'financial_account' => $transaction->financialAccount,
            'destination_financial_account_id' => $transaction->destination_financial_account_id,
            'destination_financial_account' => $transaction->destinationFinancialAccount,
            'category_id' => $transaction->category_id,
            'category' => $transaction->category,
            'amount' => $transaction->amount,
            'fee_amount' => $transaction->fee_amount,
            'total_amount' => $transaction->total_amount,
            'description' => $transaction->description,
            'merchant' => $transaction->merchant,
            'reference_number' => $transaction->reference_number,
            'notes' => $transaction->notes,
            'payment_method' => $transaction->payment_method,
            'status' => $transaction->status->value,
            'status_label' => $transaction->status->label(),
            'source' => $transaction->source->value,
            'reversed_transaction_id' => $transaction->reversed_transaction_id,
            'cancellation_reason' => $transaction->cancellation_reason,
            'attachments' => $transaction->attachments,
        ];
    }
}
