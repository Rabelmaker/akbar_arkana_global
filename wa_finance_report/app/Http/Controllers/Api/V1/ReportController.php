<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ReportFilterRequest;
use App\Services\FinancialReportService;
use App\Support\ApiMoney;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private FinancialReportService $reports) {}

    public function summary(ReportFilterRequest $request): JsonResponse
    {
        return $this->part($request, 'summary', 'Report summary retrieved successfully.');
    }

    public function cashFlow(ReportFilterRequest $request): JsonResponse
    {
        return $this->part($request, 'cashFlow', 'Cash flow report retrieved successfully.');
    }

    public function income(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->report($request);

        return ApiResponse::success(ApiMoney::normalize([
            'period' => $report['period'],
            'total' => number_format($report['summary']['income'], 2, '.', ''),
            'categories' => collect($report['categories'])->where('income', '>', 0)->values(),
            'cash_flow' => $report['cashFlow'],
        ]), 'Income report retrieved successfully.');
    }

    public function expenses(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->report($request);

        return ApiResponse::success(ApiMoney::normalize([
            'period' => $report['period'],
            'total' => number_format($report['summary']['expense'], 2, '.', ''),
            'categories' => collect($report['categories'])->where('expense', '>', 0)->values(),
            'cash_flow' => $report['cashFlow'],
        ]), 'Expense report retrieved successfully.');
    }

    public function categories(ReportFilterRequest $request): JsonResponse
    {
        return $this->part($request, 'categories', 'Category report retrieved successfully.');
    }

    public function financialAccounts(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->report($request);

        return ApiResponse::success(ApiMoney::normalize([
            'activity' => $report['accounts'],
            'current_balances' => $report['balances'],
        ]), 'Financial account report retrieved successfully.');
    }

    public function budgets(ReportFilterRequest $request): JsonResponse
    {
        return $this->part($request, 'budgets', 'Budget report retrieved successfully.');
    }

    public function export(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->report($request);
        $rows = $this->reports->exportRows(
            $request->attributes->get('activeAccount'),
            $request->validated(),
        );

        return ApiResponse::success(ApiMoney::normalize([
            'report_name' => 'Laporan Keuangan',
            'period' => ['start' => $report['period']['from'], 'end' => $report['period']['to']],
            'summary' => [
                'total_income' => number_format($report['summary']['income'], 2, '.', ''),
                'total_expense' => number_format($report['summary']['expense'], 2, '.', ''),
                'net_cash_flow' => number_format($report['summary']['net'], 2, '.', ''),
            ],
            'columns' => ['number', 'date', 'type', 'description', 'account', 'category', 'amount', 'fee', 'total', 'status', 'source'],
            'rows' => $rows,
        ]), 'Structured report export retrieved successfully.');
    }

    private function part(ReportFilterRequest $request, string $key, string $message): JsonResponse
    {
        $report = $this->report($request);

        return ApiResponse::success(ApiMoney::normalize([
            'period' => $report['period'],
            $key => $report[$key],
        ]), $message);
    }

    /** @return array<string, mixed> */
    private function report(ReportFilterRequest $request): array
    {
        return $this->reports->build(
            $request->attributes->get('activeAccount'),
            $request->validated(),
        );
    }
}
