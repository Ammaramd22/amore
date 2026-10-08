<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('total_price', 12, 4)->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
