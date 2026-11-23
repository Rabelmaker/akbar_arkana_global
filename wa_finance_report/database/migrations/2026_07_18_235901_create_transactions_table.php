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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_number', 40)->unique();
            $table->string('type', 20);
            $table->dateTime('transaction_date');
            $table->foreignId('financial_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_financial_account_id')->nullable()->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->decimal('amount', 20, 2);
            $table->decimal('fee_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2);
            $table->char('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->string('description');
            $table->string('merchant')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_method', 64)->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('source', 20)->default('web');
            $table->string('external_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'idempotency_key']);
            $table->index(['account_id', 'transaction_date']);
            $table->index(['account_id', 'status', 'type']);
            $table->index(['account_id', 'financial_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
