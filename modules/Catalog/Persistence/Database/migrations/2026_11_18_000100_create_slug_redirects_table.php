<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Slug cũ của sản phẩm/danh mục/thương hiệu (stabilization S3): đổi slug ở Admin → link cũ (Google đã index, khách đã chia
| sẻ) chuyển hướng 301 sang slug mới thay vì 404. Một slug cũ trỏ tới một bản ghi; bản ghi khác lấy lại slug đó thì dòng bị
| xoá (slug thuộc bản ghi đang dùng). Chỉ slug gốc (ngôn ngữ mặc định).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('subject', 16)->comment('product | category | brand');
            $table->unsignedBigInteger('subject_id');
            $table->string('slug', 191)->comment('Slug cũ');
            $table->timestamps();
            $table->unique(['subject', 'slug']);
            $table->index(['subject', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
