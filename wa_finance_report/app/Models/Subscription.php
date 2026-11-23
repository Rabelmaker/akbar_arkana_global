<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'price',
        'payment_status',
        'auto_renew',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'status' => SubscriptionStatus::Trial,
        'price' => 0,
        'payment_status' => PaymentStatus::Pending,
        'auto_renew' => false,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => SubscriptionStatus::class,
            'price' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'auto_renew' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::GracePeriod,
            ])
            ->whereDate('start_date', '<=', today())
            ->where(function (Builder $dateQuery): void {
                $dateQuery
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            });
    }

    public function isValid(): bool
    {
        return $this->status->grantsAccess()
            && $this->start_date->lte(today())
            && ($this->end_date === null || $this->end_date->gte(today()));
    }
}
