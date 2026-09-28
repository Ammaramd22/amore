<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billiard_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['pool', 'snooker'])->default('pool');
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('billiard_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('billiard_table_id')->constrained('billiard_tables')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->enum('source', ['walk_in', 'phone', 'online', 'admin'])->default('walk_in');
            $table->enum('status', ['booked', 'active', 'completed', 'cancelled', 'no_show'])->default('booked');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->decimal('hours', 8, 2)->default(1);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('end_alert_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scheduled_start', 'status']);
            $table->index(['payment_status', 'status']);
            $table->index('customer_phone');
        });

        Schema::create('billiard_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billiard_booking_id')->constrained('billiard_bookings')->cascadeOnDelete();
            $table->enum('method', ['cash', 'card', 'bank_transfer', 'online', 'credit'])->default('cash');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Allow billiards staff user_type (MySQL enum)
        try {
            DB::statement("ALTER TABLE users MODIFY user_type ENUM('admin','manager','cashier','waiter','kitchen','delivery','staff','software_owner','billiards') NOT NULL DEFAULT 'staff'");
        } catch (\Throwable) {
            // SQLite / non-MySQL — ignore
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billiard_payments');
        Schema::dropIfExists('billiard_bookings');
        Schema::dropIfExists('billiard_tables');

        try {
            DB::table('users')->where('user_type', 'billiards')->update(['user_type' => 'staff']);
            DB::statement("ALTER TABLE users MODIFY user_type ENUM('admin','manager','cashier','waiter','kitchen','delivery','staff','software_owner') NOT NULL DEFAULT 'staff'");
        } catch (\Throwable) {
            //
        }
    }
};
