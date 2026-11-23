<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 20, 2)->default(0);
            $table->string('billing_period', 32)->index();
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('max_financial_accounts')->nullable();
            $table->unsignedInteger('max_monthly_transactions')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->boolean('api_access')->default(false);
            $table->boolean('whatsapp_integration')->default(false);
            $table->boolean('google_sheets_integration')->default(false);
            $table->boolean('report_export')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
