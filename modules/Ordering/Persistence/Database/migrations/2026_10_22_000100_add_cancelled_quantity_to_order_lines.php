<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Huỷ một phần (0.3.18): `quantity` và các khoản tiền của dòng là phần CÒN HIỆU LỰC; số đã huỷ cộng dồn ở đây để hiển thị.
 * Chi tiết từng lần huỷ: order_events (lines_cancelled) + order_adjustments (cancellation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->unsignedInteger('cancelled_quantity')->default(0)->after('quantity');
        });

        // Huỷ hết một dòng → quantity = 0 (dòng giữ lại làm lịch sử): nới quantity > 0 thành >= 0, vẫn cấm dòng rỗng từ đầu.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_lines DROP CHECK order_lines_quantity_positive');
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_quantity_positive CHECK (quantity >= 0 AND quantity + cancelled_quantity > 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_lines DROP CHECK order_lines_quantity_positive');
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_quantity_positive CHECK (quantity > 0)');
        }

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn('cancelled_quantity');
        });
    }
};
