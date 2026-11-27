<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Lịch lặp theo tuần (0.5.116, `Shared\Domain\Schedule\WeeklySchedule`) cho khuyến mãi và campaign (campaign ghi xuống thành viên). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->json('recurrence')->nullable()->after('ends_at');
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('recurrence')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('recurrence');
        });
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('recurrence');
        });
    }
};
