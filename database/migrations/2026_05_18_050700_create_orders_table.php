<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'express'])->default('dine_in');
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('tip_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->text('order_notes')->nullable();
            $table->string('pickup_time')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('google_maps_link')->nullable();
            $table->boolean('payment_on_delivery')->default(false);
            $table->enum('delivery_status', ['pending', 'assigned', 'picked_up', 'delivered', 'cancelled'])->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('merged_from_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->boolean('is_void')->default(false);
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
