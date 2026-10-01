<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin UI is Bootstrap 5 (AdminLTE) — never use default Tailwind pagination
        // (Tailwind `w-5 h-5` SVGs explode to full page width without Tailwind CSS).
        Paginator::useBootstrapFive();

        \App\Services\TimezoneConfigService::applyFromSettings();
        \App\Services\MailConfigService::applyFromSettings();
    }
}
