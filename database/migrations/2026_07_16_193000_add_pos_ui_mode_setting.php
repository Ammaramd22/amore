<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'pos_ui_mode')->exists();
        if (! $exists) {
            DB::table('settings')->insert([
                'key' => 'pos_ui_mode',
                'value' => 'restaurant',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'POS front UI: restaurant (tables/KOT) or bakery (fast counter with images)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'pos_ui_mode')->delete();
    }
};
