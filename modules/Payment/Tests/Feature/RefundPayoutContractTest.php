<?php

use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Testing\RefundPayoutContract;
use Modules\Payment\Tests\Fixtures\LimitedPayout;

/*
| P4/R26: implementation mẫu chạy bộ contract test của RefundPayout (cả nhánh thành công và từ chối).
*/

$refund = fn (int $amount) => fn () => new RefundData('01HREFUND000000000000ABC1', '01HPAY0000000000000000ABC1', 'CT000001', $amount, 'VND', 'Đổi size', ['full_name' => 'Khách thử']);

RefundPayoutContract::define('fixture limited (chi được)', fn () => new LimitedPayout, $refund(120_000));
RefundPayoutContract::define('fixture limited (vượt hạn mức)', fn () => new LimitedPayout, $refund(2_000_000));
