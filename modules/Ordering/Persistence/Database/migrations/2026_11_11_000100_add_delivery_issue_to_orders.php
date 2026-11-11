<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Sự cố giao hàng đang mở của đơn (DeliveryIssue: đặt vận đơn lỗi, giao không thành công, đang hoàn về) — tổng hợp từ
| vận đơn, do Fulfillment cập nhật. Đưa đơn vào hàng đợi "Sự cố giao hàng" ở Admin; null = không có sự cố.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_issue', 24)->nullable()->after('fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_issue');
        });
    }
};
