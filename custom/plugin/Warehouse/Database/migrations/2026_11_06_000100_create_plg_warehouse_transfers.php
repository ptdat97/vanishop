<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * vani.warehouse 1.0 (ADR-042): phiếu chuyển kho thuộc plugin. Cửa hàng đã dùng chuyển kho của Core (trước 0.3.88, bảng
 * stock_transfers) → đổi tên, giữ dữ liệu; cài mới → tạo bảng. Kho (locations) và tồn vẫn thuộc Core.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_transfers') && ! Schema::hasTable('plg_warehouse_transfers')) {
            Schema::rename('stock_transfers', 'plg_warehouse_transfers');
            Schema::rename('stock_transfer_lines', 'plg_warehouse_transfer_lines');

            return;
        }

        Schema::create('plg_warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('from_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->restrictOnDelete();
            $table->string('status', 16)->default('pending');         // pending | shipped | received | cancelled
            $table->string('note')->nullable();
            $table->string('reference', 128)->nullable();             // tham chiếu ngoài (chứng từ)
            $table->string('cancel_reason')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index('status');
            $table->index(['from_location_id', 'status']);
            $table->index(['to_location_id', 'status']);
        });

        Schema::create('plg_warehouse_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('plg_warehouse_transfers')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');                       // số gửi
            $table->unsignedInteger('received_quantity')->nullable();  // số nhận thực tế (ghi khi received)
            $table->timestamps();
            $table->unique(['stock_transfer_id', 'variant_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE plg_warehouse_transfer_lines ADD CONSTRAINT plg_warehouse_transfer_lines_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE plg_warehouse_transfer_lines ADD CONSTRAINT plg_warehouse_transfer_lines_received_not_over CHECK (received_quantity IS NULL OR received_quantity <= quantity)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_warehouse_transfer_lines');
        Schema::dropIfExists('plg_warehouse_transfers');
    }
};
