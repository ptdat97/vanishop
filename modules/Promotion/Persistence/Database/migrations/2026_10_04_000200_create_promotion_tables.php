<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('status', 16)->default('active');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->integer('priority')->default(0);
            $table->string('stacking', 16)->default('combinable'); // exclusive | combinable
            $table->boolean('requires_voucher')->default(true);
            // Một action mỗi khuyến mãi (percent_off | amount_off | action do plugin đăng ký).
            $table->string('action_type', 64);
            $table->json('action_config');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->bigInteger('budget_amount')->nullable();
            $table->bigInteger('budget_used_amount')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['brand_id', 'status']);
        });

        // Điều kiện do plugin cung cấp (rule_type). Mọi rule phải thoả; không có rule = áp cho cả giỏ của brand.
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type', 64);
            $table->json('config');
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64)->unique(); // chữ hoa, không khoảng trắng
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->restrictOnDelete();
            // Không FK sang orders: Promotion là upstream của Ordering.
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->bigInteger('discount_amount');
            $table->char('currency_code', 3);
            $table->string('status', 16)->default('applied'); // applied | reverted
            $table->timestamps();

            $table->unique(['promotion_id', 'order_id']);
            $table->index('order_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vouchers ADD CONSTRAINT vouchers_usage_within_limit CHECK (usage_limit IS NULL OR used_count <= usage_limit)');
            DB::statement('ALTER TABLE promotions ADD CONSTRAINT promotions_usage_within_limit CHECK (usage_limit IS NULL OR usage_count <= usage_limit)');
            DB::statement('ALTER TABLE promotions ADD CONSTRAINT promotions_budget_within_limit CHECK (budget_amount IS NULL OR budget_used_amount <= budget_amount)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('promotions');
    }
};
