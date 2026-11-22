<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Modules\Promotion\Contracts\ContextualPromotionAction;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Shared\Domain\Money\Money;
use Plugin\Bundles\Domain\BundleConfig;
use Plugin\Bundles\Domain\BundleMath;

/**
 * Giảm % cho số bộ đủ món ({@see BundleMath}). Cần ngữ cảnh giỏ (số lượng, đơn giá); không có ngữ cảnh thì không giảm.
 */
final class BundleAction implements ContextualPromotionAction
{
    public const TYPE = 'bundle_percent';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-bundles::texts.action_label');
    }

    public function validateConfig(array $config): array
    {
        $errors = [];
        if (BundleConfig::requirement($config) === null) {
            $errors[] = __('vani-bundles::texts.invalid_items');
        }
        if (BundleConfig::basisPoints($config) === null) {
            $errors[] = __('vani-bundles::texts.invalid_percent', ['min' => BundleConfig::MIN_BASIS_POINTS / 100, 'max' => BundleConfig::MAX_BASIS_POINTS / 100]);
        }

        return $errors;
    }

    public function apply(array $remaining, array $config, string $currencyCode): array
    {
        return [];
    }

    public function applyInContext(PromotionContext $context, array $remaining, array $config): array
    {
        $requirement = BundleConfig::requirement($config);
        $basisPoints = BundleConfig::basisPoints($config);
        if ($requirement === null || $basisPoints === null) {
            return [];
        }
        $lines = [];
        foreach (array_keys($remaining) as $key) {
            $line = $context->line($key);
            if ($line !== null) {
                $lines[$key] = ['style_id' => $line->styleId, 'quantity' => $line->quantity, 'unit_price' => $line->unitPrice->amount];
            }
        }

        return array_map(
            fn (int $amount): Money => Money::of($amount, $context->currencyCode),
            BundleMath::discounts($lines, $requirement, $basisPoints),
        );
    }
}
