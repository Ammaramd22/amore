<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('card_sales', 12, 2)->default(0);
            $table->decimal('bank_transfer_sales', 12, 2)->default(0);
            $table->decimal('online_sales', 12, 2)->default(0);
            $table->decimal('credit_sales', 12, 2)->default(0);
            $table->decimal('cash_in', 12, 2)->default(0);
            $table->decimal('cash_out', 12, 2)->default(0);
            $table->integer('orders_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // Add register_id to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('register_id')->nullable()->constrained('cash_registers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['register_id']);
            $table->dropColumn('register_id');
        });
        Schema::dropIfExists('cash_registers');
    }
};
