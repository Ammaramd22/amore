<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, boolean
            $table->timestamps();
        });

        // Insert default settings
        DB::table('marketing_settings')->insert([
            ['key' => 'welcome_text', 'value' => 'Welcome! Order at the counter', 'type' => 'text'],
            ['key' => 'poster_image', 'value' => null, 'type' => 'image'],
            ['key' => 'poster_images', 'value' => '[]', 'type' => 'json'],
            ['key' => 'slide_interval', 'value' => '6', 'type' => 'text'],
            ['key' => 'idle_timeout', 'value' => '30', 'type' => 'text'],
            ['key' => 'marketing_enabled', 'value' => '1', 'type' => 'boolean'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_settings');
    }
};
