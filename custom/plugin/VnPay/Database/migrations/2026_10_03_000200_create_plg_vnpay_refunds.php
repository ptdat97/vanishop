<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Hoàn tiền VNPay đã thành công, theo khoá idempotency của Core (ADR-027): gọi lại cùng khoá trả cùng kết quả.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_vnpay_refunds', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 191)->unique();
            $table->char('payment_public_id', 26)->index();
            $table->bigInteger('amount');
            $table->string('gateway_reference', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_vnpay_refunds');
    }
};
