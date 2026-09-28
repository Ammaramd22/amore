<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_product')) {
            Schema::create('branch_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['branch_id', 'product_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 60)->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'login_code')) {
                $table->string('login_code', 4)->nullable()->unique()->after('username');
            }
        });

        // Attach existing products to all active branches (default = all)
        if (Schema::hasTable('branch_product') && Schema::hasTable('products') && Schema::hasTable('branches')) {
            $branchIds = DB::table('branches')->whereNull('deleted_at')->where('is_active', 1)->pluck('id');
            $productIds = DB::table('products')->whereNull('deleted_at')->pluck('id');
            $now = now();
            foreach ($productIds as $pid) {
                foreach ($branchIds as $bid) {
                    DB::table('branch_product')->insertOrIgnore([
                        'branch_id' => $bid,
                        'product_id' => $pid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'login_code')) {
                $table->dropColumn('login_code');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
