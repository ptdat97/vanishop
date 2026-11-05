<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Danh sách yêu thích của khách (docs/05-plugin/specs/wishlist.md §3): một dòng mỗi (khách, mẫu). Không khoá ngoại
 * sang bảng Core (ADR-027). price_amount / in_stock: trạng thái lần kiểm gần nhất để báo giảm giá / có hàng lại.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('style_id');
            $table->unsignedBigInteger('price_amount')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->timestamp('notified_stock_at')->nullable();
            $table->timestamp('notified_price_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'style_id']);
            $table->index('style_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_wishlist_items');
    }
};
