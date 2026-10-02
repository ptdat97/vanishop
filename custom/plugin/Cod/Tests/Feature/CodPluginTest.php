<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cod\Infrastructure\CodGateway;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

PaymentGatewayContract::define('vani.cod', fn () => app(CodGateway::class));

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->quote = function (): array {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return array_column($this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", [], $headers)->json('data.payment_methods'), 'code');
    };
});

it('ngưỡng COD đặt trong Admin → Cấu hình ghi đè .env', function () {
    expect(($this->quote)())->toContain('cod');

    T::seed(fn () => app(Settings::class)->set('vani.cod', 'max_amount', 100_000));
    expect(($this->quote)())->not->toContain('cod');
});

it('extension point bắt buộc: tắt vani.cod được khi còn cổng khác, tắt cổng cuối cùng bị từ chối', function () {
    $plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));

    $plugins->disable('vani.cod');
    expect(($this->quote)())->not->toContain('cod');

    expect(fn () => $plugins->disable('vani.bank-transfer'))
        ->toThrow(PluginOperationFailed::class, 'Cổng thanh toán');

    $plugins->enable('vani.cod');
    $plugins->disable('vani.bank-transfer');
    expect(($this->quote)())->toBe(['cod']);
});
