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
                'description' => 'POS front UI: restaurant, bakery, or ice_cream',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('settings')->where('key', 'pos_ui_mode')->update([
            'description' => 'Software owner only: Restaurant / Bakery / Ice Cream shop UI',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // keep setting
    }
};
