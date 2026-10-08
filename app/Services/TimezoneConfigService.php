<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class TimezoneConfigService
{
    /** Apply the configured system timezone onto Laravel / PHP date handling. */
    public static function applyFromSettings(): void
    {
        try {
            $tz = Setting::timezone();
            Config::set('app.timezone', $tz);
            date_default_timezone_set($tz);
        } catch (\Throwable $e) {
            // Settings table may not exist during early migrate / install
        }
    }
}
