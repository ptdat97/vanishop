<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Plugin\Bundles\Domain\BundleConfig;
use Plugin\Bundles\Domain\BundleMath;

/**
 * Điều kiện "Mua đủ combo": giỏ có ít nhất một bộ đủ món → các dòng thuộc combo đủ điều kiện. Chỉ phụ thuộc dòng hàng,
 * nên được kiểm tra lại khi khách bớt hàng (CartContentRule).
 */
final class BundleRule implements CartContentRule
{
    public const TYPE = 'bundle_complete';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-bundles::texts.rule_label');
    }

    public function validateConfig(array $config): array
    {
        return BundleConfig::requirement($config) === null ? [__('vani-bundles::texts.invalid_items')] : [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $requirement = BundleConfig::requirement($config);
        if ($requirement === null) {
            return Eligibility::none();
        }
        $lines = [];
        foreach ($candidates->keys as $key) {
            $line = $context->line($key);
            if ($line !== null && isset($requirement[$line->styleId])) {
                $lines[$key] = ['style_id' => $line->styleId, 'quantity' => $line->quantity];
            }
        }

        return BundleMath::sets($lines, $requirement) > 0 ? new Eligibility(array_keys($lines)) : Eligibility::none();
    }
}
