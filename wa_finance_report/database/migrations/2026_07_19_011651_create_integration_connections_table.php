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
        Schema::create('integration_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->index();
            $table->string('name');
            $table->string('status', 24)->default('inactive')->index();
            $table->text('credentials_encrypted')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'provider', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_connections');
    }
};
