<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type', 20)->default('other'); // cash, bank, other
            $table->json('payment_methods')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('direction', 10); // in, out
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();

            $table->index(['account_id', 'transacted_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('amount')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('accounts');
    }
};
