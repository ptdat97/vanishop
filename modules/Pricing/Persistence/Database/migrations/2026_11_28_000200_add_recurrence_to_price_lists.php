<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Lịch lặp theo tuần của bảng giá (0.5.116, `Shared\Domain\Schedule\WeeklySchedule`): sale cuối tuần, khung giờ vàng. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->json('recurrence')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn('recurrence');
        });
    }
};
