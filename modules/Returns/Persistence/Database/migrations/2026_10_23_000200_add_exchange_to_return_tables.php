<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Đổi hàng (0.3.32): yêu cầu `resolution = exchange` có variant thay thế cho từng dòng trả; hoàn tất tạo đơn thay thế.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->string('resolution', 16)->default('refund')->after('reason_code'); // refund | exchange
            $table->foreignId('replacement_order_id')->nullable()->after('refunded_amount')->constrained('orders')->restrictOnDelete();
        });
        Schema::table('return_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('exchange_variant_id')->nullable()->after('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('return_lines', function (Blueprint $table) {
            $table->dropColumn('exchange_variant_id');
        });
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_order_id');
            $table->dropColumn('resolution');
        });
    }
};
