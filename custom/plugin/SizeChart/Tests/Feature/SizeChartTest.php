<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\SizeChart\Infrastructure\SizeCharts;
use Plugin\SizeChart\Persistence\SizeChart;
use Plugin\SizeChart\SizeChartServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->style = $this->s->style;
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(SizeChartServiceProvider::ID);
        app(PluginManager::class)->enable(SizeChartServiceProvider::ID);
    });
    app()->register(SizeChartServiceProvider::class);
    $this->charts = app(SizeCharts::class);
    $this->chart = fn (string $name, array $overrides = []) => T::seed(fn () => $this->charts->save([
        'names' => ['vi' => $name], 'base_unit' => 'cm', 'display_unit' => null,
        'blocks' => [['type' => 'table', 'rows' => [['Size', 'Ngực'], ['S', '80'], ['M', '84']]]],
        'advisor_enabled' => false, 'advisor_rules' => [], 'is_default' => false, 'active' => true, ...$overrides,
    ]));
    $this->extension = fn () => $this->getJson('/api/storefront/v1/products/'.$this->style->slug)->assertOk()->json('data.extensions')['vani.size-chart'] ?? null;
});

it('chọn bảng theo ưu tiên: sản phẩm → danh mục chính (kế thừa danh mục cha) → thương hiệu → mặc định; bảng tắt bị bỏ qua', function () {
    [$women, $dresses] = T::seed(function () {
        $women = Category::factory()->create(['slug' => 'nu']);

        return [$women, Category::factory()->childOf($women)->create(['slug' => 'dam'])];
    });
    T::seed(fn () => $this->style->update(['primary_category_id' => $dresses->id]));
    $default = ($this->chart)('Mặc định', ['is_default' => true]);
    $brandChart = ($this->chart)('Theo thương hiệu');
    $womenChart = ($this->chart)('Đồ nữ');
    $productChart = ($this->chart)('Riêng sản phẩm', ['active' => false]);

    expect(($this->extension)()['name'])->toBe('Mặc định');
    T::seed(fn () => $this->charts->assign('brand', $this->brand->id, $brandChart->id));
    expect(($this->extension)()['name'])->toBe('Theo thương hiệu');
    T::seed(fn () => $this->charts->assign('category', $women->id, $womenChart->id));
    expect(($this->extension)()['name'])->toBe('Đồ nữ'); // danh mục cha của danh mục chính
    T::seed(fn () => $this->charts->assign('product', $this->style->id, $productChart->id));
    expect(($this->extension)()['name'])->toBe('Đồ nữ'); // bảng gán sản phẩm đang tắt

    T::seed(fn () => SizeChart::query()->update(['active' => false]));
    expect(($this->extension)())->toBeNull();
    expect($default->fresh()->is_default)->toBeTrue();
});

it('đổi đơn vị: số trong ô (trừ hàng/cột tiêu đề) theo tỉ lệ, làm tròn 1 chữ số; ô chữ giữ nguyên; chỉ một bảng mặc định', function () {
    expect(SizeCharts::convert([['Size', 'Ngực'], ['S', '80'], ['M', 'tự do'], ['L', '86,5']], 1 / 2.54))
        ->toBe([['Size', 'Ngực'], ['S', '31.5'], ['M', 'tự do'], ['L', '34.1']]);

    ($this->chart)('A', ['is_default' => true, 'display_unit' => 'inch']);
    ($this->chart)('B', ['is_default' => true]);
    expect(SizeChart::query()->where('is_default', true)->pluck('names')->all())->toBe([['vi' => 'B']]);

    T::seed(fn () => $this->charts->assign('product', $this->style->id, SizeChart::query()->get()->first(fn ($chart) => $chart->names['vi'] === 'A')->id));
    expect(($this->extension)()['blocks'][0])->toMatchArray(['rows' => [['Size', 'Ngực'], ['S', '80'], ['M', '84']], 'converted' => [['Size', 'Ngực'], ['S', '31.5'], ['M', '33.1']]]);
});

it('luật gợi ý không hợp lệ bị loại khi lưu; khối văn bản không chạy được HTML/script', function () {
    $chart = ($this->chart)('Có gợi ý', ['advisor_enabled' => true, 'is_default' => true, 'advisor_rules' => [
        ['size' => 'S', 'height_min' => 150, 'height_max' => 160, 'weight_min' => 40, 'weight_max' => 50, 'note' => 'Ôm'],
        ['size' => '', 'height_min' => 1, 'height_max' => 2, 'weight_min' => 1, 'weight_max' => 2],
        ['size' => 'M', 'height_min' => 170, 'height_max' => 160, 'weight_min' => 50, 'weight_max' => 60],
    ], 'blocks' => [['type' => 'text', 'markdown' => '**Đo vòng ngực** <script>alert(1)</script> [x](javascript:alert(1))']]]);

    expect($chart->advisor_rules)->toHaveCount(1)->and($chart->advisor_rules[0])->toMatchArray(['size' => 'S', 'note' => 'Ôm']);

    $html = $this->get('/san-pham/'.$this->style->slug)->assertOk()->getContent();
    expect($html)->toContain('data-vani-size-chart')->toContain('<strong>Đo vòng ngực</strong>')->toContain('Tìm size của tôi')
        ->not->toContain('<script>alert(1)')->not->toContain('javascript:alert');
});

it('Admin: tạo, sửa (xung đột lock_version), xoá bỏ mọi gán; gán bảng ở form sản phẩm, danh mục, thương hiệu', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'size_chart.view', 'size_chart.manage']), 'staff');
    $payload = ['names' => ['vi' => 'Váy'], 'base_unit' => 'cm', 'display_unit' => 'inch', 'blocks' => [['type' => 'table', 'rows' => [['Size', 'Eo'], ['S', '64']]]],
        'advisor_enabled' => true, 'advisor_rules' => [['size' => 'S', 'height_min' => 150, 'height_max' => 160, 'weight_min' => 40, 'weight_max' => 50, 'note' => '']], 'is_default' => false, 'active' => true];

    $this->post('/admin/plugins/vani-size-chart', [...$payload, 'names' => ['vi' => '']])->assertSessionHasErrors('names.vi');
    $this->post('/admin/plugins/vani-size-chart', [...$payload, 'advisor_rules' => [['size' => 'S', 'height_min' => 170, 'height_max' => 160, 'weight_min' => 40, 'weight_max' => 50]]])->assertSessionHasErrors('advisor_rules.0.height_max');
    $this->post('/admin/plugins/vani-size-chart', $payload)->assertRedirect();
    $chart = SizeChart::query()->sole();

    $this->put("/admin/plugins/vani-size-chart/{$chart->id}", [...$payload, 'names' => ['vi' => 'Váy nữ'], 'lock_version' => 0])->assertSessionHasNoErrors();
    $this->put("/admin/plugins/vani-size-chart/{$chart->id}", [...$payload, 'lock_version' => 0])->assertSessionHasErrors('lock_version');
    $this->get('/admin/plugins/vani-size-chart')->assertInertia(fn (Assert $page) => $page->component('SizeChart::Index')->where('charts.0.name', 'Váy nữ'));

    // Gán ở form sản phẩm (lựa chọn đọc lúc mở form).
    $this->get("/admin/catalog/products/{$this->style->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('extensionSections', fn ($sections) => collect($sections)->firstWhere('plugin', 'vani.size-chart')['fields'][0]['options'] === [(string) $chart->id => 'Váy nữ']));
    $category = T::seed(fn () => Category::factory()->create(['slug' => 'ao']));
    $this->put("/admin/catalog/categories/{$category->id}", ['slug' => 'ao', 'parent_id' => null, 'status' => 'active', 'position' => 0, 'lock_version' => $category->fresh()->lock_version,
        'translations' => ['vi' => ['name' => 'Áo']], 'extensions' => ['vani-size-chart' => ['chart' => ['chart' => (string) $chart->id]]]])->assertSessionHasNoErrors();
    $this->put("/admin/catalog/brands/{$this->brand->id}", ['code' => $this->brand->code, 'slug' => $this->brand->slug, 'name' => $this->brand->name, 'description' => null, 'status' => 'active', 'position' => 0, 'lock_version' => $this->brand->fresh()->lock_version,
        'extensions' => ['vani-size-chart' => ['chart' => ['chart' => (string) $chart->id]]]])->assertSessionHasNoErrors();
    expect($this->charts->assignedChart('category', $category->id))->toBe($chart->id)
        ->and($this->charts->assignedChart('brand', $this->brand->id))->toBe($chart->id)
        ->and(($this->extension)()['name'])->toBe('Váy nữ');

    $this->delete("/admin/plugins/vani-size-chart/{$chart->id}")->assertRedirect();
    expect(SizeChart::query()->count())->toBe(0)->and($this->charts->assignedChart('brand', $this->brand->id))->toBeNull();
});
