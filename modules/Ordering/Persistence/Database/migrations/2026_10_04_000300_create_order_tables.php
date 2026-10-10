<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ADR-012: đơn là bản ghi bất biến, giữ snapshot; không phụ thuộc catalog hiện tại.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('number', 32)->unique();
            $table->string('source', 16)->default('web'); // web | app | zalo | admin | pos | exchange
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // FK khi có module Customer
            $table->char('currency_code', 3);
            $table->string('order_status', 16);
            $table->string('payment_status', 24);
            $table->string('fulfillment_status', 24);
            $table->string('return_status', 24)->default('none');
            $table->string('payment_method', 64);
            $table->bigInteger('subtotal_amount');
            $table->bigInteger('discount_amount');
            $table->bigInteger('shipping_amount');
            $table->bigInteger('tax_amount'); // thuế đã gồm trong giá
            $table->bigInteger('total_amount');
            $table->json('customer_snapshot');
            $table->json('shipping_address');
            $table->json('shipping_method');
            $table->string('note', 500)->nullable();
            $table->string('reservation_key', 64);
            $table->string('source_cart_id', 26)->nullable()->index();
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index('placed_at');
            $table->index('order_status');
            $table->index(['source', 'placed_at']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->index(); // không FK: đơn không phụ thuộc catalog
            $table->string('sku', 64);
            $table->string('product_name');
            $table->unsignedBigInteger('brand_id')->nullable()->index(); // snapshot thương hiệu (không FK)
            $table->string('brand_name')->nullable();
            $table->string('color_name')->nullable();
            $table->string('size_code', 32);
            $table->string('image_url')->nullable();
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_amount');
            $table->bigInteger('compare_at_amount')->nullable();
            $table->bigInteger('subtotal_amount');
            $table->bigInteger('discount_amount');
            $table->bigInteger('total_amount');
            $table->unsignedInteger('tax_rate_bp');
            $table->bigInteger('tax_amount');
            $table->timestamps();
        });

        Schema::create('order_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);   // promotion | shipping | fee …
            $table->string('source', 64); // core | plugin id
            $table->string('code', 64)->nullable();
            $table->string('label');
            $table->bigInteger('amount'); // âm = giảm
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Append-only: mọi thay đổi trạng thái/địa chỉ/ghi chú của đơn.
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->string('reason')->nullable();
            $table->string('actor_type', 32)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('source', 64);
            $table->string('correlation_id', 64)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at', 6);

            $table->index(['order_id', 'id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_not_negative CHECK (total_amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_adjustments');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
