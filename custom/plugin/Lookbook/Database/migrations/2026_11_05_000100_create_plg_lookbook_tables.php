<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * vani.lookbook (docs/05-plugin/specs/lookbook.md §4): lookbook + ảnh theo thứ tự. Ảnh là id trong Thư viện ảnh của Core,
 * danh mục là id danh mục — không khoá ngoại sang bảng Core (ADR-027); đối tượng đã xoá thì bỏ qua khi hiển thị.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_lookbooks', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->json('translations');
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['active', 'position']);
        });

        Schema::create('plg_lookbook_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lookbook_id')->constrained('plg_lookbooks')->cascadeOnDelete();
            $table->unsignedBigInteger('media_id');
            $table->json('alt')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->index(['lookbook_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_lookbook_images');
        Schema::dropIfExists('plg_lookbooks');
    }
};
