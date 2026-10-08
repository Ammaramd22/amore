<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'is_comp')) {
                $table->boolean('is_comp')->default(false)->after('is_void');
            }
            if (! Schema::hasColumn('order_items', 'comp_reason')) {
                $table->string('comp_reason')->nullable()->after('is_comp');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_comp')) {
                $table->boolean('is_comp')->default(false)->after('is_void');
            }
            if (! Schema::hasColumn('orders', 'comp_reason')) {
                $table->text('comp_reason')->nullable()->after('is_comp');
            }
            if (! Schema::hasColumn('orders', 'comped_by')) {
                $table->foreignId('comped_by')->nullable()->after('comp_reason')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'comped_at')) {
                $table->timestamp('comped_at')->nullable()->after('comped_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'comped_by')) {
                $table->dropConstrainedForeignId('comped_by');
            }
            foreach (['comped_at', 'comp_reason', 'is_comp'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['comp_reason', 'is_comp'] as $col) {
                if (Schema::hasColumn('order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
