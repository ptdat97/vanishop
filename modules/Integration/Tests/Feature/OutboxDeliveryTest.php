<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Integration\Application\OutboxWorker;
use Modules\Integration\Application\ReplayService;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\OutboxMessage;
use Modules\Integration\Domain\HmacSignature;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Testing\ConnectorContract;
use Modules\Integration\Tests\Feature\Fixtures\FakeErpConnector;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    config(['vanishop.integration.retry_jitter' => 0.0]);
    FakeErpConnector::reset();
    H::client('erp-main');
    [$this->subscription, $this->secret] = H::subscription('erp-main', ['*'], 'https://erp.example/hooks');
    $this->work = fn (): int => app(OutboxWorker::class)->run();
    $this->row = fn (string $aggregate = 'LU-1', ?string $type = null) => OutboxRecord::query()->where('aggregate_id', $aggregate)
        ->when($type !== null, fn ($query) => $query->where('message_type', $type))->sole();
});

it('gửi webhook có envelope, chữ ký HMAC, Idempotency-Key = message_id, correlation id', function () {
    Http::fake(['erp.example/*' => Http::response(['ok' => true])]);
    H::publish('order.created', 'LU-1', ['order' => ['number' => 'LU-1']]);

    expect(($this->work)())->toBe(1);

    $message = ($this->row)();
    Http::assertSent(function (Request $request) use ($message): bool {
        return $request->url() === 'https://erp.example/hooks'
            && $request->header('Idempotency-Key')[0] === $message->message_id
            && $request->header('X-Vani-Event')[0] === 'order.created'
            && $request->header('X-Correlation-Id')[0] === (string) $message->correlation_id
            && HmacSignature::verify($this->secret, $request->body(), $request->header('X-Vani-Signature')[0], now()->getTimestamp())
            && $request['event_type'] === 'order.created'
            && $request['data']['order']['number'] === 'LU-1';
    });
    expect($message->status->value)->toBe('sent')
        ->and($message->attempts)->toBe(1)
        ->and($message->sent_at)->not->toBeNull();
});

it('5xx/timeout → retry theo backoff; hết lượt → dead', function () {
    config(['vanishop.integration.webhook_pause_after_hours' => 24 * 30]);
    $this->freezeSecond();
    Http::fake(['erp.example/*' => Http::response('down', 503)]);
    H::publish('order.created', 'LU-1');

    ($this->work)();
    $message = ($this->row)();
    expect($message->status->value)->toBe('pending')
        ->and($message->attempts)->toBe(1)
        ->and($message->last_error)->toBe('http 503')
        ->and($message->next_attempt_at->getTimestamp())->toBe(now()->addMinute()->getTimestamp());

    expect(($this->work)())->toBe(0); // chưa tới giờ thử lại

    foreach ([300, 900, 3600, 21600, 86400] as $delay) {
        $this->travel($delay)->seconds();
        ($this->work)();
        expect(($this->row)()->status->value)->toBe('pending');
    }
    $this->travel(86400)->seconds();
    ($this->work)();

    expect(($this->row)()->status->value)->toBe('dead')
        ->and(($this->row)()->attempts)->toBe(7)
        ->and(($this->row)()->next_attempt_at)->toBeNull();
});

it('4xx do dữ liệu → failed ngay, không retry', function () {
    Http::fake(['erp.example/*' => Http::response(['error' => 'bad'], 422)]);
    H::publish('order.created', 'LU-1');

    ($this->work)();

    expect(($this->row)()->status->value)->toBe('failed')
        ->and(($this->row)()->last_error)->toBe('http 422');
    Http::assertSentCount(1);
});

it('giữ thứ tự trong cùng aggregate: message sau chờ message trước đang retry; aggregate khác vẫn chạy', function () {
    Http::fakeSequence('erp.example/*')->push('down', 500)->whenEmpty(Http::response('ok'));
    H::publish('order.created', 'LU-1');
    H::publish('order.confirmed', 'LU-1');
    H::publish('order.created', 'LU-2');

    ($this->work)();

    expect(($this->row)('LU-1', 'order.created')->status->value)->toBe('pending')
        ->and(($this->row)('LU-1', 'order.confirmed')->status->value)->toBe('pending')
        ->and(($this->row)('LU-1', 'order.confirmed')->attempts)->toBe(0)
        ->and(($this->row)('LU-2')->status->value)->toBe('sent');

    $this->travel(2)->minutes();
    ($this->work)();

    expect(($this->row)('LU-1', 'order.created')->status->value)->toBe('sent')
        ->and(($this->row)('LU-1', 'order.confirmed')->status->value)->toBe('sent');
});

it('connector: ok lưu external_id; permanent → failed; exception → retry', function () {
    $this->subscription->delete();
    app(Extensions::class)->tag([FakeErpConnector::class], Connector::TAG);
    FakeErpConnector::$results = [DeliveryResult::ok('SO-001'), DeliveryResult::permanent('mapping.missing:warehouse:WH-1'), new RuntimeException('boom')];
    H::publish('order.created', 'LU-1');
    H::publish('order.created', 'LU-2');
    H::publish('order.created', 'LU-3');
    H::publish('payment.captured', 'LU-4'); // connector không hỗ trợ → không có message

    ($this->work)();

    expect(($this->row)('LU-1')->external_id)->toBe('SO-001')
        ->and(($this->row)('LU-2')->status->value)->toBe('failed')
        ->and(($this->row)('LU-2')->last_error)->toBe('mapping.missing:warehouse:WH-1')
        ->and(($this->row)('LU-3')->status->value)->toBe('pending')
        ->and(($this->row)('LU-3')->last_error)->toContain('boom')
        ->and(OutboxRecord::query()->where('aggregate_id', 'LU-4')->exists())->toBeFalse()
        ->and(FakeErpConnector::$sent[0]->messageId)->toBe(($this->row)('LU-1')->message_id)
        ->and(FakeErpConnector::$sent[0]->attempt)->toBe(1);
});

it('webhook lỗi liên tục quá 24h → subscription tạm dừng', function () {
    Http::fake(['erp.example/*' => Http::response('down', 500)]);
    H::publish('order.created', 'LU-1');

    ($this->work)();
    expect($this->subscription->fresh()->failing_since)->not->toBeNull()
        ->and($this->subscription->fresh()->status)->toBe('active');

    $this->travel(25)->hours();
    ($this->work)();
    expect($this->subscription->fresh()->status)->toBe('paused');

    H::publish('order.created', 'LU-2'); // subscription dừng → không nhận message mới
    expect(OutboxRecord::query()->where('aggregate_id', 'LU-2')->exists())->toBeFalse();
});

it('replay message failed/dead: giữ message_id, reset lượt thử, có audit; CLI lọc theo target', function () {
    Http::fake(['erp.example/*' => Http::sequence()->push('bad', 400)->push('bad', 400)->whenEmpty(Http::response('ok'))]);
    H::publish('order.created', 'LU-1');
    H::publish('order.created', 'LU-2');
    ($this->work)();
    $first = ($this->row)('LU-1');
    expect($first->status->value)->toBe('failed');

    $count = T::seed(fn (): int => app(ReplayService::class)->replayOutbox(['ids' => [$first->id]]));
    expect($count)->toBe(1)
        ->and(($this->row)('LU-1')->status->value)->toBe('pending')
        ->and(($this->row)('LU-1')->attempts)->toBe(0)
        ->and(AuditLog::query()->where('action', 'integration.outbox.replayed')->count())->toBe(1);

    ($this->work)();
    expect(($this->row)('LU-1')->status->value)->toBe('sent')
        ->and(($this->row)('LU-1')->message_id)->toBe($first->message_id);

    Artisan::call('vani:integration:replay', ['--target' => $this->subscription->target(), '--status' => 'failed']);
    expect(($this->row)('LU-2')->status->value)->toBe('pending');
    Artisan::call('vani:integration:dispatch');
    expect(($this->row)('LU-2')->status->value)->toBe('sent');
});

it('message processing bị bỏ dở (worker chết) được trả về hàng đợi', function () {
    Http::fake(['erp.example/*' => Http::response('ok')]);
    H::publish('order.created', 'LU-1');
    ($this->row)()->update(['status' => 'processing', 'locked_at' => now()->subMinutes(11)]);

    ($this->work)();

    expect(($this->row)()->status->value)->toBe('sent');
});

ConnectorContract::define(
    'fixture fake-erp (mẫu cách dùng bộ contract)',
    fn () => new FakeErpConnector,
    fn () => new OutboxMessage('11111111-1111-1111-1111-111111111111', 'fake-erp', 'order.created', '1', 'order', 'LU-1', [], null, 1),
    [
        'ok' => fn () => FakeErpConnector::$results = [DeliveryResult::ok('SO-1')],
        'retryable' => fn () => FakeErpConnector::$results = [DeliveryResult::retryable('http 503')],
        'permanent' => fn () => FakeErpConnector::$results = [DeliveryResult::permanent('http 422')],
    ],
);
