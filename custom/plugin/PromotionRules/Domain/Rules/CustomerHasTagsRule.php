<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Customer\Contracts\CustomerSegments;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule (0.3.0): khách có ít nhất một tag (hoặc đủ mọi tag khi `match = all`). Cấu hình:
 * {"tags": ["kol", "khach-si"], "match": "any"|"all"}. Khách vãng lai không đủ điều kiện.
 */
final class CustomerHasTagsRule implements PromotionRule
{
    public const TYPE = 'customer_has_tags';

    public function __construct(private readonly CustomerSegments $segments) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return 'Khách có tag';
    }

    public function validateConfig(array $config): array
    {
        $errors = [];
        if (! is_array($config['tags'] ?? null) || $config['tags'] === []) {
            $errors[] = 'Nhập ít nhất một tag (tags).';
        }
        if (isset($config['match']) && ! in_array($config['match'], ['any', 'all'], true)) {
            $errors[] = 'match phải là any hoặc all.';
        }

        return $errors;
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        if ($context->customerId === null) {
            return Eligibility::none();
        }
        $has = $this->segments->segmentOf($context->customerId)->tags;
        $wanted = array_map('strval', (array) ($config['tags'] ?? []));
        $matched = array_intersect($wanted, $has);
        $ok = ($config['match'] ?? 'any') === 'all' ? count($matched) === count(array_unique($wanted)) : $matched !== [];

        return $ok ? $candidates : Eligibility::none();
    }
}
