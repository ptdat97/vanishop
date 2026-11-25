<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 0.5.86: block Builder theo phạm vi (ScopedBuilderSurface), vd. nội dung riêng của từng thương hiệu/danh mục/bộ sưu tập.
 * Bảng riêng thay vì Settings: Settings nạp cả namespace mỗi request, còn trang chỉ cần đúng một dòng (surface, scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_scoped_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('surface', 40);
            $table->string('scope', 64);
            $table->json('blocks');
            $table->timestamps();
            $table->unique(['surface', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_scoped_blocks');
    }
};
