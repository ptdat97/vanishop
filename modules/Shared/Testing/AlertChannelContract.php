<?php

declare(strict_types=1);

namespace Modules\Shared\Testing;

use Closure;
use Modules\Shared\Contracts\AlertChannel;
use Modules\Shared\Contracts\Data\Alert;

/**
 * Contract test cho AlertChannel. Kiểm tra: mã ổn định; gửi một cảnh báo mẫu không ném lỗi (bên gọi giả lập kênh gửi thật,
 * vd. `Mail::fake()`, `Http::fake()`).
 */
final class AlertChannelContract
{
    /**
     * @param  Closure(): AlertChannel  $channel
     * @param  Closure(): Alert  $alert
     */
    public static function define(string $label, Closure $channel, Closure $alert): void
    {
        describe("AlertChannel contract: {$label}", function () use ($channel, $alert): void {
            it('có mã ổn định', fn () => expect($channel()->code())->toMatch('/^[a-z][a-z0-9_-]*$/'));

            it('gửi cảnh báo mẫu không lỗi', function () use ($channel, $alert): void {
                $channel()->send($alert());
                expect(true)->toBeTrue();
            });
        });
    }
}
