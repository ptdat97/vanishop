<?php

use Modules\Promotion\Testing\PromotionRuleContract;
use Plugin\PromotionRules\Domain\Rules\ExcludeOnSaleRule;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InBrandsRule;
use Plugin\PromotionRules\Domain\Rules\InCategoriesRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\InProductsRule;
use Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule;
use Plugin\PromotionRules\Domain\Rules\MinQuantityRule;

PromotionRuleContract::define('vani.promotion-rules min_order_subtotal', fn () => app(MinOrderSubtotalRule::class), ['min_subtotal' => 200_000], ['min_subtotal' => -1]);
PromotionRuleContract::define('vani.promotion-rules min_quantity', fn () => app(MinQuantityRule::class), ['min_quantity' => 2], ['min_quantity' => 0]);
PromotionRuleContract::define('vani.promotion-rules in_collections', fn () => app(InCollectionsRule::class), ['slugs' => ['he-2026']], ['slugs' => []]);
PromotionRuleContract::define('vani.promotion-rules first_order_only', fn () => app(FirstOrderOnlyRule::class), [], ['scope' => 'galaxy']);
PromotionRuleContract::define('vani.promotion-rules in_brands', fn () => app(InBrandsRule::class), ['slugs' => ['lumiere']], ['slugs' => []]);
PromotionRuleContract::define('vani.promotion-rules in_products', fn () => app(InProductsRule::class), ['product_ids' => [11]], ['product_ids' => ['a']]);
PromotionRuleContract::define('vani.promotion-rules in_categories', fn () => app(InCategoriesRule::class), ['category_ids' => [3]], ['category_ids' => []]);
PromotionRuleContract::define('vani.promotion-rules exclude_on_sale', fn () => app(ExcludeOnSaleRule::class), [], ['x' => 1]);
