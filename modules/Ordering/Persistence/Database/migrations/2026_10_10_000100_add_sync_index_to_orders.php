<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Đồng bộ kéo (Integration API GET /orders?updated_since=): keyset theo (updated_at, id).
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['updated_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['updated_at', 'id']);
        });
    }
};
