<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Infrastructure;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

/** Block Builder "Chiến dịch giá": một chiến dịch, hoặc tự động chọn chiến dịch đang chạy ưu tiên cao nhất. */
final class CampaignBlock implements StorefrontBlock
{
    public const TYPE = 'pricing_campaign';

    private const MAX_CHOICES = 200;

    public function __construct(private readonly CampaignPresenter $presenter) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-pricing-campaign::texts.block.label');
    }

    public function fields(): array
    {
        $choices = ['0' => __('vani-pricing-campaign::texts.block.auto')] + PricingCampaign::query()->where('status', '!=', 'stopped')->orderByDesc('id')
            ->limit(self::MAX_CHOICES)->pluck('name', 'id')->mapWithKeys(fn (string $name, int $id): array => [(string) $id => $name])->all();

        return [
            FieldDefinition::string('title', __('vani-pricing-campaign::texts.block.title'), max: 120),
            FieldDefinition::select('campaign', __('vani-pricing-campaign::texts.block.campaign'), $choices, required: true),
            FieldDefinition::int('limit', __('vani-pricing-campaign::texts.block.limit'), help: __('vani-pricing-campaign::texts.block.limit_help')),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 8) ?: 8));

        return [
            'title' => (string) ($config['title'] ?? ''),
            'campaign' => $this->presenter->find((int) ($config['campaign'] ?? 0), $locale, now()->getTimestamp(), $limit),
            'scriptSrc' => vani_asset(PricingCampaignServiceProvider::ID, 'campaigns.js'),
        ];
    }

    public function view(): string
    {
        return 'vani-pricing-campaign::blocks.campaign';
    }
}
