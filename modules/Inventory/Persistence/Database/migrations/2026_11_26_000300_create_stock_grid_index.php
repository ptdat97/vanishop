<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Read model của lưới tồn Admin (db-refactor G3): bản sao những gì lưới cần từ Catalog (StockGridIndexer đồng bộ qua event
| ProductListingProjected) + số bán được online (cập nhật khi AvailabilityChanged). Lọc, sắp xếp, phân trang, đếm "việc cần
| làm" bằng SQL — không nạp mọi SKU vào bộ nhớ. Không FK sang Catalog (bảng của module khác).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_grid_index', function (Blueprint $table): void {
            $table->unsignedBigInteger('variant_id')->primary();
            $table->unsignedBigInteger('product_id');
            $table->string('product_code', 64);
            $table->string('name_sort', 191);            // tên sản phẩm chữ thường (ngôn ngữ mặc định), rỗng → mã
            $table->unsignedInteger('variant_rank');     // thứ tự SKU trong sản phẩm (theo thứ tự trục / giá trị)
            $table->string('status', 16);
            $table->boolean('tracked')->default(true);
            $table->string('search_text', 512);          // sku + mã + tên, chữ thường — tìm "chứa"
            $table->unsignedInteger('sellable')->default(0); // Σ bán được ở kho giao online
            $table->index(['name_sort', 'product_code', 'product_id', 'variant_rank'], 'stock_grid_sort');
            $table->index('product_id');
            $table->index(['status', 'tracked', 'sellable'], 'stock_grid_attention');
        });

        Schema::create('stock_grid_options', function (Blueprint $table): void {
            $table->string('type_code', 32);
            $table->string('value_code', 32);
            $table->unsignedBigInteger('variant_id');
            $table->primary(['type_code', 'value_code', 'variant_id']);
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_grid_options');
        Schema::dropIfExists('stock_grid_index');
    }
};
