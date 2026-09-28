<?php

declare(strict_types=1);

namespace Plugin\PromotionRules;

use Modules\Extension\PluginServiceProvider;
use Modules\Promotion\Contracts\PromotionRule;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule;
use Plugin\PromotionRules\Domain\Rules\MinQuantityRule;

final class PromotionRulesServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.promotion-rules';
    }

    public function boot(): void
    {
        $this->contribute(PromotionRule::TAG, MinOrderSubtotalRule::class);
        $this->contribute(PromotionRule::TAG, MinQuantityRule::class);
        $this->contribute(PromotionRule::TAG, InCollectionsRule::class);
        $this->contribute(PromotionRule::TAG, FirstOrderOnlyRule::class);
    }
}
