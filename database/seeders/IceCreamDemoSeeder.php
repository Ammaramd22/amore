<?php

namespace Database\Seeders;

use App\Services\IceCreamDemoService;
use Illuminate\Database\Seeder;

class IceCreamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $summary = IceCreamDemoService::ensureSeeded();

        $this->command?->info(
            'Ice cream demo: '.$summary['categories'].' categories, '
            .$summary['products'].' products'
            .($summary['images_copied'] ? ', '.$summary['images_copied'].' images copied' : '')
            .'. POS UI set to ice_cream.'
        );
    }
}
