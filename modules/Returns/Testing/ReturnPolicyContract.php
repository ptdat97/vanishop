<?php

declare(strict_types=1);

namespace Modules\Returns\Testing;

use Closure;
use DateTimeImmutable;
use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnDecision;
use Modules\Returns\Contracts\ReturnPolicy;

/**
 * Contract test cho ReturnPolicy. Kiểm tra: mã ổn định; hàng CHƯA giao không bao giờ đủ điều kiện; từ chối luôn có
 * lý do; xác định (cùng thời điểm `now`).
 */
final class ReturnPolicyContract
{
    /**
     * @param  Closure(): ReturnPolicy  $policy
     */
    public static function define(string $label, Closure $policy): void
    {
        $context = fn (?DateTimeImmutable $deliveredAt): ReturnContext => new ReturnContext(
            1, 1, [10 => 1], 'wrong_size', $deliveredAt, new DateTimeImmutable('2026-10-14 10:00:00'), 'customer',
        );

        describe("ReturnPolicy contract: {$label}", function () use ($policy, $context): void {
            it('có mã ổn định', fn () => expect($policy()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('hàng chưa giao không đủ điều kiện; từ chối có lý do; xác định', function () use ($policy, $context): void {
                $notDelivered = $policy()->evaluate($context(null));
                expect($notDelivered)->toBeInstanceOf(ReturnDecision::class)
                    ->and($notDelivered->eligible)->toBeFalse()
                    ->and((string) $notDelivered->reason)->not->toBe('');

                foreach ([new DateTimeImmutable('2026-10-13 10:00:00'), new DateTimeImmutable('2020-01-01')] as $deliveredAt) {
                    $decision = $policy()->evaluate($context($deliveredAt));
                    expect($policy()->evaluate($context($deliveredAt)))->toEqual($decision);
                    if (! $decision->eligible) {
                        expect((string) $decision->reason)->not->toBe('');
                    }
                }
            });
        });
    }
}
