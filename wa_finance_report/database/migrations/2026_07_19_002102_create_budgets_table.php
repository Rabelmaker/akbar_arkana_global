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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_type', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 20, 2);
            $table->unsignedTinyInteger('alert_percentage')->default(80);
            $table->boolean('rollover_enabled')->default(false);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'status', 'start_date', 'end_date']);
            $table->index(['account_id', 'category_id', 'financial_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
