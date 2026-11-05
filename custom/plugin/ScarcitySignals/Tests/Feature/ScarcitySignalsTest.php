<?php

use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\ScarcitySignals\Infrastructure\Signals;
use Plugin\ScarcitySignals\ScarcitySignalsServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    // Mỗi size còn 2 (≤ ngưỡng mặc định 5).
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 2);
    $this->style = $this->s->style;
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(ScarcitySignalsServiceProvider::ID);
        app(PluginManager::class)->enable(ScarcitySignalsServiceProvider::ID);
    });
    app()->register(ScarcitySignalsServiceProvider::class);
    Cache::flush();
    $this->sold = [];
    app()->instance(OrderStatistics::class, Mockery::mock(OrderStatistics::class, fn ($mock) => $mock->shouldReceive('confirmedQuantitiesByStyle')->andReturnUsing(fn () => $this->sold)));
    $this->set = fn (array $values) => T::seed(function () use ($values) {
        foreach ($values as $key => $value) {
            app(Settings::class)->set(ScarcitySignalsServiceProvider::ID, $key, $value);
        }
        Cache::flush();
    });
    $this->extension = fn () => $this->getJson('/api/storefront/v1/products/'.$this->style->slug)->assertOk()->json('data.extensions')[ScarcitySignalsServiceProvider::ID] ?? null;
});

it('"Chỉ còn N" theo từng size khi 0 < còn lại ≤ ngưỡng; ngưỡng riêng theo danh mục lấy nhỏ nhất; tắt được', function () {
    expect(($this->extension)())->toBe(['low_stock' => [(string) $this->s->id => 2, (string) $this->m->id => 2], 'threshold' => 5, 'sold' => null]);
    $html = $this->get('/san-pham/'.$this->style->slug)->assertOk()->getContent();
    expect($html)->toContain('data-scarcity-left')->toContain('Chỉ còn __N__ sản phẩm');

    [$parent, $child] = T::seed(function () {
        $parent = Category::factory()->create(['slug' => 'nu']);

        return [$parent, Category::factory()->childOf($parent)->create(['slug' => 'dam'])];
    });
    T::seed(fn () => $this->style->update(['primary_category_id' => $child->id]));
    ($this->set)(['threshold_overrides' => "nu = 8\n{$child->id} = 1\nrác"]);
    expect(($this->extension)())->toBeNull(); // ngưỡng = min(8, 1) = 1 < 2

    ($this->set)(['threshold_overrides' => 'nu = 8']);
    expect(($this->extension)()['threshold'])->toBe(8);

    ($this->set)(['show_low_stock' => false]);
    expect(($this->extension)())->toBeNull();
    expect($this->get('/san-pham/'.$this->style->slug)->getContent())->not->toContain('data-scarcity-left');
});

it('"Đã bán N trong X ngày": số từ đơn đã thu tiền/đã giao; dưới mức tối thiểu hoặc tắt thì giấu', function () {
    $this->sold = [$this->style->id => 12];
    ($this->set)(['show_low_stock' => false, 'sold_window_days' => 14]);

    expect(($this->extension)()['sold'])->toBe(['count' => 12, 'days' => 14]);
    $this->get('/san-pham/'.$this->style->slug)->assertSee('Đã bán 12 trong 14 ngày qua');

    $this->sold = [$this->style->id => 2];
    Cache::flush();
    expect(($this->extension)())->toBeNull(); // 2 < mức tối thiểu 3

    $this->sold = [$this->style->id => 50];
    ($this->set)(['show_sold_count' => false]);
    expect(($this->extension)())->toBeNull();
});

it('nhãn "Sắp hết" ở thẻ sản phẩm theo tổng số bán được của mọi size; còn nhiều hoặc tắt thì không có', function () {
    $search = fn () => $this->get('/tim-kiem?q='.urlencode('Đầm'))->assertOk()->getContent();
    expect($search())->toContain('data-scarcity-badge'); // 2 + 2 = 4 ≤ 5

    ($this->set)(['low_stock_threshold' => 3]);
    expect($search())->not->toContain('data-scarcity-badge');

    ($this->set)(['low_stock_threshold' => 5, 'show_list_label' => false]);
    expect($search())->not->toContain('data-scarcity-badge');
});

it('dòng ngưỡng riêng sai định dạng bị bỏ; ngưỡng bị kẹp trong 1–100', function () {
    expect(Signals::overrides("ao-thun = 10\n 12=3 \nkhông hợp lệ\nvay = 0\nx = 500"))->toBe(['ao-thun' => 10, '12' => 3, 'x' => 100]);
});
