<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('type', 32)->default('text'); // text | text_color
            $table->boolean('require_selection')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_set_id')->constrained('option_sets')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('option_set_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_set_id')->constrained('option_sets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['option_set_id', 'product_id']);
        });

        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('option_set_id')->nullable()->constrained('option_sets')->nullOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('options')->nullOnDelete();
            $table->string('option_set_name');
            $table->string('option_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_options');
        Schema::dropIfExists('option_set_product');
        Schema::dropIfExists('options');
        Schema::dropIfExists('option_sets');
    }
};
