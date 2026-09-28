<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY user_type ENUM('admin','manager','cashier','waiter','kitchen','delivery','staff','software_owner') NOT NULL DEFAULT 'staff'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::table('users')->where('user_type', 'software_owner')->update(['user_type' => 'admin']);
            DB::statement("ALTER TABLE users MODIFY user_type ENUM('admin','manager','cashier','waiter','kitchen','delivery','staff') NOT NULL DEFAULT 'staff'");
        }
    }
};
