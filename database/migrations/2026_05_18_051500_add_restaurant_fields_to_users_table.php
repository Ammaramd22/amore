<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('employee_code', 50)->nullable()->unique()->after('avatar');
            $table->enum('user_type', ['admin', 'manager', 'cashier', 'waiter', 'kitchen', 'delivery', 'staff'])->default('staff')->after('employee_code');
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'phone', 'avatar', 'employee_code', 'user_type', 'is_active', 'last_login_at', 'last_login_ip']);
            $table->dropSoftDeletes();
        });
    }
};
