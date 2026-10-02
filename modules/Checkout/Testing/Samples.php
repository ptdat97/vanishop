<?php

declare(strict_types=1);

namespace Modules\Checkout\Testing;

use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Shared\Domain\Money\Money;

/**
 * Dữ liệu mẫu cho contract test của Checkout (không cần DB): giỏ 2 dòng, VND.
 */
final class Samples
{
    public static function totalsContext(?int $brandId = 1): TotalsContext
    {
        $line = fn (int $key, int $quantity, int $unit): TotalsLine => new TotalsLine(
            key: $key, variantId: 100 + $key, brandId: $brandId, styleId: 10, sku: "SKU-{$key}", name: "Sản phẩm {$key}", colorName: 'Đen', sizeCode: 'M',
            imageUrl: null, quantity: $quantity, unitPrice: Money::vnd($unit), compareAt: null, subtotal: Money::vnd($unit * $quantity), discount: Money::vnd(0),
        );

        return new TotalsContext(
            customerId: null, currencyCode: 'VND', lines: [$line(1, 2, 150_000), $line(2, 1, 99_000)], adjustments: [],
            voucherCodes: [], shippingMethod: 'standard',
            shippingAddress: ['province_code' => '79', 'province_name' => 'TP. Hồ Chí Minh', 'ward_code' => '26734', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'],
            now: 1_760_000_000,
        );
    }

    public static function checkoutRequest(): CheckoutRequest
    {
        $context = self::totalsContext();

        return new CheckoutRequest(
            new CartKey('01JCONTRACTTESTCART0000001', 'token'),
            ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'email' => 'lan@example.com'],
            $context->shippingAddress, 'standard', 'cod', [], null, 429_000,
        );
    }

    public static function totals(): Totals
    {
        $context = self::totalsContext();
        $subtotal = $context->linesTotal();

        return new Totals('VND', $context->lines, [], $subtotal, Money::vnd(0), null, Money::vnd(0), $subtotal, [], null);
    }
}
