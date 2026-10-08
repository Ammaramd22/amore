<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sms', 'whatsapp', 'email']);
            $table->string('provider', 32); // notify_lk, green_api, email, …
            $table->string('to');
            $table->text('message')->nullable();
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->foreignId('reference_id')->nullable(); // order_id or customer_id
            $table->string('reference_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
