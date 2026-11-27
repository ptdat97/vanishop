<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Links\BrandLinkType;
use Modules\Storefront\Application\Links\CategoryLinkType;
use Modules\Storefront\Application\Links\CollectionLinkType;
use Modules\Storefront\Application\Links\ProductLinkType;
use Modules\Storefront\Application\Links\RouteLinkType;
use Modules\Storefront\Application\Links\UrlLinkType;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Storefront\Application\Menus\MenuRepository;
use Modules\Storefront\Testing\LinkTypeContract;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

LinkTypeContract::define('core category', fn () => app(CategoryLinkType::class),
    visible: fn () => (string) T::seed(fn () => Category::factory()->create(['slug' => 'dam']))->id,
    hidden: fn () => (string) T::seed(fn () => Category::factory()->hidden()->create())->id);
LinkTypeContract::define('core brand', fn () => app(BrandLinkType::class),
    visible: fn () => (string) Brand::factory()->create()->id,
    hidden: fn () => (string) Brand::factory()->create(['status' => Brand::HIDDEN])->id);
LinkTypeContract::define('core collection', fn () => app(CollectionLinkType::class),
    visible: fn () => (string) T::seed(function () {
        $collection = ProductCollection::query()->create(['slug' => 'he-2026', 'status' => 'active']);
        $collection->syncTranslations(['vi' => ['name' => 'Hè 2026']]);

        return $collection;
    })->id,
    hidden: fn () => (string) T::seed(fn () => ProductCollection::query()->create(['slug' => 'an', 'status' => 'hidden']))->id);
LinkTypeContract::define('core product', fn () => app(ProductLinkType::class),
    visible: fn () => (string) T::product(Brand::factory()->create()->id, ['name' => 'Áo lụa'])->id,
    hidden: fn () => (string) T::product(Brand::factory()->create()->id, ['name' => 'Áo nháp', 'status' => 'draft'])->id);
LinkTypeContract::define('core route', fn () => app(RouteLinkType::class), visible: fn () => 'storefront.brands', missing: 'admin.dashboard');
LinkTypeContract::define('core url', fn () => app(UrlLinkType::class), visible: fn () => 'https://zalo.me/0912345678', missing: '//evil.example');

beforeEach(function () {
    ['brand' => $this->brand] = C::store();
    $this->category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-vay']));
    $this->category->syncTranslations(['vi' => ['name' => 'Đầm váy']]);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
    $this->url = '/admin/storefront/menus';
    $this->item = fn (array $overrides = []): array => ['link' => null, 'label' => [], 'badge' => null, 'enabled' => true, 'children' => [], ...$overrides];
});

it('Admin: vị trí theo theme; lưu nháp không đổi cửa hàng; xuất bản thì header đọc menu, link theo slug hiện tại', function () {
    $this->get($this->url)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Menus')
        ->where('location', 'header')
        ->where('locations', fn ($locations) => collect($locations)->pluck('code')->all() === ['header', 'mobile', 'footer_1', 'footer_2', 'footer_3', 'footer_4'])
        ->where('linkTypes', fn ($types) => collect($types)->pluck('type')->contains('category')));

    $this->put("{$this->url}/header", ['items' => [
        ($this->item)(['link' => ['type' => 'category', 'value' => (string) $this->category->id], 'badge' => 'sale', 'children' => [
            ($this->item)(['link' => ['type' => 'brand', 'value' => (string) $this->brand->id]]),
        ]]),
        ($this->item)(['link' => ['type' => 'url', 'value' => 'https://zalo.me/0912345678'], 'label' => ['vi' => 'Chat Zalo']]),
        ($this->item)(['label' => ['vi' => 'Bộ sưu tập'], 'children' => [($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.search'], 'label' => ['vi' => 'Tất cả']])]]),
    ]])->assertSessionHasNoErrors();

    $this->get('/')->assertOk()->assertSee('Thương hiệu')->assertDontSee('Chat Zalo');

    $this->post("{$this->url}/header/publish")->assertSessionHasNoErrors();
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('Đầm váy')->toContain(route('storefront.category', 'dam-vay'))->toContain('Sale')
        ->toContain(route('storefront.brand', $this->brand->slug))
        ->toContain('Chat Zalo')->toContain('https://zalo.me/0912345678')
        ->toContain('Bộ sưu tập')->toContain(route('storefront.search'));

    // Đổi slug: link theo ngay (không cache qua request).
    T::seed(fn () => $this->category->update(['slug' => 'dam-moi']));
    expect($this->get('/')->getContent())->toContain(route('storefront.category', 'dam-moi'))
        ->and(AuditLog::query()->where('action', 'storefront.menu_published')->exists())->toBeTrue();
});

it('menu mobile toàn màn hình: danh mục chia đôi dựng từ menu "Di động" đã phân giải (nhúng JSON)', function () {
    $this->put("{$this->url}/mobile", ['items' => [
        ($this->item)([
            'link' => ['type' => 'route', 'value' => 'storefront.brands'],
            'label' => ['vi' => 'Thương hiệu'],
            'children' => [
                ($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Tra cứu đơn']]),
            ],
        ]),
    ]])->assertSessionHasNoErrors();
    $this->post("{$this->url}/mobile/publish")->assertSessionHasNoErrors();

    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('data-vani-mobile-menu-open')
        ->toContain('data-vani-mobile-menu-fallback')
        ->toContain('id="vani-mobile-menu"')
        ->toContain('data-vani-category-nav')
        ->toContain('data-source="'.route('api.storefront.v1.categories.index').'"')
        ->toContain('data-category-url="'.route('storefront.category', '__vani_slug__').'"')
        ->toContain('data-cnav-template="tab"')
        ->toContain('data-vani-bottom-nav')
        ->toContain('data-cnav-items')
        ->toContain('Tra cứu đơn');
});

it('chưa có menu nào thì menu mobile không nhúng dữ liệu (JS tải cây danh mục Catalog qua API)', function () {
    expect($this->get('/')->assertOk()->getContent())->toContain('data-vani-category-nav')->not->toContain('data-cnav-items');
});

it('ảnh mục menu theo image_depth của vị trí: "mobile" giữ ảnh mọi cấp, "header" chỉ cấp 1', function () {
    $tree = [($this->item)([
        'label' => ['vi' => 'Nữ'], 'image_media_id' => 11,
        'children' => [($this->item)([
            'label' => ['vi' => 'Trang phục'], 'image_media_id' => 12,
            'children' => [($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'image_media_id' => 13])],
        ])],
    ])];

    $this->put("{$this->url}/mobile", ['items' => $tree])->assertSessionHasNoErrors();
    $this->put("{$this->url}/header", ['items' => $tree])->assertSessionHasNoErrors();

    $mobile = app(MenuRepository::class)->state('mobile')['draft'][0];
    $header = app(MenuRepository::class)->state('header')['draft'][0];
    expect([$mobile['image_media_id'], $mobile['children'][0]['image_media_id'], $mobile['children'][0]['children'][0]['image_media_id']])->toBe([11, 12, 13])
        ->and([$header['image_media_id'], $header['children'][0]['image_media_id'], $header['children'][0]['children'][0]['image_media_id']])->toBe([11, null, null]);
});

it('storefront bỏ mục tắt, ngoài khung giờ, hoặc trỏ tới đối tượng đã ẩn (kèm nhánh con)', function () {
    $hidden = T::seed(fn () => Category::factory()->hidden()->create(['slug' => 'an-di']));
    $this->put("{$this->url}/header", ['items' => [
        ($this->item)(['link' => ['type' => 'category', 'value' => (string) $hidden->id], 'label' => ['vi' => 'Mục ẩn'], 'children' => [($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Con của mục ẩn']])]]),
        ($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Đang tắt'], 'enabled' => false]),
        ($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Chưa tới giờ'], 'visible_from' => now()->addDay()->format('Y-m-d\TH:i')]),
        ($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Đã hết giờ'], 'visible_to' => now()->subDay()->format('Y-m-d\TH:i')]),
        ($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => 'Tra cứu đơn']]),
    ]])->assertSessionHasNoErrors();
    $this->post("{$this->url}/header/publish");

    $items = app(MenuPresenter::class)->items('header', 'vi');
    expect(array_column($items, 'label'))->toBe(['Tra cứu đơn']);
});

it('từ chối link nguy hiểm, quá độ sâu của vị trí, loại link không có', function () {
    $this->put("{$this->url}/header", ['items' => [($this->item)(['link' => ['type' => 'url', 'value' => 'javascript:alert(1)'], 'label' => ['vi' => 'X']])]])
        ->assertSessionHasErrors('items.0.link');
    $this->put("{$this->url}/header", ['items' => [($this->item)(['link' => ['type' => 'nope', 'value' => '1']])]])->assertSessionHasErrors('items.0.link');
    $this->put("{$this->url}/footer_1", ['items' => [($this->item)(['label' => ['vi' => 'Hỗ trợ'], 'children' => [($this->item)(['label' => ['vi' => 'Con']])]])]])
        ->assertSessionHasErrors('items.0.children');
    $this->put("{$this->url}/header", ['items' => [($this->item)()]])->assertSessionHasErrors('items.0.label');
    $this->put("{$this->url}/khong-co", ['items' => []])->assertNotFound();
    $this->put("{$this->url}/sidebar", ['items' => []])->assertSessionHasErrors('location');
});

it('phiên bản: xuất bản đẩy bản cũ vào lịch sử; khôi phục vào nháp; bỏ nháp về bản đang hiển thị', function () {
    $publish = function (string $label): void {
        $this->put("{$this->url}/footer_1", ['items' => [($this->item)(['link' => ['type' => 'route', 'value' => 'storefront.track'], 'label' => ['vi' => $label]])]]);
        $this->post("{$this->url}/footer_1/publish");
    };
    $publish('Bản 1');
    $publish('Bản 2');
    expect(app(MenuPresenter::class)->items('footer_1', 'vi')[0]['label'])->toBe('Bản 2');

    $this->get("{$this->url}?location=footer_1")->assertInertia(fn (Assert $page) => $page->has('menu.versions', 1)->where('menu.has_changes', false));
    $this->post("{$this->url}/footer_1/restore/0")->assertSessionHasNoErrors();
    $this->get("{$this->url}?location=footer_1")->assertInertia(fn (Assert $page) => $page->where('menu.items.0.label.vi', 'Bản 1')->where('menu.has_changes', true));
    expect($this->get('/')->getContent())->toContain('Bản 2')->not->toContain('Bản 1');

    $this->post("{$this->url}/footer_1/discard");
    $this->get("{$this->url}?location=footer_1")->assertInertia(fn (Assert $page) => $page->where('menu.items.0.label.vi', 'Bản 2'));
});

it('phân giải theo lô: số truy vấn không tăng theo số link cùng loại', function () {
    $categories = T::seed(fn () => Category::factory()->count(6)->create());
    $queries = function (int $count) use ($categories): int {
        $this->put("{$this->url}/header", ['items' => $categories->take($count)->map(fn ($category) => ($this->item)(['link' => ['type' => 'category', 'value' => (string) $category->id]]))->values()->all()]);
        $this->post("{$this->url}/header/publish");
        // Mỗi lần đo như một request mới (cây danh mục, ảnh ghi nhớ trong request), sau một lượt làm nóng cache cài đặt.
        app(MenuPresenter::class)->items('header', 'vi');
        app()->forgetScopedInstances();
        DB::enableQueryLog();
        app(MenuPresenter::class)->items('header', 'vi');
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        return $count;
    };

    expect($queries(6))->toBe($queries(1));
});

it('tìm đối tượng cho bộ chọn link; cần quyền storefront.manage', function () {
    $this->getJson("{$this->url}/links?type=category&q=dam")->assertOk()->assertJsonPath('data.0.label', 'Đầm váy')->assertJsonPath('data.0.value', (string) $this->category->id);

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->getJson("{$this->url}/links?type=category")->assertForbidden();
    $this->put("{$this->url}/header", ['items' => []])->assertForbidden();
});
