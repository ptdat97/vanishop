<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign;

use Illuminate\Support\Facades\App;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Pricing\Contracts\ListPriceReader;
use Modules\Pricing\Events\PriceChanged;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\PricingCampaign\Application\PricingCampaigns;
use Plugin\PricingCampaign\Infrastructure\CampaignBlock;
use Plugin\PricingCampaign\Infrastructure\CampaignPresenter;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\Persistence\PricingCampaignItem;

final class PricingCampaignServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.pricing-campaign';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        // Một presenter mỗi request: chiến dịch đang bật nạp một lần cho mọi thẻ sản phẩm.
        $this->app->scoped(CampaignPresenter::class);
        // Trang Ưu đãi / block / badge cache được: lưu chiến dịch → cache trang hết hiệu lực (ADR-051); giá đổi qua PriceChanged.
        $this->pageCacheModels([PricingCampaign::class, PricingCampaignItem::class]);
        $this->translations($this->pluginPath('Resources/lang'), 'vani-pricing-campaign');
        $this->permissions([
            'pricing_campaign.view' => 'vani-pricing-campaign::texts.permission_view',
            'pricing_campaign.manage' => 'vani-pricing-campaign::texts.permission_manage',
        ], [PermissionRegistry::PRESET_MARKETING]);

        $this->adminMenu('pricing-campaigns', 'vani-pricing-campaign::texts.menu', 'admin.plugins.vani-pricing-campaign.index', 'pricing_campaign.view', 220, group: 'marketing');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('PricingCampaign', $this->pluginPath('Resources/js/Pages'));

        // Giá niêm yết đổi → tính lại giá chiến dịch có sản phẩm đó.
        $this->onEvent(PriceChanged::class, function (PriceChanged $event): void {
            if ($event->priceListId === $this->app->make(ListPriceReader::class)->listId()) {
                $this->app->make(PricingCampaigns::class)->listPricesChanged($event->variantIds);
            }
        });

        $this->contribute(StorefrontBlock::TAG, CampaignBlock::class);
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-pricing-campaign');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: ['vi' => 'uu-dai', 'en' => 'deals'], cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        $offer = fn (array $product): ?array => $this->app->make(CampaignPresenter::class)->forProduct(
            (int) ($product['id'] ?? 0),
            App::getLocale(),
            now()->getTimestamp(),
            is_array($product['price']['price_list_codes'] ?? null) ? $product['price']['price_list_codes'] : null,
        );
        $this->onSlot('vani.storefront.plp.card_badges', function (array $product) use ($offer): ?SlotView {
            $deal = $offer($product);

            return $deal === null || ! $deal['running'] ? null : new SlotView('vani-pricing-campaign::badge', ['offer' => $deal]);
        });
        $this->onSlot('vani.storefront.pdp.after_price', function (array $product) use ($offer): ?SlotView {
            $deal = $offer($product);

            return $deal === null ? null : new SlotView('vani-pricing-campaign::pdp', [
                'offer' => $deal, 'productId' => (int) $product['id'], 'scriptSrc' => vani_asset(self::ID, 'campaigns.js'),
                'liveUrl' => route('api.storefront.x.vani-pricing-campaign.product', (int) $product['id']),
            ]);
        });
    }
}
