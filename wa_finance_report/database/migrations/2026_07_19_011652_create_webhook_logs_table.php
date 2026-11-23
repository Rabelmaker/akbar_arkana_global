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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->index();
            $table->string('event_name', 100)->index();
            $table->string('external_id')->nullable()->index();
            $table->string('idempotency_key');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('status', 24)->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'idempotency_key']);
            $table->index(['account_id', 'provider', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
