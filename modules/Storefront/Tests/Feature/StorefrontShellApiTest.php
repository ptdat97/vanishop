<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| "Vỏ" trang qua Storefront API (ADR-044, api-first-refactor đợt A2): client không phải Blade dựng được đầu/chân trang,
| menu và trang chủ chỉ từ API — cùng dữ liệu mà layout và trang chủ Blade dùng.
*/

beforeEach(function () {
    ['brand' => $this->brand] = C::store();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
    $this->api = '/api/storefront/v1';
});

it('GET /store: tên, ngôn ngữ, quy tắc định dạng tiền, ẩn giá, mua không đăng nhập, tìm kiếm phổ biến, vị trí menu, đầu/chân trang', function () {
    T::seed(fn () => app(Settings::class)->set('core', 'storefront.popular_searches', "đầm lụa\náo linen"));
    $this->put('/admin/storefront/theme-settings', ['settings' => [
        'header' => ['announcement' => ['vi' => 'Miễn phí giao từ 500.000', 'en' => ''], 'announcement_url' => ''],
        'footer' => ['contact_phone' => '0912 345 678', 'copyright' => ['vi' => '© Cửa hàng Lan', 'en' => '']],
    ]])->assertSessionHasNoErrors();

    $store = $this->getJson("{$this->api}/store")->assertOk()->json('data');
    expect($store['name'])->toBe(config('app.name'))
        ->and($store['locale'])->toMatchArray(['current' => 'vi', 'default' => 'vi'])
        ->and($store['currency'])->toMatchArray(['currency' => 'VND', 'symbol' => '₫', 'exponent' => 0])
        ->and($store['prices'])->toBe(['visibility' => 'public', 'hidden' => false])
        ->and($store['guest_checkout'])->toBeTrue()
        ->and($store['popular_searches'])->toBe(['đầm lụa', 'áo linen'])
        ->and(array_column($store['menu_locations'], 'code'))->toBe(['header', 'mobile', 'footer_1', 'footer_2', 'footer_3', 'footer_4'])
        ->and($store['theme']['settings']['header']['announcement'])->toBe('Miễn phí giao từ 500.000')
        ->and($store['theme']['settings']['footer']['copyright'])->toBe('© Cửa hàng Lan');

    // Chỉ hiện giá cho thành viên + tắt mua không đăng nhập: khách vãng lai biết trước để dựng giao diện đúng.
    T::seed(function () {
        app(Settings::class)->set('core', 'storefront.price_visibility', 'members');
        app(Settings::class)->set('core', 'storefront.guest_checkout', false);
    });
    $this->getJson("{$this->api}/store")->assertJsonPath('data.prices', ['visibility' => 'members', 'hidden' => true])
        ->assertJsonPath('data.guest_checkout', false);
});

it('GET /menus/{location}: menu đã xuất bản giống hệt dữ liệu layout Blade dùng; chưa xuất bản → rỗng; vị trí lạ → 404', function () {
    $category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-vay']));
    $category->syncTranslations(['vi' => ['name' => 'Đầm váy']]);
    $item = fn (array $overrides = []): array => ['link' => null, 'label' => [], 'badge' => null, 'enabled' => true, 'children' => [], ...$overrides];

    $this->getJson("{$this->api}/menus/header")->assertOk()->assertJsonPath('data', [])->assertJsonPath('meta.published', false);
    $this->put('/admin/storefront/menus/header', ['items' => [
        $item(['link' => ['type' => 'category', 'value' => (string) $category->id], 'badge' => 'sale', 'children' => [$item(['link' => ['type' => 'brand', 'value' => (string) $this->brand->id]])]]),
    ]])->assertSessionHasNoErrors();
    $this->post('/admin/storefront/menus/header/publish')->assertSessionHasNoErrors();

    $menu = $this->getJson("{$this->api}/menus/header")->assertOk()->assertJsonPath('meta.published', true)->json('data');
    expect($menu[0])->toMatchArray(['label' => 'Đầm váy', 'url' => route('storefront.category', 'dam-vay'), 'badge' => 'sale'])
        ->and($menu[0]['children'][0]['url'])->toBe(route('storefront.brand', $this->brand->slug))
        ->and($menu)->toEqual(app(MenuPresenter::class)->items('header', 'vi'));

    $this->getJson("{$this->api}/menus/khong-co")->assertNotFound();
});

it('GET /pages/home: chưa cấu hình → null; có khối → [{type, data}] theo thứ tự, HTML đã làm sạch ở dạng chuỗi, khối lỗi bị bỏ', function () {
    $this->getJson("{$this->api}/pages/home")->assertOk()->assertJsonPath('data', null)->assertJsonPath('meta.configured', false);

    $this->put('/admin/storefront/home-blocks', ['blocks' => [
        ['type' => 'hero', 'config' => ['title' => 'Bộ sưu tập Hè', 'link_label' => 'Mua ngay', 'link_url' => 'javascript:alert(1)']],
        ['type' => 'rich_text', 'config' => ['body' => '<script>alert(1)</script>Miễn phí giao']],
        ['type' => 'product_grid', 'config' => ['title' => 'Bán chạy', 'source' => 'brand', 'slug' => $this->brand->slug, 'limit' => 4]],
    ]])->assertSessionHasNoErrors();

    $home = $this->getJson("{$this->api}/pages/home")->assertOk()->assertJsonPath('meta.configured', true)->json('data');
    expect(array_column($home, 'type'))->toBe(['hero', 'rich_text', 'product_grid'])
        ->and($home[0]['data'])->toMatchArray(['title' => 'Bộ sưu tập Hè', 'linkUrl' => ''])
        ->and($home[1]['data']['bodyHtml'])->toBeString()->toContain('Miễn phí giao')->not->toContain('<script>')
        ->and($home[2]['data']['title'])->toBe('Bán chạy')
        ->and($home[2]['data']['products'][0]['name'])->toBe('Đầm lụa')
        ->and($home[2]['data']['products'][0])->toHaveKey('price');

    // Blade dựng trang chủ từ cùng dữ liệu.
    $this->get('/')->assertOk()->assertSee('Bộ sưu tập Hè')->assertSee('Bán chạy');
});
