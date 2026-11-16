<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\CostPrice\CostPriceServiceProvider;
use Plugin\CostPrice\Infrastructure\NineThousandRounding;

require_once __DIR__.'/../../../../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| vani.cost-price — tham chiếu EP v3 X4: cách tính "Giá vốn + %" (BulkPriceMode), làm tròn đuôi 9.000 (PriceRounding),
| cảnh báo "Dưới giá vốn" (vani.pricing.bulk.warnings); dùng cột + công cụ tải file (X1) trên trang làm việc bảng giá.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CostPriceServiceProvider::ID);
        app(PluginManager::class)->enable(CostPriceServiceProvider::ID);
    });
    app()->register(CostPriceServiceProvider::class);
    $this->style = T::product();
    [$this->s, $this->m] = P::variants($this->style);
    P::priceList(['code' => 'base'], [$this->s->id => [300_000], $this->m->id => [300_000]]);
    $this->list = P::priceList(['code' => 'sale-1', 'type' => 'sale', 'priority' => 10], [$this->s->id => [100_000]]);
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage', 'cost-price.manage']), 'staff');
    $this->url = "/admin/pricing/price-lists/{$this->list->id}";
    $tool = route('admin.extensions.tool', ['resource' => 'price_list_item', 'plugin' => CostPriceServiceProvider::ID, 'key' => 'import_cost']);
    $this->post($tool, ['file' => UploadedFile::fake()->createWithContent('cost.csv', "SKU;Giá vốn\n{$this->s->sku};120.000\n{$this->m->sku};150000\nKHONG-CO;1\n")])
        ->assertSessionHas('success', 'Đã nhập giá vốn 2 SKU; 1 SKU không có.');
});

it('trang làm việc: cột giá vốn, cảnh báo "Dưới giá vốn" cạnh cảnh báo Core; hộp thoại có cách tính và làm tròn của plugin', function () {
    $this->get("{$this->url}/prices")->assertInertia(fn (Assert $page) => $page
        ->where('bulkOptions', ['modes' => [['code' => 'cost_markup', 'label' => 'Giá vốn + %', 'hint' => '% cộng thêm vào giá vốn']], 'roundings' => [['code' => 'tail_9000', 'label' => 'Giá đẹp đuôi 9.000']]])
        ->where('rows.0.warnings', ['over_cap', 'Dưới giá vốn'])
        ->where('summary.warnings', ['over_cap' => 1, 'Dưới giá vốn' => 1]));
    expect($this->get("{$this->url}/prices")->viewData('page')['props']['extensions']['values'][$this->s->id]['vani.cost-price:cost'])->toBe('120.000 ₫');
});

it('"Giá vốn + 30%" làm tròn đuôi 9.000: xem trước rồi áp; giảm % của Core với làm tròn của plugin', function () {
    $payload = ['scope' => 'variants', 'variant_ids' => [$this->s->id, $this->m->id], 'mode' => 'cost_markup', 'value' => 30, 'round_to' => 0, 'rounding' => 'tail_9000'];

    $this->postJson("{$this->url}/bulk/preview", $payload)->assertOk()
        ->assertJsonPath('data.rows.0.new_amount', 159_000)->assertJsonPath('data.rows.1.new_amount', 199_000);
    $this->post("{$this->url}/bulk", $payload)->assertSessionHasNoErrors();
    $this->post("{$this->url}/bulk", ['scope' => 'variants', 'variant_ids' => [$this->m->id], 'mode' => 'percent', 'value' => 40, 'round_to' => 1000, 'rounding' => 'tail_9000'])->assertSessionHasNoErrors();

    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 159_000, $this->m->id => 189_000]);
    $this->post("{$this->url}/bulk", [...$payload, 'mode' => 'khong_co'])->assertSessionHasErrors('mode');
});

it('làm tròn đuôi 9.000', function (int $amount, int $expected) {
    expect((new NineThousandRounding)->round($amount))->toBe($expected);
})->with([[191_000, 199_000], [199_000, 199_000], [200_000, 209_000], [5_000, 5_000], [9_500, 19_000]]);

it('xoá SKU (tắt ô ma trận, về sản phẩm đơn): giá vốn không giữ SKU lại nhưng được dọn trước khi xoá', function () {
    $usage = app(Extensions::class)->implementations(VariantUsage::TAG, VariantUsage::class)[CostPriceServiceProvider::ID] ?? null;

    expect($usage)->not->toBeNull()->and($usage->used([$this->s->id, $this->m->id]))->toBe([]);
    T::seed(fn () => $usage->release([$this->m->id]));
    expect(T::seed(fn () => DB::table('plg_cost_prices')->pluck('variant_id')->map(fn ($id): int => (int) $id)->all()))->toBe([$this->s->id]);
});
