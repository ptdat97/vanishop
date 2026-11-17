<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Kiểm kê nhanh (stabilization A7): phiên đếm theo kho; mỗi lần ghi số đếm lưu kèm tồn hệ thống lúc đó (`on_hand_at_count`) —
| áp dụng chỉ cộng/trừ phần chênh, hàng bán trong lúc đếm không bị ghi đè.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 32)->unique();
            $table->unsignedBigInteger('location_id');
            $table->string('note', 500)->nullable();
            $table->string('status', 16)->default('counting'); // counting | applied | cancelled
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('stock_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedInteger('counted');
            $table->integer('on_hand_at_count');
            $table->timestamp('counted_at');
            $table->integer('applied_delta')->nullable();
            $table->unique(['stock_count_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
