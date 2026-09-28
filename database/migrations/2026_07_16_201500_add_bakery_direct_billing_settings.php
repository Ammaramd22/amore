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
                'key' => 'bakery_direct_billing',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: pay & finish — no Place Order / table bills / KOT',
            ],
            [
                'key' => 'bakery_show_cart_display',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Cart customer display in POS header',
            ],
            [
                'key' => 'bakery_show_status_display',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Status board in POS header',
            ],
            [
                'key' => 'bakery_show_orders_display',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bakery',
                'description' => 'Bakery UI: show Orders in POS header',
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
            'bakery_direct_billing',
            'bakery_show_cart_display',
            'bakery_show_status_display',
            'bakery_show_orders_display',
        ])->delete();
    }
};
