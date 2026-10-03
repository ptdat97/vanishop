<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Dữ liệu riêng của plugin vani.cms (ADR-027): trang nội dung và bài viết. Nội dung là Markdown (render bỏ HTML thô).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['plg_cms_pages', 'plg_cms_posts'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('slug', 120)->unique();
                $table->string('title', 200);
                if ($name === 'plg_cms_posts') {
                    $table->string('excerpt', 500)->nullable();
                    $table->string('cover_path')->nullable();
                } else {
                    $table->boolean('show_in_header')->default(false);
                    $table->boolean('show_in_footer')->default(false);
                    $table->unsignedSmallInteger('sort_order')->default(0);
                }
                $table->mediumText('body');
                $table->string('meta_title', 200)->nullable();
                $table->string('meta_description', 300)->nullable();
                $table->string('status', 16)->default('draft'); // draft | published
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'published_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_cms_posts');
        Schema::dropIfExists('plg_cms_pages');
    }
};
