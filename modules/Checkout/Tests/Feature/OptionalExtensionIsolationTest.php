<?php

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\PaymentGateway;

require_once __DIR__.'/CheckoutTestHelpers.php';

/*
| P1-7: implementation lỗi trên luồng tuỳ chọn không làm hỏng quote/checkout/tìm kiếm.
*/

final class BrokenRateProvider implements ShippingRateProvider
{
    public function options(TotalsContext $context): array
    {
        throw new RuntimeException('API báo cước sập');
    }
}

final class BrokenGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'broken_gateway';
    }

    public function label(): string
    {
        return 'Cổng lỗi';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities;
    }

    public function isAvailable(PaymentContext $context): bool
    {
        throw new RuntimeException('health check lỗi');
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        throw new LogicException;
    }
}

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->headers = [];
});

it('hãng báo cước lỗi và cổng thanh toán lỗi health check → quote vẫn trả, chỉ ẩn phần lỗi', function () {
    app(Extensions::class)->tag([BrokenRateProvider::class], ShippingRateProvider::TAG);
    app(Extensions::class)->tag([BrokenGateway::class], PaymentGateway::TAG);
    $created = $this->postJson('/api/storefront/v1/carts', [], $this->headers);
    $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

    $quote = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", [], $headers)->assertOk();

    expect(array_column($quote->json('data.shipping_options'), 'code'))->toBe(['standard'])
        ->and(array_column($quote->json('data.payment_methods'), 'code'))->not->toContain('broken_gateway')
        ->and(array_column($quote->json('data.payment_methods'), 'code'))->toContain('cod');
});
