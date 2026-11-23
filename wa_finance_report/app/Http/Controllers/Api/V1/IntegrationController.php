<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IntegrationTransactionRequest;
use App\Http\Requests\User\ReportFilterRequest;
use App\Models\Budget;
use App\Services\FinancialReportService;
use App\Services\IntegrationTransactionService;
use App\Support\ApiMoney;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct(
        private IntegrationTransactionService $integrations,
        private FinancialReportService $reports,
    ) {}

    public function transaction(IntegrationTransactionRequest $request): JsonResponse
    {
        $result = $this->integrations->process(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return ApiResponse::success(
            $result['data'],
            $result['status'] === 202 ? 'Transaction requires review.' : 'Transaction processed successfully.',
            status: $result['status'],
        );
    }

    public function reportSummary(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->reports->build(
            $request->attributes->get('activeAccount'),
            $request->validated(),
        );

        return ApiResponse::success(ApiMoney::normalize([
            'period' => $report['period'],
            'summary' => $report['summary'],
        ]), 'Integration report summary retrieved successfully.');
    }

    public function reportTransactions(ReportFilterRequest $request): JsonResponse
    {
        return ApiResponse::success(
            ApiMoney::normalize($this->reports->exportRows(
                $request->attributes->get('activeAccount'),
                $request->validated(),
            )),
            'Integration report transactions retrieved successfully.',
        );
    }

    public function budgetSummary(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->where('account_id', $request->attributes->get('activeAccount')->id)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->withUsage()
            ->get();

        return ApiResponse::success([
            'allocated' => number_format($budgets->sum(fn (Budget $budget) => (float) $budget->amount), 2, '.', ''),
            'used' => number_format($budgets->sum(fn (Budget $budget) => $budget->usedAmount()), 2, '.', ''),
            'remaining' => number_format($budgets->sum(fn (Budget $budget) => $budget->remainingAmount()), 2, '.', ''),
            'active_budgets' => $budgets->count(),
        ], 'Integration budget summary retrieved successfully.');
    }

    public function balances(Request $request): JsonResponse
    {
        $rows = $request->attributes->get('activeAccount')
            ->financialAccounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance', 'currency'])
            ->map(fn ($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'balance' => $account->current_balance,
                'currency' => $account->currency,
            ]);

        return ApiResponse::success($rows, 'Integration balances retrieved successfully.');
    }
}
