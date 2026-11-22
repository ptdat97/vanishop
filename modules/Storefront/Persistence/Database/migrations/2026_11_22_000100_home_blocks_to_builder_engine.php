<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Builder Engine: cấu hình block lưu theo surface (`core.storefront.builder.{surface}`), ảnh đang dùng ghi nhận dưới
 * `storefront.builder` với role = mã surface. Chuyển trang chủ từ khoá cũ sang surface `home`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('namespace', 'core')->where('key', 'storefront.home_blocks')->update(['key' => 'storefront.builder.home']);
        DB::table('mediables')->where(['mediable_type' => 'storefront.home', 'mediable_id' => 1, 'role' => 'blocks'])
            ->update(['mediable_type' => 'storefront.builder', 'role' => 'home']);
    }

    public function down(): void
    {
        DB::table('settings')->where('namespace', 'core')->where('key', 'storefront.builder.home')->update(['key' => 'storefront.home_blocks']);
        DB::table('mediables')->where(['mediable_type' => 'storefront.builder', 'mediable_id' => 1, 'role' => 'home'])
            ->update(['mediable_type' => 'storefront.home', 'role' => 'blocks']);
    }
};
