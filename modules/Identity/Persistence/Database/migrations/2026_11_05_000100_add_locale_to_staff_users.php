<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Ngôn ngữ giao diện Admin riêng của nhân viên (reference-parity §3.3-3): null = ngôn ngữ mặc định của cửa hàng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table): void {
            $table->string('locale', 8)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
