<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plg_lookbook_images', function (Blueprint $table): void {
            $table->json('product_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plg_lookbook_images', function (Blueprint $table): void {
            $table->dropColumn('product_ids');
        });
    }
};
