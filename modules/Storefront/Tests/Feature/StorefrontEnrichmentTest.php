<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Modules\Storefront\Tests\Feature\Fixtures\BadgeEnricher;
use Modules\Storefront\Tests\Feature\Fixtures\BrokenEnricher;
use Modules\Storefront\Tests\Feature\Fixtures\CartNoteEnricher;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/Fixtures/Enrichers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    T::product($this->s->style->brand_id, ['name' => 'Áo lụa']);
    BadgeEnricher::$calls = 0;
});

it('dữ liệu plugin gắn dưới extensions.<plugin-id>, một lần gọi cho cả danh sách, bỏ id lạ; plugin lỗi bị bỏ', function () {
    app(Extensions::class)->contribute(StorefrontEnricher::TAG, BadgeEnricher::class, 'vani.cod');      // plugin đang bật (hệ thống)
    app(Extensions::class)->contribute(StorefrontEnricher::TAG, BrokenEnricher::class, 'vani.tax-vn-vat');

    $items = $this->getJson('/api/storefront/v1/products')->assertOk()->json('data');

    expect($items)->toHaveCount(2)
        ->and(array_column($items, 'extensions'))->each->toBe(['vani.cod' => ['badge' => 'Mới']])
        ->and(BadgeEnricher::$calls)->toBe(1);
});

it('plugin chưa bật không làm giàu dữ liệu; không plugin nào → không có khoá extensions', function () {
    app(Extensions::class)->contribute(StorefrontEnricher::TAG, BadgeEnricher::class, 'vani.not-enabled');

    $item = $this->getJson('/api/storefront/v1/products')->assertOk()->json('data.0');

    expect($item)->not->toHaveKey('extensions')->and(BadgeEnricher::$calls)->toBe(0);
});

it('giỏ hàng: dữ liệu làm giàu gắn vào giỏ', function () {
    app(Extensions::class)->contribute(StorefrontEnricher::TAG, CartNoteEnricher::class, 'vani.cod');

    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk()->json('data');

    expect($cart['extensions'])->toBe(['vani.cod' => ['freeship_remaining' => 200_000]]);
});
