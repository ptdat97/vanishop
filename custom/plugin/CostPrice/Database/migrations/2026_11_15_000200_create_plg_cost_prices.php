<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Giá vốn theo SKU (ADR-027: bảng riêng của plugin, FK tới variants của Core).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_cost_prices', function (Blueprint $table): void {
            $table->foreignId('variant_id')->primary()->constrained('variants')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_cost_prices');
    }
};
