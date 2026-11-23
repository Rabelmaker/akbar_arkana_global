<?php

namespace App\Http\Controllers\User;

use App\Enums\BudgetStatus;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()->hasRole('super-admin')) {
            return redirect()->route('admin.dashboard');
        }

        $account = $request->attributes->get('activeAccount');
        $financialAccounts = $account->financialAccounts()
            ->where('is_active', true)
            ->orderByDesc('current_balance')
            ->get();
        $todayTransactions = $account->transactions()
            ->where('status', 'posted')
            ->whereNull('reversed_transaction_id')
            ->whereDate('transaction_date', today());
        $incomeToday = (clone $todayTransactions)->where('type', 'income')->sum('amount');
        $expenseToday = (clone $todayTransactions)->where('type', 'expense')->sum('total_amount');
        $currentBudgets = Budget::query()
            ->where('account_id', $account->id)
            ->where('status', BudgetStatus::Active->value)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->withUsage()
            ->get();

        return Inertia::render('User/Dashboard', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency,
                'timezone' => $account->timezone,
            ],
            'metrics' => [
                'balance' => (string) $financialAccounts->where('include_in_total', true)->sum('current_balance'),
                'income_today' => (string) $incomeToday,
                'expense_today' => (string) $expenseToday,
                'net_cash_flow' => (string) ((float) $incomeToday - (float) $expenseToday),
                'financial_account_count' => $financialAccounts->count(),
                'category_count' => $account->transactionCategories()->where('is_active', true)->count(),
            ],
            'financialAccounts' => $financialAccounts->take(5)->map(fn ($financialAccount) => [
                'id' => $financialAccount->id,
                'name' => $financialAccount->name,
                'type' => $financialAccount->type->label(),
                'balance' => $financialAccount->current_balance,
                'color' => $financialAccount->color,
                'icon' => $financialAccount->icon,
            ]),
            'budgetSummary' => [
                'allocated' => $currentBudgets->sum(fn (Budget $budget) => (float) $budget->amount),
                'used' => $currentBudgets->sum(fn (Budget $budget) => $budget->usedAmount()),
                'remaining' => $currentBudgets->sum(fn (Budget $budget) => $budget->remainingAmount()),
                'alerts' => $currentBudgets->filter(fn (Budget $budget) => in_array($budget->healthStatus(), ['warning', 'exceeded'], true))->count(),
                'active' => $currentBudgets->count(),
            ],
        ]);
    }
}
