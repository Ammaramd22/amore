<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_payment_id')->nullable()->unique()->constrained('supplier_payments')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('cheque_number', 100);
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('cheque_date')->nullable();
            $table->date('due_date');
            $table->string('status', 20)->default('pending'); // pending|cleared|returned|cancelled
            $table->text('returned_reason')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->date('last_reminded_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
