<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'timezone')->exists();
        if (! $exists) {
            $default = env('APP_TIMEZONE', 'Asia/Colombo');
            if (! is_string($default) || $default === '' || ! in_array($default, timezone_identifiers_list(), true)) {
                $default = 'Asia/Colombo';
            }

            DB::table('settings')->insert([
                'key' => 'timezone',
                'value' => $default,
                'type' => 'string',
                'group' => 'business',
                'description' => 'System timezone for POS receipts, KOT, and order timestamps',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (class_exists(Setting::class)) {
            Setting::flushCache();
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'timezone')->delete();
        if (class_exists(Setting::class)) {
            Setting::flushCache();
        }
    }
};
