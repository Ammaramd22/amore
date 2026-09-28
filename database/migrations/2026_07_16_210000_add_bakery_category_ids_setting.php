<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'bakery_category_ids')->exists()) {
            return;
        }

        $now = now();
        DB::table('settings')->insert([
            'key' => 'bakery_category_ids',
            'value' => '[]',
            'type' => 'string',
            'group' => 'bakery',
            'description' => 'Bakery UI: JSON category IDs visible in Bakery POS',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'bakery_category_ids')->delete();
    }
};
