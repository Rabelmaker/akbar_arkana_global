<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /** @param array<string, mixed> $filters */
    public function build(Account $account, array $filters): array
    {
        $query = $this->query($account, $filters);
        $summary = $this->summary(clone $query);
        $comparisonFilters = $this->previousPeriodFilters($filters);
        $previousSummary = $this->summary($this->query($account, $comparisonFilters));

        return [
            'period' => [
                'from' => $filters['date_from'],
                'to' => $filters['date_to'],
                'days' => CarbonImmutable::parse($filters['date_from'])
                    ->diffInDays(CarbonImmutable::parse($filters['date_to'])) + 1,
            ],
            'summary' => $summary,
            'cashFlow' => $this->cashFlow(clone $query, $filters),
            'categories' => $this->categoryBreakdown(clone $query),
            'accounts' => $this->accountBreakdown(clone $query),
            'balances' => $account->financialAccounts()
                ->where('include_in_total', true)
                ->orderByDesc('current_balance')
                ->get(['id', 'name', 'type', 'current_balance', 'color', 'icon'])
                ->map(fn ($financialAccount) => [
                    'id' => $financialAccount->id,
                    'name' => $financialAccount->name,
                    'type' => $financialAccount->type->value,
                    'balance' => (float) $financialAccount->current_balance,
                    'color' => $financialAccount->color,
                    'icon' => $financialAccount->icon,
                ])->values(),
            'budgets' => $this->budgetPerformance($account, $filters),
            'comparison' => [
                'period' => [
                    'from' => $comparisonFilters['date_from'],
                    'to' => $comparisonFilters['date_to'],
                ],
                'current' => $summary,
                'previous' => $previousSummary,
                'changes' => [
                    'income' => $this->percentageChange($summary['income'], $previousSummary['income']),
                    'expense' => $this->percentageChange($summary['expense'], $previousSummary['expense']),
                    'net' => $this->percentageChange($summary['net'], $previousSummary['net']),
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    public function transactions(Account $account, array $filters): LengthAwarePaginator
    {
        return $this->query($account, $filters)
            ->with([
                'financialAccount:id,name',
                'destinationFinancialAccount:id,name',
                'category:id,name,color',
                'creator:id,name',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => $this->serializeTransaction($transaction));
    }

    /** @param array<string, mixed> $filters */
    public function exportRows(Account $account, array $filters): Collection
    {
        return $this->query($account, $filters)
            ->with([
                'financialAccount:id,name',
                'destinationFinancialAccount:id,name',
                'category:id,name',
                'creator:id,name',
            ])
            ->oldest('transaction_date')
            ->oldest('id')
            ->get()
            ->map(fn (Transaction $transaction) => $this->serializeTransaction($transaction));
    }

    /** @param array<string, mixed> $filters */
    private function query(Account $account, array $filters): Builder
    {
        return Transaction::query()
            ->where('account_id', $account->id)
            ->whereNull('reversed_transaction_id')
            ->whereDate('transaction_date', '>=', $filters['date_from'])
            ->whereDate('transaction_date', '<=', $filters['date_to'])
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['financial_account_id'] ?? null, fn (Builder $query, int $id) => $query->where(
                fn (Builder $accountQuery) => $accountQuery
                    ->where('financial_account_id', $id)
                    ->orWhere('destination_financial_account_id', $id),
            ))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['created_by'] ?? null, fn (Builder $query, int $id) => $query->where('created_by', $id))
            ->when($filters['amount_min'] ?? null, fn (Builder $query, mixed $amount) => $query->where('amount', '>=', $amount))
            ->when($filters['amount_max'] ?? null, fn (Builder $query, mixed $amount) => $query->where('amount', '<=', $amount));
    }

    private function summary(Builder $query): array
    {
        $result = $query->selectRaw("
            COUNT(*) as transaction_count,
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as expense,
            COALESCE(SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END), 0) as transfer,
            COALESCE(SUM(fee_amount), 0) as fees
        ")->first();

        $income = (float) $result->income;
        $expense = (float) $result->expense;

        return [
            'transaction_count' => (int) $result->transaction_count,
            'income' => $income,
            'expense' => $expense,
            'transfer' => (float) $result->transfer,
            'fees' => (float) $result->fees,
            'net' => $income - $expense,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function cashFlow(Builder $query, array $filters): array
    {
        $rows = $query
            ->selectRaw("
                DATE(transaction_date) as report_date,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as expense
            ")
            ->groupByRaw('DATE(transaction_date)')
            ->orderBy('report_date')
            ->get()
            ->keyBy('report_date');

        $from = CarbonImmutable::parse($filters['date_from']);
        $to = CarbonImmutable::parse($filters['date_to']);
        $periodDays = $from->diffInDays($to) + 1;
        $step = $periodDays > 120 ? 7 : 1;
        $series = [];

        for ($cursor = $from; $cursor->lte($to); $cursor = $cursor->addDays($step)) {
            $windowEnd = $cursor->addDays($step - 1)->min($to);
            $windowRows = $rows->filter(fn ($row, string $date) => $date >= $cursor->toDateString() && $date <= $windowEnd->toDateString());
            $income = (float) $windowRows->sum(fn ($row) => (float) $row->income);
            $expense = (float) $windowRows->sum(fn ($row) => (float) $row->expense);
            $series[] = [
                'date' => $cursor->toDateString(),
                'label' => $step === 1 ? $cursor->format('d M') : $cursor->format('d M').'–'.$windowEnd->format('d M'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        }

        return $series;
    }

    private function categoryBreakdown(Builder $query): array
    {
        $rows = $query
            ->selectRaw("
                category_id,
                COUNT(*) as transaction_count,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as expense
            ")
            ->groupBy('category_id')
            ->orderByDesc('expense')
            ->get();
        $categories = TransactionCategory::query()
            ->whereKey($rows->pluck('category_id')->filter())
            ->get(['id', 'name', 'color', 'icon'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($categories): array {
            $category = $categories->get($row->category_id);

            return [
                'id' => $row->category_id,
                'name' => $category?->name ?? 'Tanpa kategori',
                'color' => $category?->color ?? '#A8AAAE',
                'icon' => $category?->icon ?? 'ri-price-tag-3-line',
                'transaction_count' => (int) $row->transaction_count,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
            ];
        })->values()->all();
    }

    private function accountBreakdown(Builder $query): array
    {
        return $query
            ->selectRaw("
                financial_account_id,
                COUNT(*) as transaction_count,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END), 0) as transfer
            ")
            ->with('financialAccount:id,name,color,icon')
            ->groupBy('financial_account_id')
            ->orderByDesc('expense')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->financial_account_id,
                'name' => $row->financialAccount?->name ?? 'Rekening dihapus',
                'color' => $row->financialAccount?->color ?? '#A8AAAE',
                'icon' => $row->financialAccount?->icon ?? 'ri-wallet-3-line',
                'transaction_count' => (int) $row->transaction_count,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'transfer' => (float) $row->transfer,
            ])->values()->all();
    }

    /** @param array<string, mixed> $filters */
    private function budgetPerformance(Account $account, array $filters): array
    {
        return Budget::query()
            ->where('account_id', $account->id)
            ->whereDate('start_date', '<=', $filters['date_to'])
            ->whereDate('end_date', '>=', $filters['date_from'])
            ->withUsage()
            ->with(['category:id,name', 'financialAccount:id,name'])
            ->orderByDesc('start_date')
            ->limit(8)
            ->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'name' => $budget->name,
                'category' => $budget->category?->name,
                'financial_account' => $budget->financialAccount?->name,
                'amount' => (float) $budget->amount,
                'used' => $budget->usedAmount(),
                'remaining' => $budget->remainingAmount(),
                'percentage' => $budget->usagePercentage(),
                'health' => $budget->healthStatus(),
                'start_date' => $budget->start_date->toDateString(),
                'end_date' => $budget->end_date->toDateString(),
            ])->values()->all();
    }

    /** @param array<string, mixed> $filters */
    private function previousPeriodFilters(array $filters): array
    {
        $from = CarbonImmutable::parse($filters['date_from']);
        $to = CarbonImmutable::parse($filters['date_to']);
        $days = $from->diffInDays($to) + 1;

        return [
            ...$filters,
            'date_from' => $from->subDays($days)->toDateString(),
            'date_to' => $from->subDay()->toDateString(),
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return abs($current) < 0.01 ? 0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /** @return array<string, mixed> */
    private function serializeTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'date' => $transaction->transaction_date->format('Y-m-d H:i'),
            'type' => $transaction->type->value,
            'type_label' => $transaction->type->label(),
            'description' => $transaction->description,
            'financial_account' => $transaction->financialAccount?->name,
            'destination_account' => $transaction->destinationFinancialAccount?->name,
            'category' => $transaction->category?->name,
            'amount' => (float) $transaction->amount,
            'fee' => (float) $transaction->fee_amount,
            'total' => (float) $transaction->total_amount,
            'status' => $transaction->status->value,
            'status_label' => $transaction->status->label(),
            'source' => $transaction->source->value,
            'creator' => $transaction->creator?->name,
        ];
    }
}
