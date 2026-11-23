<?php

namespace App\Models;

use App\Enums\TransactionCategoryType;
use Database\Factories\TransactionCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionCategory extends Model
{
    /** @use HasFactory<TransactionCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id', 'parent_id', 'name', 'slug', 'type', 'icon', 'color',
        'description', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionCategoryType::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'category_id');
    }
}
