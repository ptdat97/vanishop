<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Read model cho trang danh sách và facet (db-refactor §4.3): ProductListingProjector ghi trong cùng transaction với thay
| đổi của Catalog; `list_price_min` do Pricing ghi (contract ListPrices), `in_stock` do Inventory ghi (ListingAvailability).
| Dựng lại: `php artisan vani:catalog:rebuild-listing`.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_listing', function (Blueprint $table): void {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('status', 16);
            $table->dateTime('published_from')->nullable();
            $table->dateTime('published_to')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->bigInteger('list_price_min')->nullable();   // giá niêm yết thấp nhất (bản sao cho lọc/sắp xếp, không phải giá bán)
            $table->boolean('in_stock')->default(true);         // còn ít nhất một SKU bán được
            $table->dateTime('created_at');
            $table->index(['status', 'published_from']);
            $table->index(['status', 'list_price_min', 'product_id']);
            $table->index(['status', 'created_at', 'product_id']);
            $table->index(['brand_id', 'status']);
        });

        // facet: 'category' (gồm danh mục cha), 'collection', 'attribute:<attribute id>', 'option:<mã loại>'.
        // token: id danh mục / bộ sưu tập / giá trị thuộc tính, hoặc filter_token của giá trị tuỳ chọn (SKU đang bán).
        Schema::create('product_facet_values', function (Blueprint $table): void {
            $table->string('facet', 48);
            $table->string('token', 64);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['facet', 'token', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_facet_values');
        Schema::dropIfExists('product_listing');
    }
};
