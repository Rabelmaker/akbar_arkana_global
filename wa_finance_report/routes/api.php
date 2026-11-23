<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\UserController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\FinancialAccountController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransactionCategoryController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Support\ApiTokenAbility;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', LoginController::class)->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('/auth/logout', LogoutController::class);
        Route::get('/auth/user', UserController::class);

        Route::middleware(['active.account', 'api.access'])->group(function (): void {
            Route::get('/account', [AccountController::class, 'show']);
            Route::get('/account/subscription', [AccountController::class, 'subscription']);

            Route::middleware('abilities:'.ApiTokenAbility::FinancialAccountsRead)->group(function (): void {
                Route::get('/financial-accounts', [FinancialAccountController::class, 'index']);
                Route::get('/financial-accounts/{financial_account}', [FinancialAccountController::class, 'show']);
            });
            Route::middleware(['active.subscription', 'abilities:'.ApiTokenAbility::FinancialAccountsWrite])->group(function (): void {
                Route::post('/financial-accounts', [FinancialAccountController::class, 'store']);
                Route::put('/financial-accounts/{financial_account}', [FinancialAccountController::class, 'update']);
                Route::delete('/financial-accounts/{financial_account}', [FinancialAccountController::class, 'destroy']);
            });

            Route::middleware('abilities:'.ApiTokenAbility::CategoriesRead)->group(function (): void {
                Route::get('/categories', [TransactionCategoryController::class, 'index']);
                Route::get('/categories/{transaction_category}', [TransactionCategoryController::class, 'show']);
            });
            Route::middleware(['active.subscription', 'abilities:'.ApiTokenAbility::CategoriesWrite])->group(function (): void {
                Route::post('/categories', [TransactionCategoryController::class, 'store']);
                Route::put('/categories/{transaction_category}', [TransactionCategoryController::class, 'update']);
                Route::delete('/categories/{transaction_category}', [TransactionCategoryController::class, 'destroy']);
            });

            Route::middleware('abilities:'.ApiTokenAbility::TransactionsRead)->group(function (): void {
                Route::get('/transactions', [TransactionController::class, 'index']);
                Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
            });
            Route::middleware(['active.subscription', 'abilities:'.ApiTokenAbility::TransactionsWrite])->group(function (): void {
                Route::post('/transactions', [TransactionController::class, 'store']);
                Route::put('/transactions/{transaction}', [TransactionController::class, 'update']);
                Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);
                Route::post('/transactions/{transaction}/post', [TransactionController::class, 'post']);
                Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);
                Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse']);
            });

            Route::middleware('abilities:'.ApiTokenAbility::BudgetsRead)->group(function (): void {
                Route::get('/budgets', [BudgetController::class, 'index']);
                Route::get('/budgets/{budget}', [BudgetController::class, 'show']);
            });
            Route::middleware(['active.subscription', 'abilities:'.ApiTokenAbility::BudgetsWrite])->group(function (): void {
                Route::post('/budgets', [BudgetController::class, 'store']);
                Route::put('/budgets/{budget}', [BudgetController::class, 'update']);
                Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy']);
            });

            Route::prefix('reports')->middleware('abilities:'.ApiTokenAbility::ReportsRead)->group(function (): void {
                Route::get('/summary', [ReportController::class, 'summary']);
                Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
                Route::get('/income', [ReportController::class, 'income']);
                Route::get('/expenses', [ReportController::class, 'expenses']);
                Route::get('/categories', [ReportController::class, 'categories']);
                Route::get('/financial-accounts', [ReportController::class, 'financialAccounts']);
                Route::get('/budgets', [ReportController::class, 'budgets']);
                Route::get('/export', [ReportController::class, 'export']);
            });

            Route::prefix('integrations')
                ->middleware(['active.subscription', 'abilities:'.ApiTokenAbility::IntegrationsWrite, 'throttle:integrations'])
                ->group(function (): void {
                    Route::post('/transactions', [IntegrationController::class, 'transaction']);
                    Route::get('/reports/summary', [IntegrationController::class, 'reportSummary']);
                    Route::get('/reports/transactions', [IntegrationController::class, 'reportTransactions']);
                    Route::get('/budgets/summary', [IntegrationController::class, 'budgetSummary']);
                    Route::get('/financial-accounts/balances', [IntegrationController::class, 'balances']);
                });
        });
    });
});
