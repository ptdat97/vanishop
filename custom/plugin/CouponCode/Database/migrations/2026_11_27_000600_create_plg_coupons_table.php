<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Promotion\Contracts\ManagedPromotions;
use Plugin\CouponCode\CouponCodeServiceProvider;

/*
 * vani.coupon-code: nội dung form mã giảm giá. Lịch, lượt, voucher, lượt mỗi khách, ghi nhận trên đơn ở Core Promotion
 * (khuyến mãi do plugin quản lý — `promotion_id`, không khoá ngoại sang bảng Core, ADR-027).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->string('kind', 16); // shared | template
            $table->string('code', 64)->nullable(); // mã dùng chung chính (mã sinh thêm nằm ở `vouchers` của Core)
            $table->string('prefix', 16)->nullable(); // tiền tố mã riêng của mã mẫu
            $table->string('name', 120);
            $table->json('translations'); // {locale: {description}} — chữ cho khách
            $table->string('discount_type', 16); // percent | amount
            $table->unsignedInteger('discount_value');
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_subtotal')->nullable();
            $table->string('scope_type', 16)->default('all'); // all | products | categories | collections | brands
            $table->json('scope_values'); // id sản phẩm/danh mục/bộ sưu tập/thương hiệu
            $table->json('scope_slugs'); // slug bộ sưu tập/thương hiệu lúc lưu (rule in_collections/in_brands theo slug)
            $table->boolean('exclude_on_sale')->default(false);
            $table->json('customer_groups');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_per_customer')->nullable();
            $table->unsignedSmallInteger('valid_days')->nullable(); // mã riêng phát từ mã mẫu
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('exclusive')->default(false);
            $table->boolean('public')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['public', 'active']);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $promotions = app(ManagedPromotions::class);
            foreach (array_keys($promotions->ids(CouponCodeServiceProvider::ID)) as $key) {
                $promotions->remove(CouponCodeServiceProvider::ID, (string) $key);
            }
        });

        Schema::dropIfExists('plg_coupons');
    }
};
