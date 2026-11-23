<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancialAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreFinancialAccountRequest;
use App\Http\Requests\User\UpdateFinancialAccountRequest;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function __construct(private FinancialAccountBalanceService $balanceService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FinancialAccount::class);
        $account = $request->attributes->get('activeAccount');
        $financialAccounts = $account->financialAccounts()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return Inertia::render('User/FinancialAccounts/Index', [
            'financialAccounts' => $financialAccounts->map(fn (FinancialAccount $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'institution_name' => $item->institution_name,
                'account_number' => $item->account_number,
                'account_holder_name' => $item->account_holder_name,
                'opening_balance' => $item->opening_balance,
                'current_balance' => $item->current_balance,
                'currency' => $item->currency,
                'color' => $item->color,
                'icon' => $item->icon,
                'is_active' => $item->is_active,
                'include_in_total' => $item->include_in_total,
                'notes' => $item->notes,
            ]),
            'summary' => [
                'total_balance' => $financialAccounts->where('is_active', true)->where('include_in_total', true)->sum('current_balance'),
                'active_count' => $financialAccounts->where('is_active', true)->count(),
                'total_count' => $financialAccounts->count(),
            ],
            'types' => collect(FinancialAccountType::cases())->map(fn (FinancialAccountType $type) => [
                'value' => $type->value,
                'title' => $type->label(),
            ]),
        ]);
    }

    public function store(StoreFinancialAccountRequest $request): RedirectResponse
    {
        $this->balanceService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return back()->with('success', 'Rekening keuangan berhasil ditambahkan.');
    }

    public function update(UpdateFinancialAccountRequest $request, FinancialAccount $financialAccount): RedirectResponse
    {
        $this->balanceService->update($financialAccount, $request->user(), $request->validated(), $request);

        return back()->with('success', 'Rekening keuangan berhasil diperbarui.');
    }

    public function destroy(Request $request, FinancialAccount $financialAccount): RedirectResponse
    {
        Gate::authorize('delete', $financialAccount);
        $this->balanceService->delete($financialAccount, $request->user(), $request);

        return back()->with('success', 'Rekening keuangan berhasil dihapus.');
    }
}
