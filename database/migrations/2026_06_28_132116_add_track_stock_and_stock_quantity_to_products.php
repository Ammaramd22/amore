<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'track_stock')) {
                $table->boolean('track_stock')->default(false)->after('show_in_qr');
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->decimal('stock_quantity', 12, 3)->default(0)->after('track_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
            if (Schema::hasColumn('products', 'track_stock')) {
                $table->dropColumn('track_stock');
            }
        });
    }
};
