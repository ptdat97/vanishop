<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Dữ liệu riêng của plugin (ADR-027): ghi chú gắn với sản phẩm Core, khoá ngoại tới styles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_hello_world_notes', function (Blueprint $table): void {
            $table->foreignId('style_id')->primary()->constrained('styles')->restrictOnDelete();
            $table->string('note', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_hello_world_notes');
    }
};
