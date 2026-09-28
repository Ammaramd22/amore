<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('loyalty_joined')->default(false)->after('loyalty_points');
            $table->string('loyalty_token', 64)->nullable()->unique()->after('loyalty_joined');
            $table->unsignedInteger('loyalty_stamps')->default(0)->after('loyalty_token');
            $table->unsignedInteger('loyalty_free_drinks')->default(0)->after('loyalty_stamps');
            $table->timestamp('loyalty_joined_at')->nullable()->after('loyalty_free_drinks');
        });

        Schema::create('loyalty_stamp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // earn|redeem|adjust|join
            $table->integer('stamps_delta')->default(0);
            $table->integer('free_delta')->default(0);
            $table->unsignedInteger('stamps_after')->default(0);
            $table->unsignedInteger('free_after')->default(0);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_stamp_logs');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_joined',
                'loyalty_token',
                'loyalty_stamps',
                'loyalty_free_drinks',
                'loyalty_joined_at',
            ]);
        });
    }
};
