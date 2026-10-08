<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_partners', 'collection_type')) {
                $table->string('collection_type', 20)->default('partner')->after('settlement_cycle');
                // own = restaurant collects (pay now / COD daily)
                // partner = platform/partner remits later (weekly) — no POS payment
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_partners', 'collection_type')) {
                $table->dropColumn('collection_type');
            }
        });
    }
};
