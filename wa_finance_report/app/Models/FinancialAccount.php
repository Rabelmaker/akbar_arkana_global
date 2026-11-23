<?php

namespace App\Models;

use App\Enums\FinancialAccountType;
use Database\Factories\FinancialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    /** @use HasFactory<FinancialAccountFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'type', 'institution_name', 'account_number',
        'account_holder_name', 'opening_balance', 'current_balance', 'currency',
        'color', 'icon', 'is_active', 'include_in_total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancialAccountType::class,
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'include_in_total' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_financial_account_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
