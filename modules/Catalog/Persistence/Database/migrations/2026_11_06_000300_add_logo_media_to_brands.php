<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 0.3.90: logo thương hiệu chọn từ Thư viện ảnh (id ảnh, có bản thu nhỏ). `logo_path` (file cũ) chỉ còn đọc khi chưa chọn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->unsignedBigInteger('logo_media_id')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('logo_media_id');
        });
    }
};
