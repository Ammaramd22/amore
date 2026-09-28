<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kitchen_orders') || ! Schema::hasColumn('kitchen_orders', 'printed_at')) {
            return;
        }

        // Existing tickets were already handled (or never will be); only NEW null printed_at = pending.
        DB::table('kitchen_orders')
            ->whereNull('printed_at')
            ->update(['printed_at' => DB::raw('COALESCE(created_at, NOW())')]);
    }

    public function down(): void
    {
        // no-op — cannot know which were truly never printed
    }
};
