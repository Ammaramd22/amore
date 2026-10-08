<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeForHosting extends Command
{
    protected $signature = 'optimize:hosting
                            {--clear : Clear caches instead of building them}';

    protected $description = 'Optimize Laravel for cPanel / shared hosting (config, routes, views, settings cache)';

    public function handle(): int
    {
        if ($this->option('clear')) {
            Artisan::call('optimize:clear');
            $this->line(Artisan::output());
            Setting::flushCache();
            $this->info('Caches cleared (including settings).');

            return self::SUCCESS;
        }

        if (config('app.debug')) {
            $this->warn('APP_DEBUG is true — set APP_DEBUG=false on live cPanel for best speed.');
        }

        if (config('app.env') !== 'production') {
            $this->warn('APP_ENV is "'.config('app.env').'" — use production on live hosting.');
        }

        $this->info('Building caches for shared hosting…');

        Artisan::call('config:cache');
        $this->line(trim(Artisan::output()));

        Artisan::call('route:cache');
        $this->line(trim(Artisan::output()));

        Artisan::call('view:cache');
        $this->line(trim(Artisan::output()));

        Artisan::call('event:cache');
        $this->line(trim(Artisan::output()));

        Setting::flushCache();
        Setting::allCached(); // warm settings into cache store
        $this->info('Settings cache warmed ('.Setting::allCached()->count().' keys).');

        $this->newLine();
        $this->info('Done. Recommended live .env for cPanel shared:');
        $this->line('  APP_ENV=production');
        $this->line('  APP_DEBUG=false');
        $this->line('  LOG_LEVEL=error');
        $this->line('  CACHE_STORE=file');
        $this->line('  SESSION_DRIVER=file');
        $this->line('  QUEUE_CONNECTION=database');
        $this->newLine();
        $this->comment('Also enable OPcache in cPanel → MultiPHP INI Editor.');

        return self::SUCCESS;
    }
}
