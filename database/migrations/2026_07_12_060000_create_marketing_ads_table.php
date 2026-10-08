<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_ads', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('show_on_date')->nullable()->comment('Null = every day; set = only that date');
            $table->timestamps();
        });

        // Cap how many ads rotate on the customer display each day
        DB::table('marketing_settings')->updateOrInsert(
            ['key' => 'ads_per_day'],
            [
                'value' => '3',
                'type' => 'integer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Import legacy poster_images JSON into ads table
        $raw = DB::table('marketing_settings')->where('key', 'poster_images')->value('value');
        $list = json_decode((string) $raw, true);
        if (! is_array($list) || $list === []) {
            $single = DB::table('marketing_settings')->where('key', 'poster_image')->value('value');
            $list = $single ? [$single] : [];
        }

        $order = 0;
        foreach ($list as $url) {
            $path = is_string($url) ? strtok(ltrim($url, '/'), '?') : '';
            if ($path === '' || $path === false) {
                continue;
            }
            if (! str_starts_with($path, 'storage/')) {
                $path = 'storage/'.ltrim($path, '/');
            }
            // store relative public-disk path: marketing/xxx.jpg
            $relative = preg_replace('#^storage/#', '', $path) ?: $path;
            $relative = ltrim($relative, '/');

            DB::table('marketing_ads')->insert([
                'title' => 'Ad '.($order + 1),
                'image_path' => $relative,
                'is_active' => true,
                'sort_order' => $order,
                'show_on_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $order++;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_ads');
        DB::table('marketing_settings')->where('key', 'ads_per_day')->delete();
    }
};
