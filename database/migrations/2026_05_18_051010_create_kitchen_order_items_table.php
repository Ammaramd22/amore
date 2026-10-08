<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->text('special_instructions')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->integer('prep_time_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
    }
};
