<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->boolean('has_variants')->default(false);
            $table->boolean('has_addons')->default(false);
            $table->boolean('is_combo')->default(false);
            $table->enum('routed_to', ['kitchen', 'bar', 'both'])->default('kitchen');
            $table->boolean('is_available')->default(true);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('show_in_qr')->default(true);
            $table->boolean('track_stock')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
