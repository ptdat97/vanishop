<?php

declare(strict_types=1);

namespace Modules\Promotion\Testing;

use Closure;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Shared\Domain\Money\Money;

/**
 * Contract test cho PromotionAction. Kiểm tra: type ổn định; cấu hình hợp lệ/sai; giảm giá chỉ cho dòng được đưa
 * vào, cùng tiền tệ, không âm, không vượt số tiền còn lại của dòng; xác định.
 */
final class PromotionActionContract
{
    /**
     * @param  Closure(): PromotionAction  $action
     * @param  array<string, mixed>  $valid
     * @param  array<string, mixed>|null  $invalid
     */
    public static function define(string $label, Closure $action, array $valid, ?array $invalid = null): void
    {
        describe("PromotionAction contract: {$label}", function () use ($action, $valid, $invalid): void {
            it('có type ổn định và nhãn', fn () => expect($action()->type())->toMatch('/^[a-z][a-z0-9_]*$/')->and($action()->label())->not->toBe(''));

            it('cấu hình hợp lệ không có lỗi; cấu hình sai có lỗi', function () use ($action, $valid, $invalid): void {
                expect($action()->validateConfig($valid))->toBe([]);
                if ($invalid !== null) {
                    expect($action()->validateConfig($invalid))->not->toBe([])->each->toBeString();
                }
            });

            it('giảm giá hợp lệ theo dòng và xác định', function () use ($action, $valid): void {
                $remaining = [1 => Money::vnd(300_000), 2 => Money::vnd(99_000), 3 => Money::vnd(1)];
                $discounts = $action()->apply($remaining, $valid, 'VND');

                expect(array_diff(array_keys($discounts), array_keys($remaining)))->toBe([]);
                foreach ($discounts as $key => $discount) {
                    expect($discount)->toBeInstanceOf(Money::class)
                        ->and($discount->currency->code)->toBe('VND')
                        ->and($discount->amount)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($remaining[$key]->amount);
                }
                expect($action()->apply($remaining, $valid, 'VND'))->toEqual($discounts);
            });
        });
    }
}
