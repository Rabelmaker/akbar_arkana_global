<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\AccountSettingsController;
use App\Http\Controllers\User\ActiveAccountController;
use App\Http\Controllers\User\ApiTokenController;
use App\Http\Controllers\User\BudgetController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\FinancialAccountController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\User\TransactionCategoryController;
use App\Http\Controllers\User\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]));

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', UserDashboardController::class)
        ->middleware('active.account')
        ->name('dashboard');

    Route::put('/account/active/{account}', ActiveAccountController::class)
        ->name('account.active.update');

    Route::middleware('active.account')->group(function (): void {
        Route::get('/account/settings', [AccountSettingsController::class, 'edit'])
            ->name('account.settings.edit');
        Route::patch('/account/settings', [AccountSettingsController::class, 'update'])
            ->name('account.settings.update');

        Route::get('/financial-accounts', [FinancialAccountController::class, 'index'])
            ->name('financial-accounts.index');
        Route::get('/transaction-categories', [TransactionCategoryController::class, 'index'])
            ->name('transaction-categories.index');
        Route::get('/transactions', [TransactionController::class, 'index'])
            ->name('transactions.index');
        Route::get('/budgets', [BudgetController::class, 'index'])
            ->name('budgets.index');
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/reports/print', [ReportController::class, 'print'])
            ->name('reports.print');
        Route::get('/api-tokens', [ApiTokenController::class, 'index'])
            ->name('api-tokens.index');
        Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
            ->name('api-tokens.destroy');

        Route::middleware('active.subscription')->group(function (): void {
            Route::post('/api-tokens', [ApiTokenController::class, 'store'])
                ->name('api-tokens.store');
            Route::get('/reports/export/csv', [ReportController::class, 'csv'])
                ->name('reports.export.csv');
            Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])
                ->name('reports.export.pdf');
            Route::get('/reports/export/xlsx', [ReportController::class, 'xlsx'])
                ->name('reports.export.xlsx');
            Route::post('/financial-accounts', [FinancialAccountController::class, 'store'])
                ->name('financial-accounts.store');
            Route::patch('/financial-accounts/{financial_account}', [FinancialAccountController::class, 'update'])
                ->name('financial-accounts.update');
            Route::delete('/financial-accounts/{financial_account}', [FinancialAccountController::class, 'destroy'])
                ->name('financial-accounts.destroy');

            Route::post('/transaction-categories', [TransactionCategoryController::class, 'store'])
                ->name('transaction-categories.store');
            Route::patch('/transaction-categories/{transaction_category}', [TransactionCategoryController::class, 'update'])
                ->name('transaction-categories.update');
            Route::delete('/transaction-categories/{transaction_category}', [TransactionCategoryController::class, 'destroy'])
                ->name('transaction-categories.destroy');

            Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
            Route::match(['post', 'patch'], '/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
            Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
            Route::post('/transactions/{transaction}/post', [TransactionController::class, 'post'])->name('transactions.post');
            Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
            Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');

            Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
            Route::patch('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
            Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('role:super-admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::middleware('permission:manage plans')->group(function (): void {
            Route::resource('subscription-plans', SubscriptionPlanController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

        Route::middleware('permission:manage subscriptions')->group(function (): void {
            Route::resource('subscriptions', SubscriptionController::class)
                ->only(['index', 'store', 'update']);
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
