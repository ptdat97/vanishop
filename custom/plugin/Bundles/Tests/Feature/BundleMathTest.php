<?php

use Plugin\Bundles\Domain\BundleMath;

it('tính số bộ đủ món, giảm vào dòng rẻ nhất trước và giữ giá gốc cho phần dư', function () {
    // Combo: áo (style 1) × 1 + váy (style 2) × 1, giảm 10%. Khách có 2 áo khác size (giá 300k và 250k) và 1 váy 200k.
    $lines = [
        11 => ['style_id' => 1, 'quantity' => 1, 'unit_price' => 300_000],
        12 => ['style_id' => 1, 'quantity' => 1, 'unit_price' => 250_000],
        21 => ['style_id' => 2, 'quantity' => 1, 'unit_price' => 200_000],
    ];
    $requirement = [1 => 1, 2 => 1];

    expect(BundleMath::sets($lines, $requirement))->toBe(1)
        ->and(BundleMath::discounts($lines, $requirement, 1_000))->toBe([12 => 25_000, 21 => 20_000])
        ->and(BundleMath::discounts([11 => $lines[11]], $requirement, 1_000))->toBe([])
        ->and(BundleMath::sets([11 => ['style_id' => 1, 'quantity' => 5, 'unit_price' => 1]], [1 => 2, 2 => 1]))->toBe(0);
});
