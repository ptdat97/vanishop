<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Support\StoreLocale;

/*
 * Danh mục lý do đổi trả do Admin quản lý (reference-parity §3.13-1): tên theo ngôn ngữ, thứ tự, bật/tắt, cờ "lỗi do
 * cửa hàng". Lý do đã dùng chỉ tắt, không xoá. Dữ liệu ban đầu = danh sách cũ ở config `vanishop.returns.reasons`.
 * Khách báo đã gửi hàng về (§3.13-2): mã vận đơn trả tuỳ chọn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_reasons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->json('names');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('store_fault')->default(false);
            $table->timestamps();
        });

        Schema::table('return_requests', function (Blueprint $table): void {
            $table->string('return_tracking_number', 64)->nullable()->after('customer_note');
        });

        $storeFault = ['not_as_described', 'defective'];
        foreach (array_values((array) config('vanishop.returns.reasons', [])) as $position => $code) {
            $names = [];
            foreach (StoreLocale::supported() as $locale) {
                $names[$locale] = __("returns::labels.reason.{$code}", [], $locale);
            }
            DB::table('return_reasons')->insert([
                'code' => $code, 'names' => json_encode($names, JSON_UNESCAPED_UNICODE), 'position' => $position, 'is_active' => true,
                'store_fault' => in_array($code, $storeFault, true), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropColumn('return_tracking_number');
        });
        Schema::dropIfExists('return_reasons');
    }
};
