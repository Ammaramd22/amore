<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_number')->nullable()->after('id');
        });

        $rows = DB::table('purchases')->orderBy('id')->get(['id']);
        foreach ($rows as $row) {
            DB::table('purchases')->where('id', $row->id)->update([
                'purchase_number' => 'PUR-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->unique('purchase_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['purchase_number']);
            $table->dropColumn('purchase_number');
        });
    }
};
