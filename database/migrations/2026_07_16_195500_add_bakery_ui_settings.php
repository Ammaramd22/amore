<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            [
                'key' => 'bakery_show_delivery',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Delivery order type',
            ],
            [
                'key' => 'bakery_show_express',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Express order type',
            ],
            [
                'key' => 'bakery_show_dine_in',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Dine-in order type',
            ],
            [
                'key' => 'bakery_disable_kot',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: direct billing only — skip KOT/BOT tickets',
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('settings')->where('key', $row['key'])->exists()) {
                continue;
            }
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'bakery_show_delivery',
            'bakery_show_express',
            'bakery_show_dine_in',
            'bakery_disable_kot',
        ])->delete();
    }
};
