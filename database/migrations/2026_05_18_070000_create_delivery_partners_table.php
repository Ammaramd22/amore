<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_partners')) {
            Schema::create('delivery_partners', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 20)->unique();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
                $table->boolean('is_active')->default(true);
                $table->text('api_config')->nullable();
                $table->timestamps();
            });
        }

        // Add delivery fields to orders
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'delivery_partner_id')) {
                    $table->foreignId('delivery_partner_id')->nullable()->constrained('delivery_partners')->onDelete('set null');
                }
                if (! Schema::hasColumn('orders', 'delivery_address')) {
                    $table->text('delivery_address')->nullable();
                }
                if (! Schema::hasColumn('orders', 'delivery_charge')) {
                    $table->decimal('delivery_charge', 10, 2)->default(0);
                }
                if (! Schema::hasColumn('orders', 'delivery_partner_fee')) {
                    $table->decimal('delivery_partner_fee', 10, 2)->default(0);
                }
                if (! Schema::hasColumn('orders', 'delivery_time')) {
                    $table->timestamp('delivery_time')->nullable();
                }
                if (! Schema::hasColumn('orders', 'delivery_status')) {
                    $table->enum('delivery_status', ['pending', 'accepted', 'picked_up', 'delivered', 'cancelled'])->nullable();
                }
            });
        }

        // Add address fields to customers
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'address')) {
                    $table->text('address')->nullable();
                }
                if (! Schema::hasColumn('customers', 'city')) {
                    $table->string('city', 100)->nullable();
                }
                if (! Schema::hasColumn('customers', 'postal_code')) {
                    $table->string('postal_code', 20)->nullable();
                }
                if (! Schema::hasColumn('customers', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable();
                }
                if (! Schema::hasColumn('customers', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'delivery_partner_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['delivery_partner_id']);
                $table->dropColumn(['delivery_partner_id', 'delivery_address', 'delivery_charge', 'delivery_partner_fee', 'delivery_time', 'delivery_status']);
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'address')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['address', 'city', 'postal_code', 'latitude', 'longitude']);
            });
        }

        Schema::dropIfExists('delivery_partners');
    }
};
