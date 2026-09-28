<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            ['key' => 'meta_wa_access_token', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_phone_number_id', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_api_version', 'value' => 'v22.0', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_template_name', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_template_language', 'value' => 'en', 'type' => 'string', 'group' => 'notifications'],
        ];

        foreach ($rows as $row) {
            if (! DB::table('settings')->where('key', $row['key'])->exists()) {
                DB::table('settings')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'meta_wa_access_token',
            'meta_wa_phone_number_id',
            'meta_wa_api_version',
            'meta_wa_template_name',
            'meta_wa_template_language',
        ])->delete();
    }
};
