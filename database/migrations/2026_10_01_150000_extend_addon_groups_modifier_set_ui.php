<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('addon_groups')) {
            Schema::table('addon_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('addon_groups', 'display_name')) {
                    $table->string('display_name')->nullable()->after('name');
                }
                if (! Schema::hasColumn('addon_groups', 'selection_type')) {
                    $table->string('selection_type', 32)->default('list')->after('display_name');
                }
                if (! Schema::hasColumn('addon_groups', 'require_selection')) {
                    $table->boolean('require_selection')->default(false)->after('selection_type');
                }
                if (! Schema::hasColumn('addon_groups', 'allow_multiple')) {
                    $table->boolean('allow_multiple')->default(true)->after('require_selection');
                }
                if (! Schema::hasColumn('addon_groups', 'hide_on_receipt')) {
                    $table->boolean('hide_on_receipt')->default(false)->after('allow_multiple');
                }
                if (! Schema::hasColumn('addon_groups', 'show_in_pos')) {
                    $table->boolean('show_in_pos')->default(true)->after('hide_on_receipt');
                }
            });
        }

        if (Schema::hasTable('addon_group_addon')) {
            Schema::table('addon_group_addon', function (Blueprint $table) {
                if (! Schema::hasColumn('addon_group_addon', 'is_preselected')) {
                    $table->boolean('is_preselected')->default(false)->after('display_order');
                }
                if (! Schema::hasColumn('addon_group_addon', 'is_available')) {
                    $table->boolean('is_available')->default(true)->after('is_preselected');
                }
            });
        }

        if (! Schema::hasTable('addon_group_branch')) {
            Schema::create('addon_group_branch', function (Blueprint $table) {
                $table->id();
                $table->foreignId('addon_group_id')->constrained('addon_groups')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['addon_group_id', 'branch_id']);
            });
        }

        if (Schema::hasTable('order_item_addons') && ! Schema::hasColumn('order_item_addons', 'hide_on_receipt')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                $table->boolean('hide_on_receipt')->default(false)->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_item_addons') && Schema::hasColumn('order_item_addons', 'hide_on_receipt')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                $table->dropColumn('hide_on_receipt');
            });
        }

        Schema::dropIfExists('addon_group_branch');

        if (Schema::hasTable('addon_group_addon')) {
            Schema::table('addon_group_addon', function (Blueprint $table) {
                if (Schema::hasColumn('addon_group_addon', 'is_available')) {
                    $table->dropColumn('is_available');
                }
                if (Schema::hasColumn('addon_group_addon', 'is_preselected')) {
                    $table->dropColumn('is_preselected');
                }
            });
        }

        if (Schema::hasTable('addon_groups')) {
            Schema::table('addon_groups', function (Blueprint $table) {
                foreach (['show_in_pos', 'hide_on_receipt', 'allow_multiple', 'require_selection', 'selection_type', 'display_name'] as $col) {
                    if (Schema::hasColumn('addon_groups', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
