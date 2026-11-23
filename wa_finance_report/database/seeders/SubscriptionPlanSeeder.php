<?php

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['code' => 'trial-14'],
            [
                'name' => 'Trial 14 Hari',
                'description' => 'Akses evaluasi WA Finance Report selama 14 hari.',
                'price' => 0,
                'billing_period' => BillingPeriod::Custom,
                'duration_days' => 14,
                'max_financial_accounts' => 2,
                'max_monthly_transactions' => 100,
                'max_users' => 1,
                'api_access' => false,
                'whatsapp_integration' => false,
                'google_sheets_integration' => false,
                'report_export' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        SubscriptionPlan::updateOrCreate(
            ['code' => 'basic-monthly'],
            [
                'name' => 'Basic Bulanan',
                'description' => 'Paket dasar untuk pencatatan keuangan pribadi dan usaha kecil.',
                'price' => 99000,
                'billing_period' => BillingPeriod::Monthly,
                'duration_days' => 30,
                'max_financial_accounts' => 5,
                'max_monthly_transactions' => 1000,
                'max_users' => 3,
                'api_access' => true,
                'whatsapp_integration' => false,
                'google_sheets_integration' => false,
                'report_export' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
        );
    }
}
