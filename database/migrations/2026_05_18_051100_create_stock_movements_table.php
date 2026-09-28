<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'transfer_in', 'transfer_out', 'wastage', 'return']);
            $table->decimal('quantity', 12, 4);
            $table->decimal('stock_before', 12, 4)->default(0);
            $table->decimal('stock_after', 12, 4)->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->foreignId('reference_id')->nullable(); // order_id or purchase_id
            $table->string('reference_type')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
