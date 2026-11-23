<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'billing_period',
        'duration_days',
        'max_financial_accounts',
        'max_monthly_transactions',
        'max_users',
        'api_access',
        'whatsapp_integration',
        'google_sheets_integration',
        'report_export',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'price' => 0,
        'is_active' => true,
        'sort_order' => 0,
        'api_access' => false,
        'whatsapp_integration' => false,
        'google_sheets_integration' => false,
        'report_export' => false,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_period' => BillingPeriod::class,
            'api_access' => 'boolean',
            'whatsapp_integration' => 'boolean',
            'google_sheets_integration' => 'boolean',
            'report_export' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
