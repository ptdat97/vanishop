<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Dữ liệu riêng của plugin vani.size-chart (ADR-027): bảng size và việc gán bảng cho sản phẩm/danh mục/thương hiệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_size_charts', function (Blueprint $table): void {
            $table->id();
            $table->json('names');                       // locale => tên
            $table->string('base_unit', 8)->default('cm');
            $table->string('display_unit', 8)->nullable(); // null = không đổi đơn vị
            $table->json('blocks');                      // [{type: image|text|table, …}]
            $table->boolean('advisor_enabled')->default(false);
            $table->json('advisor_rules');               // [{size, height_min, height_max, weight_min, weight_max, note}]
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('plg_size_chart_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chart_id')->constrained('plg_size_charts')->cascadeOnDelete();
            $table->string('target_type', 16);           // product (style) | category | brand
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_size_chart_assignments');
        Schema::dropIfExists('plg_size_charts');
    }
};
