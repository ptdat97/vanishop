<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Customer\Contracts\CustomerSegments;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule (0.3.0): khách thuộc một trong các nhóm khách. Cấu hình: {"groups": ["vip", "nhan-vien"]} (mã nhóm).
 * Khách vãng lai không đủ điều kiện.
 */
final class InCustomerGroupsRule implements PromotionRule
{
    public const TYPE = 'in_customer_groups';

    public function __construct(private readonly CustomerSegments $segments) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.in_customer_groups_rule.customer_group');
    }

    public function validateConfig(array $config): array
    {
        $groups = $config['groups'] ?? null;
        if (! is_array($groups) || $groups === []) {
            return [__('vani-promotion-rules::texts.in_customer_groups_rule.choose_least_one_customer_group')];
        }
        $known = array_column($this->segments->groups(), 'code');
        $unknown = array_diff(array_map('strval', $groups), $known);

        return $unknown === [] ? [] : [__('vani-promotion-rules::texts.in_customer_groups_rule.customer_group_not_found').implode(', ', $unknown).'.'];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        if ($context->customerId === null) {
            return Eligibility::none();
        }
        $code = $this->segments->segmentOf($context->customerId)->groupCode;

        return $code !== null && in_array($code, array_map('strval', (array) ($config['groups'] ?? [])), true) ? $candidates : Eligibility::none();
    }
}
