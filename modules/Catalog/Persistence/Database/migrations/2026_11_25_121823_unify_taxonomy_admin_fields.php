<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 0.5.86: thương hiệu, danh mục, bộ sưu tập dùng chung một khuôn Admin. Bộ sưu tập có thêm ảnh bìa (Thư viện ảnh),
 * khoá lạc quan và SEO theo ngôn ngữ như danh mục.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->unsignedBigInteger('image_media_id')->nullable()->after('position');
            $table->unsignedInteger('lock_version')->default(0)->after('image_media_id');
        });
        Schema::table('collection_translations', function (Blueprint $table): void {
            $table->string('meta_title')->nullable()->after('description');
            $table->string('meta_description', 512)->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('collection_translations', function (Blueprint $table): void {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
        Schema::table('collections', function (Blueprint $table): void {
            $table->dropColumn(['image_media_id', 'lock_version']);
        });
    }
};
