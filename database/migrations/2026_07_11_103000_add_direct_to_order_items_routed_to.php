<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE order_items MODIFY routed_to ENUM('kitchen', 'bar', 'direct') NOT NULL DEFAULT 'kitchen'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('order_items')->where('routed_to', 'direct')->update(['routed_to' => 'kitchen']);
            DB::statement("ALTER TABLE order_items MODIFY routed_to ENUM('kitchen', 'bar') NOT NULL DEFAULT 'kitchen'");
        }
    }
};
