<?php

use Illuminate\Support\HtmlString;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Identity\Persistence\Models\StaffUser;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->sku, 'brand' => $this->brand] = C::store();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
});

it('Builder chỉ cho trang chủ: trang khác không có trình biên tập hay API bố cục', function () {
    $this->get('/admin/storefront/builder/home')->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Builder')
        ->missing('surfaces')->missing('zones')->where('configured', false));
    foreach (['product', 'listing', 'cart', 'brands'] as $surface) {
        $this->get("/admin/storefront/builder/{$surface}")->assertNotFound();
        $this->getJson("/api/storefront/v1/pages/layouts/{$surface}")->assertNotFound();
    }
    $this->put(route('admin.storefront.builder.update'), ['blocks' => [['type' => 'listing_product_grid', 'config' => []]]])
        ->assertSessionHasErrors('blocks.0.type');
});

it('trang khác thêm nội dung bằng code tại slot; đầu trang danh sách dùng phần mặc định của theme khi không plugin nào thay', function () {
    $category = T::seed(function (): Category {
        $category = Category::factory()->create(['slug' => 'dam-lua']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm lụa', 'description' => 'Lụa tơ tằm mát nhẹ.']]);
        $this->sku->product->categories()->attach($category);

        return $category;
    });
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.pdp.after_details', fn () => new HtmlString('<p>CAM-KET-PDP</p>'));

    $this->get(route('storefront.product', $this->sku->product->slug))->assertOk()->assertSee('CAM-KET-PDP', false);
    $this->get('/danh-muc/dam-lua')->assertOk()
        ->assertSeeInOrder(['data-category-banner', 'Đầm lụa', 'Lụa tơ tằm mát nhẹ.', 'data-vani-listing'], false);

    $hooks->onSlot('vani.storefront.plp.header', fn (array $context) => ($context['category']['id'] ?? null) === $category->id
        ? new HtmlString('<section>BANNER-RIENG</section>') : null);
    $this->get('/danh-muc/dam-lua')->assertOk()->assertSee('BANNER-RIENG', false)->assertDontSee('data-category-banner', false);
    $this->get('/thuong-hieu/'.$this->brand->slug)->assertOk()->assertSee('data-brand-banner', false)->assertDontSee('BANNER-RIENG', false);
});

it('xem trước block nháp bằng theme và nhập bố cục trước khi lưu', function () {
    $block = ['type' => 'rich_text', 'config' => ['body' => '<p>Nội dung nháp độc lập</p>']];
    $preview = route('admin.storefront.builder.draft', 'preview');
    $html = $this->postJson($preview, ['blocks' => [$block]])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('html');
    expect($html)->toContain('name="viewport"', 'Nội dung nháp độc lập');
    $this->get(route('storefront.home'))->assertDontSee('Nội dung nháp độc lập');

    $import = route('admin.storefront.builder.draft', 'import');
    $this->postJson($import, ['version' => 1, 'blocks' => [[...$block, 'zone' => 'main']]])
        ->assertOk()->assertJsonPath('blocks.0.type', 'rich_text')->assertJsonMissingPath('blocks.0.zone');
    $this->postJson($import, ['version' => 2, 'blocks' => [$block]])->assertUnprocessable()->assertJsonValidationErrors('version');
    $this->postJson($import, ['version' => 1, 'blocks' => [['type' => 'khong-co']]])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.type');
    $this->get(route('admin.storefront.builder.edit'))->assertInertia(fn (Assert $page) => $page->where('configured', false));
});

it('yêu cầu quyền quản lý giao diện khi xem trước hoặc nhập bản nháp', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    foreach (['preview', 'import'] as $operation) {
        $this->postJson(route('admin.storefront.builder.draft', $operation), ['blocks' => []])->assertForbidden();
    }
});
