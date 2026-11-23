<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionCategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreTransactionCategoryRequest;
use App\Http\Requests\User\UpdateTransactionCategoryRequest;
use App\Models\TransactionCategory;
use App\Services\TransactionCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionCategoryController extends Controller
{
    public function __construct(private TransactionCategoryService $categoryService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TransactionCategory::class);
        $account = $request->attributes->get('activeAccount');
        $categories = $account->transactionCategories()
            ->with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('User/TransactionCategories/Index', [
            'categories' => $categories->map(fn (TransactionCategory $category) => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name,
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type->value,
                'type_label' => $category->type->label(),
                'icon' => $category->icon,
                'color' => $category->color,
                'description' => $category->description,
                'is_default' => $category->is_default,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ]),
            'types' => collect(TransactionCategoryType::cases())->map(fn (TransactionCategoryType $type) => [
                'value' => $type->value,
                'title' => $type->label(),
            ]),
            'summary' => [
                'income' => $categories->where('type', TransactionCategoryType::Income)->count(),
                'expense' => $categories->where('type', TransactionCategoryType::Expense)->count(),
                'active' => $categories->where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(StoreTransactionCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create(
            $request->attributes->get('activeAccount'),
            $request->user(),
            $request->validated(),
            $request,
        );

        return back()->with('success', 'Kategori transaksi berhasil ditambahkan.');
    }

    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): RedirectResponse
    {
        $this->categoryService->update($transactionCategory, $request->user(), $request->validated(), $request);

        return back()->with('success', 'Kategori transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, TransactionCategory $transactionCategory): RedirectResponse
    {
        Gate::authorize('delete', $transactionCategory);
        $this->categoryService->delete($transactionCategory, $request->user(), $request);

        return back()->with('success', 'Kategori transaksi berhasil dihapus.');
    }
}
