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
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32);
            $table->string('institution_name')->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('account_holder_name')->nullable();
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->char('currency', 3)->default('IDR');
            $table->string('color', 16)->default('#7367F0');
            $table->string('icon', 64)->default('ri-wallet-3-line');
            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_total')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
            $table->index(['account_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
