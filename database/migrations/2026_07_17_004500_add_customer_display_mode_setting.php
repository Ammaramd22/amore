<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'customer_display_mode')->exists()) {
            return;
        }

        $now = now();
        DB::table('settings')->insert([
            'key' => 'customer_display_mode',
            'value' => 'digital',
            'type' => 'string',
            'group' => 'pos',
            'description' => 'Customer display: digital (TV) or analog (LED)',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'customer_display_mode')->delete();
    }
};
