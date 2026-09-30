<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Application\OutboxWorker;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Tests\Feature\Fixtures\FakeErpConnector;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;

/*
| Nhiều worker outbox chạy song song (FOR UPDATE SKIP LOCKED) → mỗi message gửi đúng một lần,
| và trong cùng aggregate vẫn giữ thứ tự.
*/

require_once __DIR__.'/../../modules/Integration/Tests/Feature/IntegrationTestHelpers.php';

uses(DatabaseTruncation::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }
});

/**
 * @return list<Closure>
 */
function outboxWorkerTasks(int $count, string $log): array
{
    $tasks = [];
    for ($worker = 0; $worker < $count; $worker++) {
        $tasks[] = static function () use ($log): int {
            app(Extensions::class)->tag([FakeErpConnector::class], Connector::TAG);
            FakeErpConnector::$logFile = $log;
            $processed = 0;
            for ($round = 0; $round < 20; $round++) {
                $processed += app(OutboxWorker::class)->run(limit: 5, batchSize: 5);
                usleep(20_000);
            }

            return $processed;
        };
    }

    return $tasks;
}

it('4 worker song song, 40 message / 10 aggregate → mỗi message gửi đúng một lần, đúng thứ tự', function () {
    app(Extensions::class)->tag([FakeErpConnector::class], Connector::TAG);
    for ($i = 0; $i < 40; $i++) {
        H::publish('order.step'.intdiv($i, 10), 'LU-'.($i % 10));
    }
    expect(OutboxRecord::query()->count())->toBe(40);

    $log = storage_path('framework/testing/outbox-concurrency.log');
    @mkdir(dirname($log), 0777, true);
    @unlink($log);

    Concurrency::driver('process')->run(outboxWorkerTasks(4, $log));

    $lines = array_map(fn (string $line): array => explode(' ', $line), file($log, FILE_IGNORE_NEW_LINES));
    $ids = array_column($lines, 0);
    expect($ids)->toHaveCount(40)
        ->and(array_unique($ids))->toHaveCount(40)
        ->and(OutboxRecord::query()->where('status', 'sent')->count())->toBe(40);

    // Trong từng aggregate, thứ tự gửi thực tế (ghi lúc send) = thứ tự phát event.
    foreach (collect($lines)->groupBy(1) as $sent) {
        expect($sent->pluck(2)->all())->toBe(['order.step0', 'order.step1', 'order.step2', 'order.step3']);
    }
});
