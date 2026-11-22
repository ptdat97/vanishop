<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Khuyến mãi do plugin quản lý (ManagedPromotions): `managed_by` = id plugin, `managed_key` = khoá của plugin,
 * `managed_route` = route Admin của plugin để sửa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('managed_by', 64)->nullable()->after('campaign_id');
            $table->string('managed_key', 64)->nullable()->after('managed_by');
            $table->string('managed_route', 128)->nullable()->after('managed_key');
            $table->unique(['managed_by', 'managed_key']);
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropUnique(['managed_by', 'managed_key']);
            $table->dropColumn(['managed_by', 'managed_key', 'managed_route']);
        });
    }
};
