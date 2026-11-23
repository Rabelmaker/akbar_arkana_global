<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ReportFilterRequest;
use App\Services\FinancialReportService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private FinancialReportService $reports,
        private ReportExportService $exports,
    ) {}

    public function index(ReportFilterRequest $request): Response|RedirectResponse
    {
        if ($request->user()->hasRole('super-admin')) {
            return redirect()->route('admin.dashboard');
        }

        $account = $request->attributes->get('activeAccount');
        $filters = $request->validated();

        return Inertia::render('User/Reports/Index', [
            'report' => $this->reports->build($account, $filters),
            'transactions' => $this->reports->transactions($account, $filters),
            'filters' => $filters,
            'financialAccounts' => $account->financialAccounts()
                ->orderBy('name')->get(['id', 'name']),
            'categories' => $account->transactionCategories()
                ->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'users' => $account->users()->orderBy('name')->get(['users.id', 'users.name']),
            'filterOptions' => [
                'types' => collect(TransactionType::cases())->map(fn (TransactionType $type) => ['value' => $type->value, 'title' => $type->label()]),
                'statuses' => collect(TransactionStatus::cases())->map(fn (TransactionStatus $status) => ['value' => $status->value, 'title' => $status->label()]),
                'sources' => collect(TransactionSource::cases())->map(fn (TransactionSource $source) => ['value' => $source->value, 'title' => ucfirst($source->value)]),
            ],
            'canExport' => $this->canExport($request),
        ]);
    }

    public function print(ReportFilterRequest $request): HttpResponse
    {
        abort_if($request->user()->hasRole('super-admin'), 403);
        $account = $request->attributes->get('activeAccount');
        $filters = $request->validated();

        return response()->view('reports.document', [
            'account' => $account,
            'filters' => $filters,
            'report' => $this->reports->build($account, $filters),
            'rows' => $this->reports->exportRows($account, $filters),
            'printMode' => true,
        ]);
    }

    public function csv(ReportFilterRequest $request): StreamedResponse
    {
        abort_if($request->user()->hasRole('super-admin'), 403);
        abort_unless($this->canExport($request), 403, 'Paket aktif tidak mendukung ekspor laporan.');

        return $this->exports->csv(
            $request->attributes->get('activeAccount'),
            $request->validated(),
        );
    }

    public function pdf(ReportFilterRequest $request): HttpResponse
    {
        abort_if($request->user()->hasRole('super-admin'), 403);
        abort_unless($this->canExport($request), 403, 'Paket aktif tidak mendukung ekspor laporan.');

        return $this->exports->pdf(
            $request->attributes->get('activeAccount'),
            $request->validated(),
        );
    }

    public function xlsx(ReportFilterRequest $request): JsonResponse
    {
        abort_if($request->user()->hasRole('super-admin'), 403);
        abort_unless($this->canExport($request), 403, 'Paket aktif tidak mendukung ekspor laporan.');
        $account = $request->attributes->get('activeAccount');
        $filters = $request->validated();
        $report = $this->reports->build($account, $filters);

        return response()->json([
            'filename' => "laporan-keuangan-{$filters['date_from']}-{$filters['date_to']}.xlsx",
            'account' => $account->name,
            'period' => $report['period'],
            'summary' => $report['summary'],
            'transactions' => $this->reports->exportRows($account, $filters),
        ]);
    }

    private function canExport(ReportFilterRequest $request): bool
    {
        if (! $request->user()->can('export reports')) {
            return false;
        }

        return (bool) $request->attributes->get('activeAccount')
            ->validSubscription()
            ->whereHas('plan', fn ($query) => $query->where('report_export', true))
            ->exists();
    }
}
