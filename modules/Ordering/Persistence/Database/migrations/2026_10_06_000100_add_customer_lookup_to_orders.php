<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tra cứu đơn theo SĐT (E.164) — tách khỏi JSON snapshot để đánh index.
            $table->string('customer_phone', 16)->nullable()->after('customer_snapshot');
            // sha256 của token truy cập đơn trả cho khách lúc đặt (xem/huỷ đơn không cần tài khoản).
            $table->char('access_token_hash', 64)->nullable()->after('customer_phone');

            $table->index(['brand_id', 'customer_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['brand_id', 'customer_phone']);
            $table->dropColumn(['customer_phone', 'access_token_hash']);
        });
    }
};
