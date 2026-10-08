<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'expiry_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('expiry_date')->nullable()->after('stock_quantity');
            });
        }

        if (! DB::table('settings')->where('key', 'expiry_remind_days')->exists()) {
            $now = now();
            DB::table('settings')->insert([
                'key' => 'expiry_remind_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Days before product/ingredient expiry to show dashboard reminder',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'expiry_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('expiry_date');
            });
        }

        DB::table('settings')->where('key', 'expiry_remind_days')->delete();
    }
};
