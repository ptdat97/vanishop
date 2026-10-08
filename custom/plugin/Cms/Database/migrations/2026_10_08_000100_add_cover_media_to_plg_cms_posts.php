<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Contracts\MediaDirectory;

/*
 * vani.cms 1.1.0: ảnh bìa và ảnh trong bài lấy từ Thư viện ảnh dùng chung (Core MediaDirectory). `cover_path` (ảnh tải
 * riêng của CMS 1.0) giữ lại để hiển thị bài cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plg_cms_posts', function (Blueprint $table): void {
            $table->unsignedBigInteger('cover_media_id')->nullable()->after('cover_path');
        });
    }

    public function down(): void
    {
        // Gỡ plugin xoá dữ liệu: bỏ ghi nhận "đang dùng" để Thư viện ảnh xoá được các ảnh này.
        foreach (['plg.cms.post', 'plg.cms.page'] as $ownerType) {
            app(MediaDirectory::class)->releaseUsages($ownerType);
        }
        Schema::table('plg_cms_posts', function (Blueprint $table): void {
            $table->dropColumn('cover_media_id');
        });
    }
};
