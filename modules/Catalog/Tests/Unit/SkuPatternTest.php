<?php

use Modules\Catalog\Domain\SkuPattern;

it('sinh SKU chuẩn hoá từ mã style, màu, size', function (array $parts, string $sku) {
    expect(SkuPattern::make(...$parts))->toBe($sku);
})->with([
    [['LM24-SH012', 'IVR', 'M'], 'LM24-SH012-IVR-M'],
    [['lm24.sh012', 'ivr', '38.5'], 'LM24-SH012-IVR-38-5'],
    [['ST_01', 'BLK', 'ONE'], 'ST-01-BLK-ONE'],
]);
