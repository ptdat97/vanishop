<?php

declare(strict_types=1);

namespace Modules\Promotion\Tests\Fixtures;

use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/** Rule không phụ thuộc dòng hàng (kiểu "đơn đầu tiên"): sau khi đặt sẽ không còn đạt — không được kiểm tra lại. */
final class FirstOrderFixtureRule implements PromotionRule
{
    public static bool $passes = true;

    public function type(): string
    {
        return 'fixture_first_order';
    }

    public function label(): string
    {
        return 'Đơn đầu tiên';
    }

    public function validateConfig(array $config): array
    {
        return [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        return self::$passes ? $candidates : new Eligibility([]);
    }
}
