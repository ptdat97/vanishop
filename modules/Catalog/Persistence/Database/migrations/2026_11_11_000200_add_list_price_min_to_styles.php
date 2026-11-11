<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Giá niêm yết thấp nhất của sản phẩm (đơn vị nhỏ nhất của tiền tệ cửa hàng) — bản sao để lọc/sắp xếp theo giá ở trang
| danh sách (backlog L8). Nguồn là bảng giá `base` của Pricing, ghi sang qua contract `ListPrices`; null = chưa có giá.
| Sau khi migrate chạy `php artisan vani:pricing:sync-list-prices` một lần để điền cho sản phẩm đang có.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->bigInteger('list_price_min')->nullable()->after('search_text');
            $table->index('list_price_min');
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->dropIndex(['list_price_min']);
            $table->dropColumn('list_price_min');
        });
    }
};
