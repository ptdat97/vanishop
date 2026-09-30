<?php

declare(strict_types=1);

namespace Modules\Integration\Testing;

use Closure;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\InboxMessage;
use Modules\Integration\Contracts\InboundHandler;

/**
 * Contract test cho InboundHandler. Kiểm tra: system ổn định; supports message mẫu; xử lý trả DeliveryResult;
 * IDEMPOTENT — xử lý lại cùng message (retry/replay) trả ok hoặc stale, không lỗi, không làm lại việc
 * (`$sideEffects` đếm tác động, vd. số bản ghi được tạo, phải giữ nguyên sau lần 2).
 */
final class InboundHandlerContract
{
    /**
     * @param  Closure(): InboundHandler  $handler
     * @param  Closure(): InboxMessage  $message
     * @param  (Closure(): int)|null  $sideEffects
     */
    public static function define(string $label, Closure $handler, Closure $message, ?Closure $sideEffects = null): void
    {
        describe("InboundHandler contract: {$label}", function () use ($handler, $message, $sideEffects): void {
            it('có system ổn định và hỗ trợ message mẫu', function () use ($handler, $message): void {
                expect($handler()->system())->toMatch('/^[a-z][a-z0-9_.-]*$/')
                    ->and($handler()->supports($message()->messageType))->toBeTrue();
            });

            it('xử lý idempotent', function () use ($handler, $message, $sideEffects): void {
                $first = $handler()->handle($message());
                expect($first)->toBeInstanceOf(DeliveryResult::class)->and($first->isOk())->toBeTrue();
                $after = $sideEffects?->__invoke();

                $second = $handler()->handle($message());
                expect($second->isOk() || $second->isStale())->toBeTrue();
                if ($sideEffects !== null) {
                    expect($sideEffects())->toBe($after);
                }
            });
        });
    }
}
