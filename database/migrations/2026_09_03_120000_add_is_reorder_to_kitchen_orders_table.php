<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchen_orders', 'is_reorder')) {
                $table->boolean('is_reorder')->default(false)->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_orders', 'is_reorder')) {
                $table->dropColumn('is_reorder');
            }
        });
    }
};
