<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kết quả mỗi lần đối soát (integration-platform §8).
        Schema::create('integration_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);                  // order_events | …
            $table->timestamp('window_from')->nullable();
            $table->timestamp('window_to')->nullable();
            $table->unsignedInteger('checked')->default(0);
            $table->unsignedInteger('discrepancies')->default(0);
            $table->unsignedInteger('repaired')->default(0);
            $table->json('details')->nullable();         // tối đa 100 chênh lệch đầu tiên
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();

            $table->index(['type', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_reconciliations');
    }
};
