<?php

use Modules\Integration\Domain\RetryPolicy;

it('chờ theo backoff 1m → 24h rồi hết lượt', function () {
    $policy = new RetryPolicy(jitter: 0.0);

    expect(array_map(fn (int $attempt): ?int => $policy->delayAfter($attempt), range(1, 7)))
        ->toBe([60, 300, 900, 3600, 21600, 86400, null])
        ->and($policy->maxRetries())->toBe(6);
});

it('jitter giữ độ trễ trong ±20%', function () {
    $policy = new RetryPolicy;

    expect($policy->delayAfter(2, fn (int $min, int $max): int => $min))->toBe(240)
        ->and($policy->delayAfter(2, fn (int $min, int $max): int => $max))->toBe(360);
});

it('từ chối số lần thất bại < 1', function () {
    (new RetryPolicy)->delayAfter(0);
})->throws(InvalidArgumentException::class);
