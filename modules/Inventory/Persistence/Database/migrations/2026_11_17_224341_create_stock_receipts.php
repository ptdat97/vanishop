<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Phiếu nhập nhanh (stabilization A6): nháp → nhập kho (cộng tồn một lần, biến động `receive` mang số phiếu) hoặc huỷ. Nhà cung
| cấp là chữ tự do — đơn mua nhiều bước, nhận một phần, giá vốn để plugin (backlog L41).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 32)->unique();
            $table->unsignedBigInteger('location_id');
            $table->string('supplier', 120)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 16)->default('draft'); // draft | received | cancelled
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('stock_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedInteger('quantity');
            $table->unique(['stock_receipt_id', 'variant_id']);
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_lines');
        Schema::dropIfExists('stock_receipts');
    }
};
