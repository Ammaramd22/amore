<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_partner_product_prices')) {
            Schema::create('delivery_partner_product_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('delivery_partner_id');
                $table->decimal('price', 12, 2);
                $table->timestamps();
                $table->foreign('delivery_partner_id', 'dppp_partner_fk')
                    ->references('id')->on('delivery_partners')->cascadeOnDelete();
                $table->unique(['product_id', 'delivery_partner_id'], 'dppp_product_partner_uq');
            });
        }

        if (Schema::hasTable('delivery_partners') && ! Schema::hasColumn('delivery_partners', 'settlement_cycle')) {
            Schema::table('delivery_partners', function (Blueprint $table) {
                $table->string('settlement_cycle', 20)->default('weekly')->after('commission_type');
                $table->boolean('tracks_settlement')->default(true)->after('settlement_cycle');
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'partner_due_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'delivery_partner_fee')) {
                    $table->decimal('delivery_partner_fee', 12, 2)->default(0)->after('delivery_charge');
                }
                $table->decimal('partner_due_amount', 12, 2)->default(0)->after('delivery_partner_fee');
                $table->string('partner_settlement_status', 20)->default('none')->after('partner_due_amount');
                $table->decimal('partner_settled_amount', 12, 2)->default(0)->after('partner_settlement_status');
            });
        }

        if (! Schema::hasTable('delivery_partner_payments')) {
            Schema::create('delivery_partner_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_partner_id');
                $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('method', 30)->default('bank_transfer');
                $table->date('payment_date');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->foreign('delivery_partner_id', 'dppay_partner_fk')
                    ->references('id')->on('delivery_partners')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('delivery_partner_payment_orders')) {
            // May exist without FKs from failed run — drop and recreate
            Schema::drop('delivery_partner_payment_orders');
        }

        Schema::create('delivery_partner_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_partner_payment_id');
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->foreign('delivery_partner_payment_id', 'dppo_payment_fk')
                ->references('id')->on('delivery_partner_payments')->cascadeOnDelete();
        });

        if (! Schema::hasTable('delivery_partner_ledger_entries')) {
            Schema::create('delivery_partner_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_partner_id');
                $table->string('type', 20);
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('delivery_partner_payment_id')->nullable();
                $table->decimal('debit', 12, 2)->default(0);
                $table->decimal('credit', 12, 2)->default(0);
                $table->decimal('balance_after', 12, 2)->default(0);
                $table->string('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->foreign('delivery_partner_id', 'dple_partner_fk')
                    ->references('id')->on('delivery_partners')->cascadeOnDelete();
                $table->foreign('delivery_partner_payment_id', 'dple_payment_fk')
                    ->references('id')->on('delivery_partner_payments')->nullOnDelete();
                $table->index(['delivery_partner_id', 'created_at'], 'dple_partner_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partner_ledger_entries');
        Schema::dropIfExists('delivery_partner_payment_orders');
        Schema::dropIfExists('delivery_partner_payments');

        if (Schema::hasColumn('orders', 'partner_due_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['partner_due_amount', 'partner_settlement_status', 'partner_settled_amount']);
            });
        }

        if (Schema::hasColumn('delivery_partners', 'settlement_cycle')) {
            Schema::table('delivery_partners', function (Blueprint $table) {
                $table->dropColumn(['settlement_cycle', 'tracks_settlement']);
            });
        }

        Schema::dropIfExists('delivery_partner_product_prices');
    }
};
