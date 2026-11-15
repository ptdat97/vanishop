<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Bảng đối soát chung (EP v3 X5): plugin đối soát (sao kê ngân hàng, bảng kê COD của hãng…) ghi từng dòng khớp/lệch;
 * dòng lệch chờ nhân viên xử lý. Idempotent theo (source, reference).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 64);
            $table->string('reference', 128);
            $table->string('status', 16); // matched | mismatch | resolved
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->bigInteger('expected')->nullable();
            $table->bigInteger('actual')->nullable();
            $table->string('note')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unique(['source', 'reference']);
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_entries');
    }
};
