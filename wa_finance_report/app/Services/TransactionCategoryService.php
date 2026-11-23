<?php

namespace App\Services;

use App\Models\Account;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class TransactionCategoryService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Account $account, User $user, array $attributes, Request $request): TransactionCategory
    {
        return DB::transaction(function () use ($account, $user, $attributes, $request): TransactionCategory {
            $category = $account->transactionCategories()->create([
                ...$attributes,
                'slug' => $this->uniqueSlug($account, $attributes['name']),
                'is_default' => false,
            ]);

            $this->log($category, $user, $request, 'created', 'Transaction category created');

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TransactionCategory $category, User $user, array $attributes, Request $request): TransactionCategory
    {
        $this->ensureValidParent($category, $attributes['parent_id'] ?? null);

        return DB::transaction(function () use ($category, $user, $attributes, $request): TransactionCategory {
            $oldValues = $category->only(array_keys($attributes));
            $category->update($attributes);

            if ($category->getChanges() !== []) {
                $this->log($category, $user, $request, 'updated', 'Transaction category updated', [
                    'old' => $oldValues,
                    'attributes' => $category->only(array_keys($attributes)),
                ]);
            }

            return $category->refresh();
        });
    }

    public function delete(TransactionCategory $category, User $user, Request $request): void
    {
        DB::transaction(function () use ($category, $user, $request): void {
            $category->children()->update(['parent_id' => null]);
            $this->log($category, $user, $request, 'deleted', 'Transaction category deleted');
            $category->delete();
        });
    }

    private function ensureValidParent(TransactionCategory $category, ?int $parentId): void
    {
        while ($parentId !== null) {
            if ($parentId === $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Kategori induk tidak boleh membuat hierarki berputar.',
                ]);
            }

            $parentId = TransactionCategory::query()
                ->where('account_id', $category->account_id)
                ->whereKey($parentId)
                ->value('parent_id');
        }
    }

    private function uniqueSlug(Account $account, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori';
        $slug = $baseSlug;
        $suffix = 1;

        while ($account->transactionCategories()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(
        TransactionCategory $category,
        User $user,
        Request $request,
        string $event,
        string $description,
        array $properties = [],
    ): void {
        activity('transaction_category')
            ->causedBy($user)
            ->performedOn($category)
            ->event($event)
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($category, $request): void {
                $activity->account_id = $category->account_id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log($description);
    }
}
