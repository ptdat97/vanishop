<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

/**
 * Số implementation đang bật mà một extension point bắt buộc phải có để cửa hàng vận hành (ADR-029).
 *
 * Module sở hữu contract đăng ký ở ServiceProvider: `Extensions::requires(PaymentGateway::TAG, Requirement::AtLeastOne, '…')`.
 * Không đặt hằng trên interface: một class có thể implement nhiều contract (vd. GHN là carrier + nguồn phí giao).
 */
enum Requirement: string
{
    /** Ít nhất một implementation đang bật (vd. cổng thanh toán, phí giao). */
    case AtLeastOne = 'at_least_one';

    /** Đúng một implementation có hiệu lực — chọn qua cấu hình khi có nhiều (vd. TaxCalculator, SearchProvider). */
    case ExactlyOne = 'exactly_one';
}
