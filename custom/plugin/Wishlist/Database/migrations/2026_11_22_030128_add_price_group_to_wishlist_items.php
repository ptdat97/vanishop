<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plg_wishlist_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('price_group_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plg_wishlist_items', function (Blueprint $table): void {
            $table->dropColumn('price_group_id');
        });
    }
};
