<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Theo dõi tồn kho theo sản phẩm: false = mọi SKU luôn bán được, không cần nhập tồn (hàng đặt may, dropship, dịch vụ).
| Inventory đọc cờ qua VariantData::$tracksInventory (Catalog contract).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->boolean('track_inventory')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->dropColumn('track_inventory');
        });
    }
};
