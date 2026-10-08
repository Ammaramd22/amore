<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchens', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchens', 'printer_port')) {
                $table->unsignedSmallInteger('printer_port')->default(9100)->after('printer_ip');
            }
            if (! Schema::hasColumn('kitchens', 'print_mode')) {
                $table->string('print_mode', 20)->default('preview')->after('printer_port');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kitchens', function (Blueprint $table) {
            if (Schema::hasColumn('kitchens', 'print_mode')) {
                $table->dropColumn('print_mode');
            }
            if (Schema::hasColumn('kitchens', 'printer_port')) {
                $table->dropColumn('printer_port');
            }
        });
    }
};
