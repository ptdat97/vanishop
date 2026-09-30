<?php

use Modules\Integration\Domain\PayloadMasker;

it('che PII lồng nhau, giữ nguyên dữ liệu khác', function () {
    $masked = PayloadMasker::mask([
        'data' => ['order' => ['number' => 'LU-2610-0001', 'customer' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678'], 'total_amount' => 330000]],
        'email' => 'ab',
    ]);

    expect($masked['data']['order']['number'])->toBe('LU-2610-0001')
        ->and($masked['data']['order']['total_amount'])->toBe(330000)
        ->and($masked['data']['order']['customer']['full_name'])->toBe('Ng**********an')
        ->and($masked['data']['order']['customer']['phone'])->toBe('09******78')
        ->and($masked['email'])->toBe('**');
});
