<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Mốc đã phát CartAbandoned cho lần không hoạt động gần nhất (phát lại khi khách quay lại rồi bỏ tiếp).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->timestamp('abandoned_notified_at')->nullable()->after('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropColumn('abandoned_notified_at');
        });
    }
};
