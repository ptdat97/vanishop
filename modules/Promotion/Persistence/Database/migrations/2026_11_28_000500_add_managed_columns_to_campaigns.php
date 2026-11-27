<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Campaign do plugin quản lý (0.5.118, `Campaigns::sync`): Admin → Campaign chỉ xem, dùng lại báo cáo `CampaignUsage`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('managed_by', 64)->nullable()->after('status');
            $table->string('managed_key', 64)->nullable()->after('managed_by');
            $table->string('managed_route', 128)->nullable()->after('managed_key');
            $table->unique(['managed_by', 'managed_key']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique(['managed_by', 'managed_key']);
            $table->dropColumn(['managed_by', 'managed_key', 'managed_route']);
        });
    }
};
