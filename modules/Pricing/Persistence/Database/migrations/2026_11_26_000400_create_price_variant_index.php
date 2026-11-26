<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Read model SKU cho màn hình bảng giá (db-refactor L55): bản sao thứ tự hiển thị, trạng thái và văn bản tìm của SKU
| (PriceVariantIndexer đồng bộ qua event ProductListingProjected của Catalog) — trang làm việc của bảng giá, tìm SKU để thêm,
| lịch sử giá, "SKU chưa có giá niêm yết" lọc/sắp xếp/phân trang bằng SQL, không nạp mọi SKU. Không FK sang Catalog.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_variant_index', function (Blueprint $table): void {
            $table->unsignedBigInteger('variant_id')->primary();
            $table->unsignedBigInteger('product_id');
            $table->string('product_code', 64);
            $table->string('name_sort', 191);
            $table->unsignedInteger('variant_rank');
            $table->string('status', 16);
            $table->string('search_text', 512);
            $table->index(['name_sort', 'product_code', 'product_id', 'variant_rank'], 'price_variant_sort');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_variant_index');
    }
};
