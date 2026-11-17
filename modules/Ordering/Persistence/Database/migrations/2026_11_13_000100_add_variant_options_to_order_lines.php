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

    /**
     * Đưa lại `color_name` + `size_code` (giá trị trục Màu, mã trục Size); trục khác không có chỗ trong mô hình cũ nên mất.
     */
    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->string('color_name')->nullable()->after('brand_name');
            $table->string('size_code', 32)->default('')->after('color_name');
        });

        DB::table('order_lines')->whereNotNull('variant_options')->select(['id', 'variant_options'])->orderBy('id')->chunkById(500, function ($lines): void {
            foreach ($lines as $line) {
                $options = collect(json_decode((string) $line->variant_options, true) ?: []);
                DB::table('order_lines')->where('id', $line->id)->update([
                    'color_name' => $options->firstWhere('type', 'color')['value'] ?? null,
                    'size_code' => mb_substr((string) ($options->firstWhere('type', 'size')['code'] ?? ''), 0, 32),
                ]);
            }
        });

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn('variant_options');
        });
    }
};
