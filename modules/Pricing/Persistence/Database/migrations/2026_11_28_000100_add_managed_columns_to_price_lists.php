<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Bảng giá do plugin quản lý (ManagedPriceLists, 0.5.115): `managed_by` = id plugin, `managed_key` = khoá của plugin,
 * `managed_route` = route Admin của plugin để sửa. Admin → Bảng giá chỉ xem bảng giá này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->string('managed_by', 64)->nullable()->after('status');
            $table->string('managed_key', 64)->nullable()->after('managed_by');
            $table->string('managed_route', 128)->nullable()->after('managed_key');
            $table->unique(['managed_by', 'managed_key']);
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropUnique(['managed_by', 'managed_key']);
            $table->dropColumn(['managed_by', 'managed_key', 'managed_route']);
        });
    }
};
