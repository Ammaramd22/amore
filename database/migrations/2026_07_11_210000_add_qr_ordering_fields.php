<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (! Schema::hasColumn('tables', 'qr_code')) {
                $table->string('qr_code', 8)->nullable()->after('number')->index();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'source')) {
                $table->string('source', 20)->default('pos')->after('order_type')->index();
            }
            if (! Schema::hasColumn('orders', 'approval_status')) {
                $table->string('approval_status', 20)->nullable()->after('status')->index();
            }
        });

        // Backfill unique 4-digit table codes
        $used = [];
        $tables = DB::table('tables')->whereNull('deleted_at')->orderBy('id')->get(['id', 'qr_code']);
        foreach ($tables as $t) {
            if ($t->qr_code && ! isset($used[$t->qr_code])) {
                $used[$t->qr_code] = true;
                continue;
            }
            do {
                $code = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            } while (isset($used[$code]));
            $used[$code] = true;
            DB::table('tables')->where('id', $t->id)->update(['qr_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (Schema::hasColumn('tables', 'qr_code')) {
                $table->dropColumn('qr_code');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('orders', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
