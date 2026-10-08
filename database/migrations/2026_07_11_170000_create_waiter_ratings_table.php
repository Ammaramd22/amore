<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiter_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('waiter_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1–5
            $table->string('emoji', 16)->nullable();
            $table->timestamp('rated_at')->useCurrent();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['waiter_id', 'rated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiter_ratings');
    }
};
