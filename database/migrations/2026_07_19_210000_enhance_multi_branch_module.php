<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('branches', 'invoice_logo')) {
                $table->string('invoice_logo')->nullable()->after('email');
            }
            if (! Schema::hasColumn('branches', 'receipt_footer')) {
                $table->string('receipt_footer')->nullable()->after('invoice_logo');
            }
            if (! Schema::hasColumn('branches', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_main');
            }
        });

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->unique(['branch_id', 'user_id']);
            });
        }

        // Backfill pivot from legacy users.branch_id
        if (Schema::hasTable('branch_user') && Schema::hasColumn('users', 'branch_id')) {
            $rows = DB::table('users')
                ->whereNotNull('branch_id')
                ->whereNull('deleted_at')
                ->get(['id', 'branch_id']);

            $now = now();
            foreach ($rows as $row) {
                DB::table('branch_user')->insertOrIgnore([
                    'branch_id' => $row->branch_id,
                    'user_id' => $row->id,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['key' => 'multi_branch_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'branches', 'description' => 'Enable multi-branch module'],
            ['key' => 'max_branches', 'value' => '2', 'type' => 'integer', 'group' => 'branches', 'description' => 'Maximum branches restaurant may create'],
        ] as $row) {
            $exists = DB::table('settings')->where('key', $row['key'])->exists();
            if (! $exists) {
                DB::table('settings')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Ensure at least one main branch exists
        if (Schema::hasTable('branches') && DB::table('branches')->whereNull('deleted_at')->count() === 0) {
            DB::table('branches')->insert([
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'company_name' => DB::table('settings')->where('key', 'company_name')->value('value'),
                'is_active' => true,
                'is_main' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');

        Schema::table('branches', function (Blueprint $table) {
            foreach (['company_name', 'invoice_logo', 'receipt_footer', 'sort_order'] as $col) {
                if (Schema::hasColumn('branches', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::table('settings')->whereIn('key', ['multi_branch_enabled', 'max_branches'])->delete();
    }
};
