<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'rounding_amount')) {
                $table->decimal('rounding_amount', 12, 2)->default(0)->after('tip_amount');
            }
            if (! Schema::hasColumn('orders', 'card_surcharge_amount')) {
                $table->decimal('card_surcharge_amount', 12, 2)->default(0)->after('rounding_amount');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'surcharge_amount')) {
                $table->decimal('surcharge_amount', 12, 2)->nullable()->after('amount');
            }
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_registers', 'cash_refunds')) {
                $table->decimal('cash_refunds', 12, 2)->default(0)->after('cash_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'card_surcharge_amount')) {
                $table->dropColumn('card_surcharge_amount');
            }
            if (Schema::hasColumn('orders', 'rounding_amount')) {
                $table->dropColumn('rounding_amount');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'surcharge_amount')) {
                $table->dropColumn('surcharge_amount');
            }
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            if (Schema::hasColumn('cash_registers', 'cash_refunds')) {
                $table->dropColumn('cash_refunds');
            }
        });
    }
};
