<?php

use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Application\InboxProcessor;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\InboundHandler;
use Modules\Integration\Contracts\Inbox;
use Modules\Integration\Persistence\Models\InboxRecord;
use Modules\Integration\Tests\Feature\Fixtures\FakeInboundHandler;

beforeEach(function () {
    config(['vanishop.integration.retry_jitter' => 0.0]);
    FakeInboundHandler::$handled = [];
    FakeInboundHandler::$results = [];
    app(Extensions::class)->tag([FakeInboundHandler::class], InboundHandler::TAG);
    $this->inbox = app(Inbox::class);
    $this->process = fn (): int => app(InboxProcessor::class)->run();
    $this->row = fn (string $id) => InboxRecord::query()->where('system', 'fake-shop')->where('external_event_id', $id)->sole();
});

it('message trùng (system, external_event_id) chỉ lưu và xử lý một lần', function () {
    expect($this->inbox->receive('fake-shop', 'evt-1', 'order.status', ['status' => 'shipped']))->toBeTrue()
        ->and($this->inbox->receive('fake-shop', 'evt-1', 'order.status', ['status' => 'shipped']))->toBeFalse()
        ->and($this->inbox->receive('other', 'evt-1', 'order.status', []))->toBeTrue();

    ($this->process)();
    ($this->process)();

    expect(FakeInboundHandler::$handled)->toHaveCount(1)
        ->and(FakeInboundHandler::$handled[0]->payload)->toBe(['status' => 'shipped'])
        ->and(($this->row)('evt-1')->status->value)->toBe('processed')
        ->and(InboxRecord::query()->where('system', 'other')->sole()->last_error)->toBe('handler.missing:other:order.status')
        ->and(InboxRecord::query()->where('system', 'other')->sole()->status->value)->toBe('failed');
});

it('retryable → thử lại theo backoff; stale → ignored_stale; permanent → failed', function () {
    FakeInboundHandler::$results = [DeliveryResult::retryable('timeout'), DeliveryResult::stale(), DeliveryResult::permanent('bad data')];
    $this->inbox->receive('fake-shop', 'evt-1', 'order.status', []);
    $this->inbox->receive('fake-shop', 'evt-2', 'order.status', []);
    $this->inbox->receive('fake-shop', 'evt-3', 'order.status', []);

    ($this->process)();
    expect(($this->row)('evt-1')->status->value)->toBe('received')
        ->and(($this->row)('evt-1')->attempts)->toBe(1)
        ->and(($this->row)('evt-2')->status->value)->toBe('ignored_stale')
        ->and(($this->row)('evt-3')->status->value)->toBe('failed');

    $this->travel(61)->seconds();
    ($this->process)();
    expect(($this->row)('evt-1')->status->value)->toBe('processed')
        ->and(FakeInboundHandler::$handled[3]->attempt)->toBe(2);
});
