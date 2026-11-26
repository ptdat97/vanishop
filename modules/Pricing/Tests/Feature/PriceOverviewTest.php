<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Facades\Hook;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

/*
| Stabilization A5: tổng quan giá đang chạy ở trang Bảng giá — đợt đang chạy (sắp kết thúc), sắp bắt đầu, SKU đang bán chưa có
| giá niêm yết, cảnh báo trên giá đang hiệu lực (hook vani.pricing.bulk.warnings, vd. giá vốn).
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view']), 'staff');
    [$this->s, $this->m] = P::variants(T::product(null, ['product_code' => 'DAM01']));
    P::priceList(['code' => 'base'], [$this->s->id => [300_000]]);
    $this->overview = fn (): array => $this->get('/admin/pricing/price-lists')->assertOk()->viewData('page')['props']['overview'];
});

it('đợt đang chạy (đánh dấu sắp kết thúc trong 3 ngày), sắp bắt đầu trong 7 ngày; đợt hết hạn/xa hơn không hiện', function () {
    P::priceList(['code' => 'sale-a', 'type' => 'sale', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(2)], [$this->s->id => [250_000]]);
    P::priceList(['code' => 'sale-b', 'type' => 'sale', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)], []);
    P::priceList(['code' => 'sale-c', 'type' => 'sale', 'starts_at' => now()->addDays(3)], []);
    P::priceList(['code' => 'sale-d', 'type' => 'sale', 'starts_at' => now()->addDays(20)], []);
    P::priceList(['code' => 'sale-e', 'type' => 'sale', 'ends_at' => now()->subDay()], []);

    $overview = ($this->overview)();
    expect(collect($overview['running'])->map(fn (array $item): array => [$item['prices'], $item['ending_soon']])->all())->toBe([[1, true], [0, false]])
        ->and($overview['upcoming'])->toHaveCount(1);
});

it('SKU đang bán chưa có giá niêm yết: số lượng + mẫu có link sửa sản phẩm', function () {
    $this->get('/admin/pricing/price-lists')->assertInertia(fn (Assert $page) => $page
        ->where('overview.missing_base.count', 1)
        ->where('overview.missing_base.samples.0.sku', $this->m->sku)
        ->where('overview.missing_base.samples.0.url', url("/admin/catalog/products/{$this->m->product_id}/edit")));
});

it('cảnh báo của plugin trên giá đang chạy (vd. dưới giá vốn) được đếm theo nhãn', function () {
    Hook::onFilter('vani.pricing.bulk.warnings', fn (array $warnings, int $variantId, ?int $listAmount, int $amount): array => $amount < 200_000 ? [...$warnings, 'Dưới giá vốn'] : $warnings);
    P::priceList(['code' => 'sale-a', 'type' => 'sale'], [$this->s->id => [150_000], $this->m->id => [250_000]]);

    $warnings = ($this->overview)()['warnings'];
    expect($warnings['labels'])->toBe(['Dưới giá vốn' => 1])
        ->and($warnings['checked'])->toBe(2)
        ->and($warnings['samples'][0]['sku'])->toBe($this->s->sku);
});
