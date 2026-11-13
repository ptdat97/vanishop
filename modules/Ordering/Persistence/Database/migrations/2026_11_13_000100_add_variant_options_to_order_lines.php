<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * ADR-045 (biến thể linh hoạt, F3): dòng đơn chụp tổ hợp tuỳ chọn của SKU `variant_options` = list<{type, name, code,
 * value}> thay cho `color_name` + `size_code` cố định. Dòng cũ chuyển sang hai tuỳ chọn Màu/Size (bỏ phần trống) rồi bỏ cột cũ
 * (môi trường dev, không giữ tương thích ngược).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->json('variant_options')->nullable()->after('brand_name');
        });

        DB::table('order_lines')->select(['id', 'color_name', 'size_code'])->orderBy('id')->chunkById(500, function ($lines): void {
            foreach ($lines as $line) {
                $options = array_values(array_filter([
                    $line->color_name !== null && $line->color_name !== '' ? ['type' => 'color', 'name' => 'Màu', 'code' => '', 'value' => $line->color_name] : null,
                    $line->size_code !== null && $line->size_code !== '' ? ['type' => 'size', 'name' => 'Size', 'code' => $line->size_code, 'value' => $line->size_code] : null,
                ]));
                DB::table('order_lines')->where('id', $line->id)->update(['variant_options' => $options === [] ? null : json_encode($options, JSON_UNESCAPED_UNICODE)]);
            }
        });

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn(['color_name', 'size_code']);
        });
    }

    public function down(): void
    {
        // Không quay lại cột màu/size cố định (ADR-045; dev: migrate:fresh).
    }
};
