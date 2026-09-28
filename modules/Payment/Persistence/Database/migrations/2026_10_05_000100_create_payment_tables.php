<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_entity_id')->constrained()->restrictOnDelete(); // tài khoản nhận tiền theo pháp nhân
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('gateway_code', 64);
            $table->bigInteger('amount');
            $table->bigInteger('refunded_amount')->default(0);
            $table->char('currency_code', 3);
            $table->string('status', 24); // pending | paid | failed | cancelled | expired | refunded | partially_refunded
            $table->string('gateway_reference', 128)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['brand_id', 'status']);
        });

        // Append-only. Unique chống IPN/callback trùng.
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('gateway_code', 64);
            $table->string('type', 16); // initiate | callback | query | confirm | refund
            $table->string('gateway_transaction_id', 128);
            $table->bigInteger('amount')->nullable();
            $table->string('status', 24);
            $table->json('raw_payload_masked')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('created_at', 6);

            $table->unique(['gateway_code', 'gateway_transaction_id', 'type'], 'payment_tx_gateway_unique');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount');
            $table->string('status', 16); // requested | processing | completed | failed
            $table->string('reason');
            $table->string('idempotency_key', 128)->unique();
            $table->string('gateway_reference', 128)->nullable();
            $table->string('requested_by_type', 32)->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_refund_within_amount CHECK (refunded_amount >= 0 AND refunded_amount <= amount)');
            DB::statement('ALTER TABLE refunds ADD CONSTRAINT refunds_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
