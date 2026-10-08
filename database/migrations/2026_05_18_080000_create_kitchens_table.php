<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create kitchens table
        Schema::create('kitchens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->string('printer_name')->nullable();
            $table->string('printer_ip')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add kitchen_id to categories
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('kitchen_id')->nullable()->constrained()->onDelete('set null');
        });

        // Update kitchen_orders to reference kitchens
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->foreignId('kitchen_id')->nullable()->constrained()->onDelete('set null');
        });

        // Add KOT print settings to settings
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('kot_print_dine_in')->default(true);
            $table->boolean('kot_print_takeaway')->default(true);
            $table->boolean('kot_print_delivery')->default(true);
            $table->boolean('kot_print_express')->default(true);
            $table->boolean('kot_separate_by_kitchen')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['kot_print_dine_in', 'kot_print_takeaway', 'kot_print_delivery', 'kot_print_express', 'kot_separate_by_kitchen']);
        });

        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->dropForeign(['kitchen_id']);
            $table->dropColumn('kitchen_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['kitchen_id']);
            $table->dropColumn('kitchen_id');
        });

        Schema::dropIfExists('kitchens');
    }
};
