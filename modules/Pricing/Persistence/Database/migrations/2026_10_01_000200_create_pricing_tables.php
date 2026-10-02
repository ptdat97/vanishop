<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->char('currency_code', 3)->default('VND');
            $table->string('type', 16);                  // base | sale | member
            $table->unsignedBigInteger('customer_group_id')->nullable();   // giá thành viên (Designed)
            $table->integer('priority')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount');                // minor unit (VND: đồng) — không dùng float/decimal
            $table->bigInteger('compare_at_amount')->nullable();
            $table->unsignedInteger('min_qty')->default(1);
            $table->timestamps();
            $table->unique(['price_list_id', 'variant_id', 'min_qty']);
            $table->index(['variant_id']);
        });

        // Lịch sử giá append-only: chứng minh giá trước khuyến mãi là giá thực tế đã bán.
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('variant_id');
            $table->bigInteger('old_amount')->nullable();
            $table->bigInteger('new_amount')->nullable();
            $table->bigInteger('old_compare_at_amount')->nullable();
            $table->bigInteger('new_compare_at_amount')->nullable();
            $table->string('actor_type', 16)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['variant_id', 'created_at']);
            $table->index(['price_list_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('price_lists');
    }
};
