<?php

namespace App\Models;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'category_id', 'financial_account_id', 'period_type',
        'start_date', 'end_date', 'amount', 'alert_percentage', 'rollover_enabled',
        'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $attributes = [
        'alert_percentage' => 80,
        'rollover_enabled' => false,
        'status' => BudgetStatus::Active,
    ];

    protected function casts(): array
    {
        return [
            'period_type' => BudgetPeriodType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'decimal:2',
            'alert_percentage' => 'integer',
            'rollover_enabled' => 'boolean',
            'status' => BudgetStatus::class,
            'used_amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeWithUsage(Builder $query): Builder
    {
        $budgetTable = $this->getTable();
        $transactionTable = (new Transaction)->getTable();

        return $query->addSelect([
            'used_amount' => Transaction::query()
                ->selectRaw('COALESCE(SUM(total_amount), 0)')
                ->whereColumn("{$transactionTable}.account_id", "{$budgetTable}.account_id")
                ->where('type', 'expense')
                ->where('status', 'posted')
                ->whereNull('reversed_transaction_id')
                ->whereRaw("DATE({$transactionTable}.transaction_date) >= {$budgetTable}.start_date")
                ->whereRaw("DATE({$transactionTable}.transaction_date) <= {$budgetTable}.end_date")
                ->whereRaw("({$budgetTable}.category_id IS NULL OR {$transactionTable}.category_id = {$budgetTable}.category_id)")
                ->whereRaw("({$budgetTable}.financial_account_id IS NULL OR {$transactionTable}.financial_account_id = {$budgetTable}.financial_account_id)"),
        ]);
    }

    public function usedAmount(): float
    {
        return (float) ($this->used_amount ?? 0);
    }

    public function remainingAmount(): float
    {
        return (float) $this->amount - $this->usedAmount();
    }

    public function usagePercentage(): float
    {
        return (float) $this->amount > 0
            ? round(($this->usedAmount() / (float) $this->amount) * 100, 2)
            : 0;
    }

    public function healthStatus(): string
    {
        return match (true) {
            $this->usagePercentage() >= 100 => 'exceeded',
            $this->usagePercentage() >= $this->alert_percentage => 'warning',
            default => 'safe',
        };
    }
}
