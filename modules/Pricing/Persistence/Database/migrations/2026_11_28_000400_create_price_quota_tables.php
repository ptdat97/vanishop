<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Suất giá (0.5.117, PriceQuotas): giới hạn số lượng bán theo giá của một bảng giá — cả bảng (`list`), theo sản phẩm
 * (`product`, mọi variant cộng chung) hoặc theo variant — và giới hạn mỗi khách. `sold` / `used` tăng bằng UPDATE có điều
 * kiện trong transaction đặt hàng; huỷ đơn hoàn lại. Hết suất → mức giá không tham gia giải giá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 16); // list | product | variant
            $table->unsignedBigInteger('scope_id')->default(0); // 0 khi scope = list
            $table->unsignedInteger('quota_limit')->nullable(); // null = không giới hạn tổng (chỉ giới hạn mỗi khách)
            $table->unsignedInteger('sold')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->timestamps();
            $table->unique(['price_list_id', 'scope', 'scope_id']);
        });
        Schema::create('price_quota_customers', function (Blueprint $table) {
            $table->foreignId('price_quota_id')->constrained('price_quotas')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();
            $table->primary(['price_quota_id', 'customer_id']);
        });
        Schema::create('price_quota_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_quota_id')->constrained('price_quotas')->cascadeOnDelete();
            // Không FK sang orders: Pricing là upstream của Ordering.
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->index('order_id');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE price_quotas ADD CONSTRAINT price_quotas_sold_within_limit CHECK (quota_limit IS NULL OR sold <= quota_limit)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_quota_usages');
        Schema::dropIfExists('price_quota_customers');
        Schema::dropIfExists('price_quotas');
    }
};
