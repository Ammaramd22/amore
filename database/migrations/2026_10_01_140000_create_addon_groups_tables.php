<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('addon_group_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_group_id')->constrained('addon_groups')->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained('addons')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['addon_group_id', 'addon_id']);
        });

        Schema::create('addon_group_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_group_id')->constrained('addon_groups')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['addon_group_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_group_product');
        Schema::dropIfExists('addon_group_addon');
        Schema::dropIfExists('addon_groups');
    }
};
