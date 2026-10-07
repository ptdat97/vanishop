<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Đối soát khoản đã thu với cổng (roadmap Phase 3): chỉ ghi chênh lệch để xử lý tay — không tự đổi trạng thái thanh
 * toán (VaniShop đã ghi nhận theo IPN/callback đã xác minh; lệch sau đó cần người kiểm tra).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('window_from');
            $table->unsignedInteger('checked')->default(0);
            $table->unsignedInteger('skipped')->default(0); // cổng không tra cứu được / lỗi gọi cổng
            $table->unsignedInteger('issues')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
        });

        Schema::create('payment_reconciliation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('payment_reconciliations')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('gateway_code', 64);
            $table->string('issue', 32); // gateway_not_captured | amount_mismatch | refund_mismatch
            $table->string('expected', 64)->nullable(); // phía VaniShop
            $table->string('actual', 64)->nullable();   // phía cổng
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution')->nullable();
            $table->index(['payment_id', 'issue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_lines');
        Schema::dropIfExists('payment_reconciliations');
    }
};
