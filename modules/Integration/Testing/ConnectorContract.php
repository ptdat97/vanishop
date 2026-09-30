<?php

declare(strict_types=1);

namespace Modules\Integration\Testing;

use Closure;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\HealthStatus;
use Modules\Integration\Contracts\Data\OutboxMessage;

/**
 * Contract test cho Connector (mô hình B). Plugin cung cấp message mẫu nó hỗ trợ và `prepare` giả lập hệ thống
 * ngoài: thành công / lỗi tạm thời (5xx, timeout) / lỗi dữ liệu (4xx). Kiểm tra: system ổn định; supports đúng;
 * phân loại ok/retryable/permanent và KHÔNG ném exception cho lỗi dự kiến; healthCheck trả HealthStatus.
 * `$sentRequests` (tuỳ chọn) trả các request đã gửi để kiểm tra header `Idempotency-Key = messageId`.
 */
final class ConnectorContract
{
    /**
     * @param  Closure(): Connector  $connector
     * @param  Closure(): OutboxMessage  $message
     * @param  array{ok: Closure(): void, retryable: Closure(): void, permanent: Closure(): void}  $scenarios
     * @param  (Closure(): list<array<string, list<string>>>)|null  $sentHeaders  header của các request đã gửi
     */
    public static function define(string $label, Closure $connector, Closure $message, array $scenarios, ?Closure $sentHeaders = null): void
    {
        describe("Connector contract: {$label}", function () use ($connector, $message, $scenarios, $sentHeaders): void {
            it('có system ổn định và hỗ trợ message mẫu', function () use ($connector, $message): void {
                expect($connector()->system())->toMatch('/^[a-z][a-z0-9_.-]*$/')
                    ->and($connector()->supports($message()->messageType))->toBeTrue()
                    ->and($connector()->supports('khong.ho.tro.loai.nay'))->toBeFalse();
            });

            foreach (['ok' => 'isOk', 'retryable' => 'isRetryable', 'permanent' => 'isPermanent'] as $kind => $check) {
                it("phân loại kết quả: {$kind}", function () use ($connector, $message, $scenarios, $kind, $check, $sentHeaders): void {
                    $scenarios[$kind]();
                    $result = $connector()->send($message());
                    expect($result)->toBeInstanceOf(DeliveryResult::class)->and($result->{$check}())->toBeTrue();

                    if ($kind === 'ok' && $sentHeaders !== null) {
                        $headers = $sentHeaders();
                        expect($headers)->not->toBe([])
                            ->and(end($headers)['Idempotency-Key'][0] ?? null)->toBe($message()->messageId);
                    }
                });
            }

            it('healthCheck trả HealthStatus', function () use ($connector, $scenarios): void {
                $scenarios['ok']();
                expect($connector()->healthCheck())->toBeInstanceOf(HealthStatus::class);
            });
        });
    }
}
