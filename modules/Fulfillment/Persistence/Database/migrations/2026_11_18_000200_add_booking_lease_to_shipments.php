<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Giữ chỗ khi đặt vận đơn với hãng (backlog L31): bước 1 ghi lease (thời điểm + mã) trong transaction ngắn, bước 2 gọi API
| hãng ngoài transaction, bước 3 ghi kết quả nếu lease còn là của mình. Không còn giữ khoá dòng vận đơn trong lúc chờ hãng.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->timestamp('booking_started_at')->nullable()->after('booking_attempts')->comment('Lease đặt vận đơn: lần đặt đang gọi hãng');
            $table->string('booking_lease', 26)->nullable()->after('booking_started_at')->comment('Mã lease của lần đặt đang chạy');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropColumn(['booking_started_at', 'booking_lease']);
        });
    }
};
