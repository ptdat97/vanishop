<?php

declare(strict_types=1);

namespace Modules\Promotion\Testing;

use Closure;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;

/**
 * Contract test cho PromotionRule. Kiểm tra: type ổn định + nhãn; cấu hình hợp lệ không lỗi, cấu hình sai có lỗi;
 * rule chỉ THU HẸP tập dòng đủ điều kiện (không thêm dòng); không có dòng ứng viên → không có dòng; xác định.
 *
 *   PromotionRuleContract::define('min_quantity', fn () => new MinQuantityRule, valid: ['min' => 2], invalid: ['min' => -1]);
 */
final class PromotionRuleContract
{
    /**
     * @param  Closure(): PromotionRule  $rule
     * @param  array<string, mixed>  $valid
     * @param  array<string, mixed>|null  $invalid
     * @param  (Closure(): PromotionContext)|null  $context
     */
    public static function define(string $label, Closure $rule, array $valid, ?array $invalid = null, ?Closure $context = null): void
    {
        $context ??= fn (): PromotionContext => self::sampleContext();

        describe("PromotionRule contract: {$label}", function () use ($rule, $valid, $invalid, $context): void {
            it('có type ổn định và nhãn', fn () => expect($rule()->type())->toMatch('/^[a-z][a-z0-9_]*$/')->and($rule()->label())->not->toBe(''));

            it('cấu hình hợp lệ không có lỗi; cấu hình sai có lỗi dạng chuỗi', function () use ($rule, $valid, $invalid): void {
                expect($rule()->validateConfig($valid))->toBe([]);
                if ($invalid !== null) {
                    expect($rule()->validateConfig($invalid))->not->toBe([])->each->toBeString();
                }
            });

            it('chỉ thu hẹp tập ứng viên, xác định', function () use ($rule, $valid, $context): void {
                // Rule chạy trong phạm vi checkout; contract test dùng phạm vi hệ thống.
                app(CurrentContext::class)->set(ContextScope::system('promotion rule contract'));
                $input = $context();
                $candidates = new Eligibility(array_map(fn (PromotionLine $line): int => $line->key, $input->lines));
                $result = $rule()->evaluate($input, $valid, $candidates);

                expect(array_diff($result->keys, $candidates->keys))->toBe([])
                    ->and($rule()->evaluate($context(), $valid, $candidates)->keys)->toBe($result->keys)
                    ->and($rule()->evaluate($context(), $valid, Eligibility::none())->keys)->toBe([]);
            });
        });
    }

    public static function sampleContext(): PromotionContext
    {
        $line = fn (int $key, int $quantity, int $unit): PromotionLine => new PromotionLine($key, 1, 10 + $key, $quantity, Money::vnd($unit), Money::vnd($unit * $quantity));

        return new PromotionContext(42, 'VND', [$line(1, 2, 150_000), $line(2, 1, 99_000)], [], 1_760_000_000);
    }
}
