<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->string('mode', 20)->default('shift')->after('user_id');
            $table->date('business_date')->nullable()->after('mode');
            $table->index(['mode', 'closed_at']);
            $table->index('business_date');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropIndex(['mode', 'closed_at']);
            $table->dropIndex(['business_date']);
            $table->dropColumn(['mode', 'business_date']);
        });
    }
};
