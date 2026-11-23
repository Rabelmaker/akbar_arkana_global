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
        Schema::create('ai_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 32)->index();
            $table->string('source_id')->nullable()->index();
            $table->string('input_type', 24)->index();
            $table->json('raw_input')->nullable();
            $table->json('extracted_data')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('model')->nullable();
            $table->string('status', 24)->default('queued')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_processing_logs');
    }
};
