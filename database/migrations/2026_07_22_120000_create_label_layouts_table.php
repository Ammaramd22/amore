<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('label_layouts')) {
            Schema::create('label_layouts', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->boolean('is_default')->default(false);
                $table->json('config');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('label_layouts');
    }
};
