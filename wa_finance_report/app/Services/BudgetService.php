<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class BudgetService
{
    /** @param array<string, mixed> $attributes */
    public function create(Account $account, User $user, array $attributes, Request $request): Budget
    {
        return DB::transaction(function () use ($account, $user, $attributes, $request): Budget {
            $budget = $account->budgets()->create([
                ...$attributes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $this->log($budget, $user, $request, 'created', 'Budget created');

            return $budget;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Budget $budget, User $user, array $attributes, Request $request): Budget
    {
        return DB::transaction(function () use ($budget, $user, $attributes, $request): Budget {
            $locked = Budget::query()->lockForUpdate()->findOrFail($budget->id);
            $old = $locked->only(array_keys($attributes));
            $locked->update([...$attributes, 'updated_by' => $user->id]);
            if ($locked->getChanges() !== []) {
                $this->log($locked, $user, $request, 'updated', 'Budget updated', [
                    'old' => $old,
                    'attributes' => $locked->only(array_keys($attributes)),
                ]);
            }

            return $locked->refresh();
        });
    }

    public function delete(Budget $budget, User $user, Request $request): void
    {
        DB::transaction(function () use ($budget, $user, $request): void {
            $locked = Budget::query()->lockForUpdate()->findOrFail($budget->id);
            $this->log($locked, $user, $request, 'deleted', 'Budget deleted');
            $locked->delete();
        });
    }

    /** @param array<string, mixed> $properties */
    private function log(Budget $budget, User $user, Request $request, string $event, string $description, array $properties = []): void
    {
        activity('budget')
            ->causedBy($user)
            ->performedOn($budget)
            ->event($event)
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($budget, $request): void {
                $activity->account_id = $budget->account_id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log($description);
    }
}
