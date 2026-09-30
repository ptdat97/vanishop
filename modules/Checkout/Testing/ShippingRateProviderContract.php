<?php

declare(strict_types=1);

namespace Modules\Checkout\Testing;

use Closure;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;

/**
 * Contract test cho ShippingRateProvider. Kiểm tra: trả list<ShippingOption>; mã khác rỗng và không trùng;
 * phí không âm, cùng tiền tệ giỏ; xác định. (Gọi API báo cước thì plugin fake HTTP trong `prepare`.)
 */
final class ShippingRateProviderContract
{
    /**
     * @param  Closure(): ShippingRateProvider  $provider
     * @param  (Closure(): TotalsContext)|null  $context
     * @param  (Closure(): void)|null  $prepare  vd. Http::fake(...)
     */
    public static function define(string $label, Closure $provider, ?Closure $context = null, ?Closure $prepare = null): void
    {
        $context ??= fn (): TotalsContext => Samples::totalsContext();

        describe("ShippingRateProvider contract: {$label}", function () use ($provider, $context, $prepare): void {
            it('lựa chọn hợp lệ, không trùng mã, phí không âm cùng tiền tệ, xác định', function () use ($provider, $context, $prepare): void {
                $prepare?->__invoke();
                $options = $provider()->options($context());

                expect($options)->each->toBeInstanceOf(ShippingOption::class);
                $codes = array_map(fn (ShippingOption $option): string => $option->code, $options);
                expect(array_unique($codes))->toHaveCount(count($codes));
                foreach ($options as $option) {
                    expect($option->code)->not->toBe('')->and($option->label)->not->toBe('')
                        ->and($option->fee->amount)->toBeGreaterThanOrEqual(0)->and($option->fee->currency->code)->toBe($context()->currencyCode);
                }
                expect($provider()->options($context()))->toEqual($options);
            });
        });
    }
}
