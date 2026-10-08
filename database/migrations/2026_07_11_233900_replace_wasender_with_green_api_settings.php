<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow green_api provider (and keep wasender for old log rows)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notification_logs MODIFY COLUMN provider VARCHAR(32) NOT NULL");
        }

        $now = now();
        $rows = [
            ['key' => 'green_api_url', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'green_api_id_instance', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'green_api_token', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('settings')->where('key', $row['key'])->exists();
            if (! $exists) {
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
            'green_api_url',
            'green_api_id_instance',
            'green_api_token',
        ])->delete();
    }
};
