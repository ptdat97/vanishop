<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * vani.cms 1.5 (đặc tả store-content.md §4): nội dung thêm theo sản phẩm (điểm nổi bật theo ngôn ngữ, ẩn hộp ưu đãi)
 * và banner đầu trang danh mục. Thanh thông báo, hộp ưu đãi dùng chung lưu ở cấu hình plugin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_cms_product_extras', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('style_id')->unique();
            $table->json('highlights')->nullable();
            $table->boolean('hide_promo_box')->default(false);
            $table->timestamps();
        });

        Schema::create('plg_cms_category_banners', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->unique();
            $table->json('content')->nullable();
            $table->string('button_url', 500)->nullable();
            $table->unsignedBigInteger('desktop_media_id')->nullable();
            $table->unsignedBigInteger('mobile_media_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_cms_category_banners');
        Schema::dropIfExists('plg_cms_product_extras');
    }
};
