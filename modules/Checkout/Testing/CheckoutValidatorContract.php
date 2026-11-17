<?php

declare(strict_types=1);

namespace Modules\Checkout\Testing;

use Closure;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;

/**
 * Contract test cho CheckoutValidator. Kiểm tra: mã ổn định; trả list<CheckoutIssue> có mã + thông điệp;
 * yêu cầu hợp lệ của plugin không bị chặn; yêu cầu vi phạm (nếu có) bị chặn; xác định.
 *
 *   CheckoutValidatorContract::define('vani.cod-province', fn () => new CodProvinceValidator(...),
 *       invalid: fn () => new CheckoutRequest(...));   // yêu cầu mà validator phải chặn
 */
final class CheckoutValidatorContract
{
    /**
     * @param  Closure(): CheckoutValidator  $validator
     * @param  (Closure(): CheckoutRequest)|null  $valid
     * @param  (Closure(): CheckoutRequest)|null  $invalid
     */
    public static function define(string $label, Closure $validator, ?Closure $valid = null, ?Closure $invalid = null, ?Closure $totals = null): void
    {
        $valid ??= fn (): CheckoutRequest => Samples::checkoutRequest();
        $totals ??= fn (): Totals => Samples::totals();

        describe("CheckoutValidator contract: {$label}", function () use ($validator, $valid, $invalid, $totals): void {
            it('có mã ổn định', fn () => expect($validator()->code())->toMatch('/^[a-z][a-z0-9_.]*$/'));

            it('yêu cầu hợp lệ không bị chặn; kết quả xác định', function () use ($validator, $valid, $totals): void {
                $issues = $validator()->validate($valid(), $totals(), true);

                expect($issues)->toBe([])->and($validator()->validate($valid(), $totals(), true))->toEqual($issues);
            });

            if ($invalid !== null) {
                it('yêu cầu vi phạm bị chặn bằng CheckoutIssue có mã và thông điệp', function () use ($validator, $invalid, $totals): void {
                    $issues = $validator()->validate($invalid(), $totals(), true);

                    expect($issues)->not->toBe([])->each->toBeInstanceOf(CheckoutIssue::class);
                    foreach ($issues as $issue) {
                        expect($issue->code)->not->toBe('')->and($issue->message)->not->toBe('');
                    }
                });
            }
        });
    }
}
