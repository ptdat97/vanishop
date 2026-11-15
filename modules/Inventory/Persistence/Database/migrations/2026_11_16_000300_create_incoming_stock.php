<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hàng đang về theo SKU (ADR-047): nhân viên/plugin/ERP ghi, plugin đọc để hiển thị và tính hạn mức đặt trước.
        Schema::create('incoming_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('expected_on')->nullable();
            $table->string('source', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_stock');
    }
};
