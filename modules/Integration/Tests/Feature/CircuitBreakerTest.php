<?php

use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Application\OutboxWorker;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Tests\Feature\Fixtures\FakeErpConnector;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    config(['vanishop.integration.retry_jitter' => 0.0, 'vanishop.integration.circuit_threshold' => 3, 'vanishop.integration.circuit_cooldown' => 60]);
    $this->freezeSecond();
    FakeErpConnector::reset();
    app(Extensions::class)->tag([FakeErpConnector::class], Connector::TAG);
    $this->row = fn (string $aggregate) => OutboxRecord::query()->where('aggregate_id', $aggregate)->sole();
});

it('3 lỗi retryable liên tiếp → ngắt mạch: message còn lại hoãn tới hết cooldown, không tốn lượt thử', function () {
    FakeErpConnector::$results = [
        DeliveryResult::retryable('http 503'), DeliveryResult::retryable('http 503'), new RuntimeException('timeout'),
    ];
    foreach (range(1, 5) as $i) {
        H::publish('order.created', "LU-{$i}");
    }

    app(OutboxWorker::class)->run();

    expect(FakeErpConnector::$sent)->toHaveCount(3)
        ->and(($this->row)('LU-4')->attempts)->toBe(0)
        ->and(($this->row)('LU-4')->last_error)->toBe('circuit.open:fake-erp')
        ->and(($this->row)('LU-4')->next_attempt_at->getTimestamp())->toBe(now()->addMinute()->getTimestamp());

    $this->travel(61)->seconds();
    app(OutboxWorker::class)->run();

    expect(FakeErpConnector::$sent)->toHaveCount(8) // mạch đóng lại khi gửi thành công
        ->and(OutboxRecord::query()->where('status', 'sent')->count())->toBe(5);
});

it('lỗi vĩnh viễn (dữ liệu) không làm ngắt mạch', function () {
    FakeErpConnector::$results = array_fill(0, 4, DeliveryResult::permanent('mapping.missing:warehouse:X'));
    foreach (range(1, 5) as $i) {
        H::publish('order.created', "LU-{$i}");
    }

    app(OutboxWorker::class)->run();

    expect(FakeErpConnector::$sent)->toHaveCount(5)
        ->and(($this->row)('LU-5')->status->value)->toBe('sent');
});
