<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Lượt dùng mỗi khách (0.5.109): `promotions.usage_limit_per_customer`; bộ đếm `promotion_customer_usages` tăng bằng
 * UPDATE có điều kiện trong transaction đặt hàng (như `vouchers.used_count`). Voucher gán cho một khách: `vouchers.customer_id`
 * (không FK — Customer đứng sau Promotion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('usage_limit_per_customer')->nullable()->after('usage_count');
        });
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('promotion_id')->index();
        });
        Schema::create('promotion_customer_usages', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
            $table->primary(['promotion_id', 'customer_id']);
            $table->index('customer_id');
        });

        $now = now();
        DB::table('promotion_usages')->where('status', 'applied')->whereNotNull('customer_id')
            ->selectRaw('promotion_id, customer_id, COUNT(*) as used_count')->groupBy('promotion_id', 'customer_id')->orderBy('promotion_id')
            ->chunk(500, function ($rows) use ($now): void {
                DB::table('promotion_customer_usages')->insert($rows->map(fn (object $row): array => [
                    'promotion_id' => $row->promotion_id, 'customer_id' => $row->customer_id, 'used_count' => $row->used_count, 'created_at' => $now, 'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_customer_usages');
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('usage_limit_per_customer');
        });
    }
};
