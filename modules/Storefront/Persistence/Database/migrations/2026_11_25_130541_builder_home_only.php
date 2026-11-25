<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * 0.5.87: Builder chỉ còn cho trang chủ. Xoá cấu hình block của các surface khác (trang sản phẩm, danh sách, giỏ,
 * thương hiệu, surface của plugin), ghi nhận ảnh của chúng và bảng block theo phạm vi. Giai đoạn dev: không chuyển dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('namespace', 'core')->where('key', 'like', 'storefront.builder.%')
            ->where('key', '!=', 'storefront.builder.home')->delete();
        DB::table('mediables')->where('mediable_type', 'storefront.builder')->where('role', '!=', 'home')->delete();
        DB::table('mediables')->where('mediable_type', 'storefront.builder_scoped')->delete();
        Schema::dropIfExists('builder_scoped_blocks');
    }

    public function down(): void
    {
        // Không khôi phục dữ liệu đã xoá.
    }
};
