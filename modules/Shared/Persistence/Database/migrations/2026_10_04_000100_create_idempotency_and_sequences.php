<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ADR-014: request có Idempotency-Key (đặt hàng, tạo refund…) chỉ được thực hiện một lần.
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64);
            $table->string('key', 128);
            $table->char('request_hash', 64);
            $table->string('status', 16); // processing | completed
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['scope', 'key']);
            $table->index('expires_at');
        });

        // Số chứng từ liên tục theo phạm vi + kỳ (vd. số đơn theo brand + tháng).
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64);
            $table->string('period', 16);
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['scope', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('idempotency_keys');
    }
};
