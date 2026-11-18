<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Phí ship trả hàng (backlog L10, Owner 2026-11-18): khi hoàn tất yêu cầu hoàn tiền, CSKH ghi phí ship hàng gửi về và ai
| chịu. Khách chịu → trừ vào tiền hoàn; cửa hàng chịu → không trừ. Lý do "lỗi do cửa hàng" mặc định cửa hàng chịu.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('return_shipping_fee')->default(0)->after('refunded_amount')->comment('Phí ship hàng gửi về (đơn vị nhỏ nhất của tiền tệ)');
            $table->string('return_shipping_payer', 8)->nullable()->after('return_shipping_fee')->comment('customer | store — ai chịu phí ship trả');
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropColumn(['return_shipping_fee', 'return_shipping_payer']);
        });
    }
};
