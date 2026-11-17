<?php

declare(strict_types=1);

namespace Modules\Ordering\Testing;

use Closure;
use Modules\Ordering\Contracts\ConfirmationChannel;
use Modules\Ordering\Contracts\Data\OrderData;

/**
 * Contract test cho ConfirmationChannel. Kiểm tra: mã, nhãn; gửi yêu cầu xác nhận cho đơn mẫu trả thông điệp cho nhân
 * viên (bên gọi giả lập kênh gửi thật).
 */
final class ConfirmationChannelContract
{
    /**
     * @param  Closure(): ConfirmationChannel  $channel
     * @param  Closure(): OrderData  $order
     */
    public static function define(string $label, Closure $channel, Closure $order): void
    {
        describe("ConfirmationChannel contract: {$label}", function () use ($channel, $order): void {
            it('mã, nhãn; gửi yêu cầu trả thông điệp', function () use ($channel, $order): void {
                $message = $channel()->request($order());
                expect($channel()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/')
                    ->and($channel()->label())->not->toBe('')
                    ->and($message)->toBeString()
                    ->and($message)->not->toBe('');
            });
        });
    }
}
