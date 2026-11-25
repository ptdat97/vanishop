<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * 0.5.86: nội dung Builder riêng của bộ sưu tập chuyển sang plugin (ScopedBuilderSurface, bảng builder_scoped_blocks),
 * dùng chung cơ chế với thương hiệu và danh mục. Giai đoạn dev: không chuyển dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->dropColumn('page_blocks');
        });
        DB::table('mediables')->where('mediable_type', 'catalog.collection_page')->delete();
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->json('page_blocks')->nullable();
        });
    }
};
