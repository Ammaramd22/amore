<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('addon_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_id')->constrained('addons')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['addon_id', 'product_id']);
        });

        if (Schema::hasTable('order_item_addons') && ! Schema::hasColumn('order_item_addons', 'addon_id')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                $table->foreignId('addon_id')->nullable()->after('product_addon_id')->constrained('addons')->nullOnDelete();
            });
        }

        // Migrate existing per-product add-ons into shared catalog + pivot (preserve order history)
        if (Schema::hasTable('product_addons')) {
            $rows = DB::table('product_addons')->orderBy('id')->get();
            $map = []; // product_addon_id => addon_id

            foreach ($rows as $row) {
                $existingId = DB::table('addons')
                    ->where('name', $row->name)
                    ->where('price', $row->price)
                    ->value('id');

                if ($existingId) {
                    $addonId = (int) $existingId;
                } else {
                    $addonId = (int) DB::table('addons')->insertGetId([
                        'name' => $row->name,
                        'price' => $row->price,
                        'is_active' => (bool) $row->is_active,
                        'display_order' => 0,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }

                $map[(int) $row->id] = $addonId;

                $exists = DB::table('addon_product')
                    ->where('addon_id', $addonId)
                    ->where('product_id', $row->product_id)
                    ->exists();

                if (! $exists) {
                    DB::table('addon_product')->insert([
                        'addon_id' => $addonId,
                        'product_id' => $row->product_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (Schema::hasColumn('order_item_addons', 'addon_id') && ! empty($map)) {
                foreach ($map as $productAddonId => $addonId) {
                    DB::table('order_item_addons')
                        ->where('product_addon_id', $productAddonId)
                        ->whereNull('addon_id')
                        ->update(['addon_id' => $addonId]);
                }
            }
        }

        // Allow historical product_addon_id to become nullable for new shared-addon orders
        if (Schema::hasTable('order_item_addons')) {
            // Prefer raw SQL — no doctrine/dbal required on shared hosting
            try {
                $fkRows = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'order_item_addons'
                      AND COLUMN_NAME = 'product_addon_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($fkRows as $fk) {
                    DB::statement('ALTER TABLE order_item_addons DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
                }
            } catch (\Throwable $e) {
                // ignore if already dropped
            }

            DB::statement('ALTER TABLE order_item_addons MODIFY product_addon_id BIGINT UNSIGNED NULL');

            try {
                Schema::table('order_item_addons', function (Blueprint $table) {
                    $table->foreign('product_addon_id')
                        ->references('id')
                        ->on('product_addons')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // ignore if FK already exists
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_item_addons') && Schema::hasColumn('order_item_addons', 'addon_id')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                $table->dropForeign(['addon_id']);
                $table->dropColumn('addon_id');
            });
        }

        Schema::dropIfExists('addon_product');
        Schema::dropIfExists('addons');
    }
};
