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
                'key' => 'customer_display_protocol',
                'value' => 'plain',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Analog LED serial protocol: plain, escpos, dsp800',
            ],
            [
                'key' => 'customer_display_baud',
                'value' => '9600',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Analog LED serial baud rate',
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

        DB::table('settings')
            ->whereIn('key', [
                'customer_display_mode',
                'customer_display_protocol',
                'customer_display_baud',
            ])
            ->update([
                'group' => 'pos',
                'updated_at' => $now,
            ]);

        DB::table('settings')
            ->where('key', 'customer_display_mode')
            ->update([
                'description' => 'Customer display: digital (TV) or analog (LED hardware)',
            ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'customer_display_protocol',
            'customer_display_baud',
        ])->delete();
    }
};
