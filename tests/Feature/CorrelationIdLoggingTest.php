<?php

use App\Logging\ContextProcessor;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Correlation id phải xuất hiện trong log, không chỉ nằm trong bộ nhớ Context —
 * nếu không thì không truy vết được một flow end-to-end (go-live gate: observability).
 */
it('đưa correlation_id từ Context vào extra của dòng log', function () {
    Context::add('correlation_id', 'trace-test-123');

    $record = (new ContextProcessor)(new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Info,
        message: 'xin chao',
        context: [],
    ));

    expect($record->extra)->toHaveKey('correlation_id', 'trace-test-123');
});

it('không ghi đè giá trị extra đã có sẵn', function () {
    Context::add('correlation_id', 'tu-context');

    $record = (new ContextProcessor)(new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Info,
        message: 'xin chao',
        context: [],
        extra: ['correlation_id' => 'tu-extra'],
    ));

    expect($record->extra['correlation_id'])->toBe('tu-extra');
});

it('bỏ qua khi Context chưa có correlation_id', function () {
    $record = (new ContextProcessor)(new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Info,
        message: 'xin chao',
        context: [],
    ));

    expect($record->extra)->not->toHaveKey('correlation_id');
});

it('giữ nguyên phần còn lại của record', function () {
    Context::add('correlation_id', 'trace-keep');

    $record = (new ContextProcessor)(new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Warning,
        message: 'giữ message',
        context: ['foo' => 'bar'],
        extra: ['khac' => 1],
    ));

    expect($record->message)->toBe('giữ message')
        ->and($record->context)->toBe(['foo' => 'bar'])
        ->and($record->extra['khac'])->toBe(1);
});

it('chạy được trong pipeline Monolog thật', function () {
    Context::add('correlation_id', 'trace-pipeline-789');

    $handler = new TestHandler;
    (new Logger('probe'))->pushHandler($handler->pushProcessor(new ContextProcessor));

    $handler->handle(new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Error,
        message: 'qua pipeline',
        context: [],
    ));

    $records = $handler->getRecords();
    expect($records)->not->toBeEmpty()
        ->and($records[0]->extra)->toHaveKey('correlation_id', 'trace-pipeline-789');
});

it('channel single/daily cấu hình trong config/logging.php ghi được log kèm correlation_id', function (string $channel) {
    $path = storage_path('framework/testing/log-'.uniqid().'.log');
    config(["logging.channels.{$channel}.path" => $path]);
    Context::add('correlation_id', 'trace-channel-456');

    Log::channel($channel)->warning('ghi thật');

    $files = glob(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'*.log') ?: [];
    expect($files)->not->toBeEmpty()
        ->and(file_get_contents($files[0]))->toContain('ghi thật')->toContain('trace-channel-456');
    array_map('unlink', $files);
})->with(['single', 'daily']);
