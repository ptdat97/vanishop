<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            // sha256 của cart token (khách vãng lai giữ token; server không lưu token gốc).
            $table->char('token_hash', 64);
            $table->foreignId('channel_id')->constrained('channels');
            // Chưa có FK: module Customer chưa tồn tại (thêm khi có bảng customers).
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->char('currency_code', 3);
            $table->string('status', 16)->default('active');
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->index(['status', 'last_activity_at']);
        });

        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants');
            $table->foreignId('brand_id')->constrained('brands');
            $table->unsignedInteger('quantity');
            // Giá lúc thêm vào giỏ (đơn vị nhỏ nhất) — chỉ để cảnh báo "giá đã đổi", không dùng tính tiền.
            $table->bigInteger('unit_price_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'variant_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cart_lines ADD CONSTRAINT cart_lines_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
        Schema::dropIfExists('carts');
    }
};
