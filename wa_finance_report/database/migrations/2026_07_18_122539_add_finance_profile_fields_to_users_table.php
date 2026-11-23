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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email')->index();
            $table->string('status', 32)->default('active')->after('password')->index();
            $table->foreignId('active_account_id')->nullable()->after('status')->constrained('accounts')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('remember_token')->index();
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_account_id');
            $table->dropColumn(['phone', 'status', 'last_login_at', 'last_login_ip', 'deleted_at']);
        });
    }
};
