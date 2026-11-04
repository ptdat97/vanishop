<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Ngôn ngữ khách hay dùng (ADR-038 §8): cập nhật khi khách đặt hàng hoặc sửa hồ sơ. Null = chưa biết.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('preferred_locale', 8)->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('preferred_locale');
        });
    }
};
