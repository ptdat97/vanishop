<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * vani.cms 1.4 (reference-parity §3.17-1/2): chuyên mục tin tức một cấp (URL /tin-tuc/{slug}, không trùng slug bài viết
 * — kiểm khi lưu) và sản phẩm gắn vào bài (tối đa 12 id mẫu, giữ thứ tự; mẫu ẩn khi hiển thị thì bỏ qua).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_cms_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('plg_cms_posts', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable()->after('id')->index();
            $table->json('product_ids')->nullable()->after('cover_media_id');
        });
    }

    public function down(): void
    {
        Schema::table('plg_cms_posts', function (Blueprint $table): void {
            $table->dropIndex(['category_id']);
            $table->dropColumn(['category_id', 'product_ids']);
        });
        Schema::dropIfExists('plg_cms_categories');
    }
};
