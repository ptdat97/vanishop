<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Promotion\Contracts\Campaigns;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

/*
 * vani.pricing-campaign: nội dung chiến dịch giá. Giá, lịch, suất ở Core: bảng giá do plugin quản lý (ManagedPriceLists +
 * PriceQuotas) và campaign (Campaigns::sync). Id của Core không khoá ngoại (ADR-027).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_pricing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('template', 16)->default('custom'); // flash_sale | hot_deal | member | weekend | custom
            $table->string('name', 120); // tên nội bộ
            $table->json('translations'); // {locale: {label, badge, content}}
            $table->string('badge_style', 16)->default('danger');
            $table->string('accent_color', 16)->nullable();
            $table->unsignedBigInteger('banner_media_id')->nullable();
            $table->boolean('show_countdown')->default(true);
            $table->boolean('show_stock')->default(true);
            $table->integer('priority')->default(30);
            $table->unsignedBigInteger('customer_group_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->json('recurrence')->nullable();
            $table->unsignedInteger('quota_total')->nullable();
            $table->unsignedInteger('per_customer_total')->nullable();
            $table->string('status', 16)->default('draft'); // draft | active | stopped
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['status', 'ends_at']);
        });
        Schema::create('plg_pricing_campaign_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pricing_campaign_id')->constrained('plg_pricing_campaigns')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('strategy', 16); // fixed_price | percent | amount
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('per_customer')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['pricing_campaign_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (array_keys(app(Campaigns::class)->ids(PricingCampaignServiceProvider::ID)) as $key) {
                app(Campaigns::class)->remove(PricingCampaignServiceProvider::ID, (string) $key);
            }
            foreach (array_keys(app(ManagedPriceLists::class)->lists(PricingCampaignServiceProvider::ID)) as $key) {
                app(ManagedPriceLists::class)->remove(PricingCampaignServiceProvider::ID, (string) $key);
            }
        });
        Schema::dropIfExists('plg_pricing_campaign_items');
        Schema::dropIfExists('plg_pricing_campaigns');
    }
};
