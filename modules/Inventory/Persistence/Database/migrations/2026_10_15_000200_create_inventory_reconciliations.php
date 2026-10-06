<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mỗi lần đối soát tồn kho (roadmap Phase 3): nội bộ (vani:inventory:verify)
        // hoặc với nguồn ngoài (vani:inventory:reconcile). Dùng chung một bảng để báo cáo.
        Schema::create('inventory_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64);                  // internal_verify | mã authority ngoài (locations.stock_authority)
            $table->timestamp('window_from')->nullable();
            $table->timestamp('window_to')->nullable();
            $table->unsignedInteger('checked')->default(0);
            $table->unsignedInteger('discrepancies')->default(0);
            $table->unsignedInteger('repaired')->default(0);
            $table->json('details')->nullable();           // tối đa 100 chênh lệch đầu (để báo cáo nhanh)
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();

            $table->index(['source', 'started_at']);
        });

        Schema::create('inventory_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('inventory_reconciliations')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            // external_mismatch | reserved_mismatch | reserved_off_ledger | on_hand_off_ledger | negative_on_hand
            $table->string('classification', 32);
            $table->integer('expected');                    // giá trị đúng ra
            $table->integer('actual');                      // giá trị hiện tại
            $table->integer('difference');                  // expected − actual
            $table->string('note')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution', 32)->nullable();   // repaired | applied | rejected | manual…

            $table->unique(['reconciliation_id', 'location_id', 'variant_id', 'classification'], 'inventory_recon_lines_unique');
            $table->index(['location_id', 'variant_id']);
            $table->index(['classification', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reconciliation_lines');
        Schema::dropIfExists('inventory_reconciliations');
    }
};
