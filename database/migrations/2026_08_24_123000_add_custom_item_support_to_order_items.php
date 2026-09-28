<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL');
        }

        Schema::table('order_items', function (Blueprint $table) use ($driver) {
            if (! Schema::hasColumn('order_items', 'is_custom_item')) {
                $table->boolean('is_custom_item')->default(false)->after('product_id');
            }

            if ($driver !== 'sqlite') {
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'is_custom_item')) {
                $table->dropColumn('is_custom_item');
            }
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        }

        if ($driver !== 'sqlite') {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products');
            });
        }
    }
};
