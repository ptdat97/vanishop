<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Testing\TaxCalculatorContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\TaxVnVat\Infrastructure\VnVatInclusiveTax;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

TaxCalculatorContract::define('vani.tax-vn-vat', fn () => app(VnVatInclusiveTax::class));

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->tax = function (): int {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return (int) $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", [], $headers)->json('data.tax_included.amount');
    };
});

it('VAT gồm trong giá theo thuế suất cấu hình; Admin ghi đè .env', function () {
    expect(($this->tax)())->toBe(27_273); // 300.000 × 10/110

    T::seed(fn () => app(Settings::class)->set('vani.tax-vn-vat', 'rate_bp', 800));
    expect(($this->tax)())->toBe(22_222); // 300.000 × 8/108
});

it('tắt plugin thuế → Core dùng `none` (không thuế), checkout vẫn chạy', function () {
    app(CurrentContext::class)->set(ContextScope::system('test'));
    app(PluginManager::class)->disable('vani.tax-vn-vat');

    expect(($this->tax)())->toBe(0);
});
