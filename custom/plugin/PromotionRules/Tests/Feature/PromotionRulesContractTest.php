<?php

use Modules\Promotion\Testing\PromotionRuleContract;
use Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule;
use Plugin\PromotionRules\Domain\Rules\InCollectionsRule;
use Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule;
use Plugin\PromotionRules\Domain\Rules\MinQuantityRule;

PromotionRuleContract::define('vani.promotion-rules min_order_subtotal', fn () => app(MinOrderSubtotalRule::class), ['min_subtotal' => 200_000], ['min_subtotal' => -1]);
PromotionRuleContract::define('vani.promotion-rules min_quantity', fn () => app(MinQuantityRule::class), ['min_quantity' => 2], ['min_quantity' => 0]);
PromotionRuleContract::define('vani.promotion-rules in_collections', fn () => app(InCollectionsRule::class), ['slugs' => ['he-2026']], ['slugs' => []]);
PromotionRuleContract::define('vani.promotion-rules first_order_only', fn () => app(FirstOrderOnlyRule::class), ['scope' => 'brand'], ['scope' => 'galaxy']);
