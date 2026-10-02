<?php

use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->resolve = fn (?string $at = null) => app(PriceResolver::class)
        ->forVariants([$this->s->id, $this->m->id], new PricingContext((new DateTimeImmutable($at ?? 'now'))->getTimestamp()));
});

it('lấy giá từ bảng giá đang bật', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [590_000], $this->m->id => [620_000]]);

    $prices = ($this->resolve)();

    expect($prices[$this->s->id]->amount->amount)->toBe(590_000)
        ->and($prices[$this->m->id]->amount->amount)->toBe(620_000);
});

it('bảng giá khuyến mãi trong khung giờ thắng, giá base thành giá gốc', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);
    P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'starts_at' => '2026-11-11 00:00', 'ends_at' => '2026-11-12 00:00'], [$this->s->id => [413_000]]);

    $during = ($this->resolve)('2026-11-11 12:00')[$this->s->id];
    $after = ($this->resolve)('2026-11-12 00:00')[$this->s->id];

    expect($during->amount->amount)->toBe(413_000)
        ->and($during->compareAt->amount)->toBe(590_000)
        ->and($during->discountPercent)->toBe(30)
        ->and($during->priceListCode)->toBe('sale-1111')
        ->and($after->amount->amount)->toBe(590_000)
        ->and($after->compareAt)->toBeNull();
});

it('bỏ qua bảng giá đang tắt', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);
    P::priceList(['code' => 'off', 'priority' => 99, 'status' => 'inactive'], [$this->s->id => [1_000]]);

    expect(($this->resolve)()[$this->s->id]->amount->amount)->toBe(590_000);
});

it('cùng priority thì giá thấp hơn thắng', function () {
    P::priceList(['code' => 'a'], [$this->s->id => [590_000]]);
    P::priceList(['code' => 'b'], [$this->s->id => [650_000]]);

    expect(($this->resolve)()[$this->s->id]->amount->amount)->toBe(590_000);
});
