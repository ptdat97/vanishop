<?php

declare(strict_types=1);

namespace Modules\Notification\Testing;

use Closure;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;

/**
 * Contract test cho NotificationChannel. Plugin cung cấp tin mẫu hợp lệ cho kênh, người nhận không liên lạc được,
 * và hai `prepare` giả lập nhà cung cấp (thành công / lỗi tạm thời, vd. Http::fake 200 / 503).
 * Kiểm tra: mã ổn định; canReach đúng; gửi thành công → sent; lỗi tạm thời → retryable (không ném exception);
 * tin thiếu nội dung/tham số → permanent (không ném exception).
 */
final class NotificationChannelContract
{
    /**
     * @param  Closure(): NotificationChannel  $channel
     * @param  Closure(): OutgoingMessage  $message
     * @param  Closure(): void  $succeed
     * @param  Closure(): void  $failTemporarily
     */
    public static function define(string $label, Closure $channel, Closure $message, Recipient $unreachable, Closure $succeed, Closure $failTemporarily): void
    {
        describe("NotificationChannel contract: {$label}", function () use ($channel, $message, $unreachable, $succeed, $failTemporarily): void {
            it('có mã ổn định; canReach theo địa chỉ người nhận', function () use ($channel, $message, $unreachable): void {
                expect($channel()->code())->toMatch('/^[a-z][a-z0-9_]*$/')
                    ->and($channel()->canReach($message()->recipient))->toBeTrue()
                    ->and($channel()->canReach($unreachable))->toBeFalse();
            });

            it('thành công → sent', function () use ($channel, $message, $succeed): void {
                $succeed();
                expect($channel()->send($message())->isSent())->toBeTrue();
            });

            it('lỗi tạm thời → retryable, không ném exception', function () use ($channel, $message, $failTemporarily): void {
                $failTemporarily();
                $result = $channel()->send($message());
                expect($result)->toBeInstanceOf(SendResult::class)->and($result->isRetryable())->toBeTrue();
            });

            it('tin thiếu nội dung/tham số → permanent, không ném exception', function () use ($channel, $message, $succeed): void {
                $succeed();
                $original = $message();
                $empty = new OutgoingMessage($original->logId, $original->idempotencyKey, $original->type, $original->recipient, null, '', [], 1);
                expect($channel()->send($empty)->kind)->toBe('permanent');
            });
        });
    }
}
