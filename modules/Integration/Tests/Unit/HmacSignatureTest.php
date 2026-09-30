<?php

use Modules\Integration\Domain\HmacSignature;

it('ký và xác minh t + "." + payload', function () {
    $header = HmacSignature::header('secret', '{"a":1}', 1_700_000_000);

    expect($header)->toBe('t=1700000000,v1='.hash_hmac('sha256', '1700000000.{"a":1}', 'secret'))
        ->and(HmacSignature::verify('secret', '{"a":1}', $header, 1_700_000_100))->toBeTrue();
});

it('từ chối payload bị sửa, secret sai, lệch giờ > 5 phút, header hỏng', function (string $secret, string $payload, int $now, ?string $header) {
    $header ??= HmacSignature::header('secret', 'body', 1_700_000_000);

    expect(HmacSignature::verify($secret, $payload, $header, $now))->toBeFalse();
})->with([
    'payload sửa' => ['secret', 'body2', 1_700_000_000, null],
    'secret sai' => ['other', 'body', 1_700_000_000, null],
    'quá hạn' => ['secret', 'body', 1_700_000_301, null],
    'thiếu v1' => ['secret', 'body', 1_700_000_000, 't=1700000000'],
    'rác' => ['secret', 'body', 1_700_000_000, 'abc'],
]);

it('chấp nhận nhiều v1 (xoay vòng secret)', function () {
    $valid = HmacSignature::compute('secret', 'body', 1_700_000_000);

    expect(HmacSignature::verify('secret', 'body', "t=1700000000,v1=deadbeef,v1={$valid}", 1_700_000_000))->toBeTrue();
});

it('payload request gắn method + đường dẫn', function () {
    expect(HmacSignature::requestPayload('get', '/api/integration/v1/orders?limit=5', ''))->toBe('GET./api/integration/v1/orders?limit=5.');
});
