<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'timezone', 'currency', 'locale', 'status', 'created_by',
    ];

    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
        'currency' => 'IDR',
        'locale' => 'id',
        'status' => AccountStatus::Active,
    ];

    protected function casts(): array
    {
        return ['status' => AccountStatus::class];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['id', 'role', 'is_owner', 'joined_at'])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function integrationConnections(): HasMany
    {
        return $this->hasMany(IntegrationConnection::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function aiProcessingLogs(): HasMany
    {
        return $this->hasMany(AiProcessingLog::class);
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function validSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->ofMany(
            ['start_date' => 'max', 'id' => 'max'],
            fn ($query) => $query->valid(),
        );
    }

    public function hasValidSubscription(): bool
    {
        return $this->subscriptions()->valid()->exists();
    }
}
