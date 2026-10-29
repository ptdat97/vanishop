<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Liên kết sản phẩm và video sản phẩm — phỏng theo mô hình Links/Video của Vanilo (MIT, xem modules/Catalog/NOTICE).
*/
return new class extends Migration
{
    public function up(): void
    {
        // Loại liên kết: "similar", "cross-sell", "upsell"… — Owner tự định nghĩa.
        Schema::create('link_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique('code');
        });

        Schema::create('link_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['link_type_id', 'locale']);
        });

        // root_style_id = null: nhóm hai chiều (mọi thành viên thấy nhau); khác null: một chiều (chỉ style gốc thấy các thành viên).
        Schema::create('style_link_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('root_style_id')->nullable()->constrained('styles')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['link_type_id', 'root_style_id']);
        });

        Schema::create('style_link_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_link_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['style_link_group_id', 'style_id']);
            $table->index('style_id');
        });

        // Video ở nền tảng ngoài (YouTube…), driver do plugin cung cấp (VideoDriver). Dùng lại được cho nhiều sản phẩm.
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 32);
            $table->string('reference', 255);
            $table->string('title')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['driver', 'reference']);
        });

        Schema::create('style_video', function (Blueprint $table) {
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['style_id', 'video_id']);
            $table->index('video_id');
        });
    }

    public function down(): void
    {
        foreach (['style_video', 'videos', 'style_link_group_items', 'style_link_groups', 'link_type_translations', 'link_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
