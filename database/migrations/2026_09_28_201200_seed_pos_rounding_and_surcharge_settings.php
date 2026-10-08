<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key' => 'price_rounding_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'Round POS bill totals up to the nearest rounding unit',
            ],
            [
                'key' => 'price_rounding_mode',
                'value' => 'up',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Rounding mode (up = always round up)',
            ],
            [
                'key' => 'price_rounding_unit',
                'value' => '1',
                'type' => 'float',
                'group' => 'pos',
                'description' => 'Currency unit to round to (e.g. 1 = nearest 1.00)',
            ],
            [
                'key' => 'card_surcharge_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'Add a percentage surcharge on card payments',
            ],
            [
                'key' => 'card_surcharge_percent',
                'value' => '3',
                'type' => 'float',
                'group' => 'pos',
                'description' => 'Card surcharge percent (applied to card portion only)',
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('settings')->where('key', $row['key'])->exists()) {
                continue;
            }
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        if (class_exists(Setting::class)) {
            Setting::flushCache();
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'price_rounding_enabled',
            'price_rounding_mode',
            'price_rounding_unit',
            'card_surcharge_enabled',
            'card_surcharge_percent',
        ])->delete();

        if (class_exists(Setting::class)) {
            Setting::flushCache();
        }
    }
};
