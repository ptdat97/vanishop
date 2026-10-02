<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Snapshot tuỳ chọn dòng (khoá theo plugin id) chụp từ dòng giỏ lúc đặt hàng — bất biến sau đó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
