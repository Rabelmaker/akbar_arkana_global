<?php

namespace App\Http\Controllers\User;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListBudgetsRequest;
use App\Http\Requests\User\StoreBudgetRequest;
use App\Http\Requests\User\UpdateBudgetRequest;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index(ListBudgetsRequest $request): Response
    {
        $account = $request->attributes->get('activeAccount');
        $filters = $request->validated();
        $query = Budget::query()
            ->where('account_id', $account->id)
            ->withUsage()
            ->with(['category:id,name,color,icon', 'financialAccount:id,name,color,icon']);
        $this->applyFilters($query, $filters);
        $budgets = $query->latest('start_date')->latest('id')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString()
            ->through(fn (Budget $budget) => $this->serialize($budget));

        $activeBudgets = Budget::query()
            ->where('account_id', $account->id)
            ->where('status', BudgetStatus::Active->value)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->withUsage()
            ->get();

        return Inertia::render('User/Budgets/Index', [
            'budgets' => $budgets,
            'filters' => $filters,
            'categories' => $account->transactionCategories()
                ->where('is_active', true)
                ->whereIn('type', ['expense', 'both'])
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'color', 'icon']),
            'financialAccounts' => $account->financialAccounts()
                ->where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'color', 'icon']),
            'periodTypes' => collect(BudgetPeriodType::cases())->map(fn (BudgetPeriodType $period) => [
                'value' => $period->value,
                'title' => $period->label(),
            ]),
            'summary' => [
                'allocated' => $activeBudgets->sum(fn (Budget $budget) => (float) $budget->amount),
                'used' => $activeBudgets->sum(fn (Budget $budget) => $budget->usedAmount()),
                'remaining' => $activeBudgets->sum(fn (Budget $budget) => $budget->remainingAmount()),
                'active' => $activeBudgets->count(),
                'warning' => $activeBudgets->filter(fn (Budget $budget) => $budget->healthStatus() === 'warning')->count(),
                'exceeded' => $activeBudgets->filter(fn (Budget $budget) => $budget->healthStatus() === 'exceeded')->count(),
            ],
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $this->budgetService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return back()->with('success', 'Budget berhasil dibuat.');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->budgetService->update($budget, $request->user(), $request->validated(), $request);

        return back()->with('success', 'Budget berhasil diperbarui.');
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('delete', $budget);
        $this->budgetService->delete($budget, $request->user(), $request);

        return back()->with('success', 'Budget berhasil dihapus.');
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['period_type'] ?? null, fn (Builder $q, string $period) => $q->where('period_type', $period))
            ->when($filters['date'] ?? null, fn (Builder $q, string $date) => $q
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date));
    }

    /** @return array<string, mixed> */
    private function serialize(Budget $budget): array
    {
        return [
            'id' => $budget->id,
            'name' => $budget->name,
            'category_id' => $budget->category_id,
            'category' => $budget->category,
            'financial_account_id' => $budget->financial_account_id,
            'financial_account' => $budget->financialAccount,
            'period_type' => $budget->period_type->value,
            'period_label' => $budget->period_type->label(),
            'start_date' => $budget->start_date->toDateString(),
            'end_date' => $budget->end_date->toDateString(),
            'amount' => $budget->amount,
            'used_amount' => $budget->usedAmount(),
            'remaining_amount' => $budget->remainingAmount(),
            'usage_percentage' => $budget->usagePercentage(),
            'alert_percentage' => $budget->alert_percentage,
            'health_status' => $budget->healthStatus(),
            'rollover_enabled' => $budget->rollover_enabled,
            'status' => $budget->status->value,
            'status_label' => $budget->status->label(),
            'notes' => $budget->notes,
        ];
    }
}
