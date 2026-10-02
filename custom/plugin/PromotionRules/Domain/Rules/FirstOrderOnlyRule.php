<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Ordering\Contracts\OrderReader;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule: chỉ áp dụng cho đơn hàng đầu tiên của khách (khách chưa từng có đơn nào không huỷ).
 * Cấu hình: {}
 */
final class FirstOrderOnlyRule implements PromotionRule
{
    public const TYPE = 'first_order_only';

    public function __construct(private readonly OrderReader $orders) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return 'Chỉ áp dụng cho đơn đầu tiên';
    }

    public function validateConfig(array $config): array
    {
        return $config === [] ? [] : ['Rule này không nhận cấu hình.'];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        // Khách vãng lai (chưa đăng nhập / không có customer_id) không đủ điều kiện cho rule khách hàng thân thiết/đơn đầu.
        if ($context->customerId === null) {
            return Eligibility::none();
        }

        $hasPlaced = $this->orders->customerHasPlacedOrder($context->customerId);

        return $hasPlaced ? Eligibility::none() : $candidates;
    }
}
