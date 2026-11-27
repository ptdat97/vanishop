<?php

declare(strict_types=1);

namespace Plugin\PromotionRules;

use Modules\Extension\PluginServiceProvider;
use Modules\Promotion\Contracts\PromotionRule;
use Plugin\PromotionRules\Domain\Rules\CustomerHasTagsRule;
use Plugin\PromotionRules\Domain\Rules\ExcludeOnSaleRule;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InBrandsRule;
use Plugin\PromotionRules\Domain\Rules\InCategoriesRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\InCustomerGroupsRule;
use Plugin\PromotionRules\Domain\Rules\InProductsRule;
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
        $this->translations($this->pluginPath('Resources/lang'), 'vani-promotion-rules');
        $this->contribute(PromotionRule::TAG, MinOrderSubtotalRule::class);
        $this->contribute(PromotionRule::TAG, MinQuantityRule::class);
        $this->contribute(PromotionRule::TAG, InCollectionsRule::class);
        $this->contribute(PromotionRule::TAG, FirstOrderOnlyRule::class);
        $this->contribute(PromotionRule::TAG, InBrandsRule::class);
        $this->contribute(PromotionRule::TAG, InCustomerGroupsRule::class);
        $this->contribute(PromotionRule::TAG, CustomerHasTagsRule::class);
        $this->contribute(PromotionRule::TAG, InProductsRule::class);
        $this->contribute(PromotionRule::TAG, InCategoriesRule::class);
        $this->contribute(PromotionRule::TAG, ExcludeOnSaleRule::class);
    }
}
