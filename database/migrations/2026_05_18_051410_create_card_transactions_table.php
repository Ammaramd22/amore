<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('terminal_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'declined', 'cancelled', 'refunded'])->default('pending');
            $table->text('response_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
