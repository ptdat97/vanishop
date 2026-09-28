<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('number', 40)->unique(); // <số đơn>-R<n>
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('status', 16);
            $table->string('reason_code', 32);
            $table->string('customer_note', 500)->nullable();
            $table->string('source', 16); // customer | staff
            $table->bigInteger('refund_amount');          // tính từ thành tiền dòng đã phân bổ giảm giá
            $table->bigInteger('refunded_amount')->nullable(); // số thực hoàn khi resolved (có thể trừ phí hư hỏng)
            $table->char('currency_code', 3);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['brand_id', 'status']);
        });

        Schema::create('return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedInteger('quantity');
            $table->bigInteger('refund_amount');
            $table->string('condition', 16)->nullable(); // sellable | damaged — ghi khi nhận hàng
            $table->unsignedBigInteger('restock_location_id')->nullable();
            $table->timestamps();
        });

        Schema::create('return_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->string('note')->nullable();
            $table->string('actor_type', 32)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('source', 16);
            $table->timestamp('created_at', 6);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE return_lines ADD CONSTRAINT return_lines_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE return_requests ADD CONSTRAINT return_refund_within_amount CHECK (refunded_amount IS NULL OR (refunded_amount >= 0 AND refunded_amount <= refund_amount))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_events');
        Schema::dropIfExists('return_lines');
        Schema::dropIfExists('return_requests');
    }
};
