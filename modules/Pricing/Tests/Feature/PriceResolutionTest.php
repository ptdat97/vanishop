<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->web = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create();
    $this->shopee = Channel::factory()->forBrand($this->brand, 'shopee.test')->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->resolve = fn (int $channelId, ?string $at = null) => app(PriceResolver::class)
        ->forVariants([$this->s->id, $this->m->id], new PricingContext($channelId, (new DateTimeImmutable($at ?? 'now'))->getTimestamp()));
});

it('lấy giá từ bảng giá được gán cho kênh', function () {
    P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], [$this->s->id => [590_000], $this->m->id => [620_000]]);

    $prices = ($this->resolve)($this->web->id);

    expect($prices[$this->s->id]->amount->amount)->toBe(590_000)
        ->and($prices[$this->m->id]->amount->amount)->toBe(620_000)
        ->and(($this->resolve)($this->shopee->id))->toBe([]);
});

it('bảng giá khuyến mãi trong khung giờ thắng, giá base thành giá gốc', function () {
    P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], [$this->s->id => [590_000]]);
    P::priceList($this->brand->id, ['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'starts_at' => '2026-11-11 00:00', 'ends_at' => '2026-11-12 00:00'], [$this->web->id], [$this->s->id => [413_000]]);

    $during = ($this->resolve)($this->web->id, '2026-11-11 12:00')[$this->s->id];
    $after = ($this->resolve)($this->web->id, '2026-11-12 00:00')[$this->s->id];

    expect($during->amount->amount)->toBe(413_000)
        ->and($during->compareAt->amount)->toBe(590_000)
        ->and($during->discountPercent)->toBe(30)
        ->and($during->priceListCode)->toBe('sale-1111')
        ->and($after->amount->amount)->toBe(590_000)
        ->and($after->compareAt)->toBeNull();
});

it('bỏ qua bảng giá đang tắt', function () {
    P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], [$this->s->id => [590_000]]);
    P::priceList($this->brand->id, ['code' => 'off', 'priority' => 99, 'status' => 'inactive'], [$this->web->id], [$this->s->id => [1_000]]);

    expect(($this->resolve)($this->web->id)[$this->s->id]->amount->amount)->toBe(590_000);
});

it('mỗi kênh có thể dùng bảng giá khác nhau', function () {
    P::priceList($this->brand->id, ['code' => 'web'], [$this->web->id], [$this->s->id => [590_000]]);
    P::priceList($this->brand->id, ['code' => 'shopee'], [$this->shopee->id], [$this->s->id => [650_000]]);

    expect(($this->resolve)($this->web->id)[$this->s->id]->amount->amount)->toBe(590_000)
        ->and(($this->resolve)($this->shopee->id)[$this->s->id]->amount->amount)->toBe(650_000);
});
