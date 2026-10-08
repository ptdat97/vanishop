<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Campaign (roadmap Phase 8, 0.3.36): gói khuyến mãi + bảng giá (sale/member) chạy chung một lịch; campaign sở hữu lịch
 * và đồng bộ xuống từng thành viên (khuyến mãi: trực tiếp; bảng giá: Pricing\Contracts\PriceListSchedule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 16)->default('draft'); // draft | active | stopped
            $table->timestamp('stopped_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained('campaigns')->nullOnDelete();
        });

        // Bảng giá thuộc tối đa một campaign. price_list_id không FK sang bảng của Pricing (ranh giới module).
        Schema::create('campaign_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->unsignedBigInteger('price_list_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_price_lists');
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });
        Schema::dropIfExists('campaigns');
    }
};
