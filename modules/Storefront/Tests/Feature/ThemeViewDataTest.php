<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Storefront\View\Composers\ImageComposer;
use Modules\Storefront\View\Composers\ListingComposer;
use Modules\Storefront\View\Composers\NotificationComposer;

/*
| Dữ liệu theme dựng sẵn ở Core (view composer) — template vani-base chỉ in ra: link lọc/sắp xếp/phân trang, `sizes` của ảnh,
| thông báo từ flash; layout (đầu/chân trang, link tài khoản). Không template nào của vani-base gọi lớp Core hay đọc request.
*/

beforeEach(function () {
    view()->addNamespace('theme', base_path('custom/theme/vani-base/views'));
    $this->compose = function (object $composer, string $view, array $data): array {
        $instance = view()->make($view, $data);
        $composer->compose($instance);

        return $instance->getData();
    };
    $this->request = function (string $uri, ?string $route = null): Request {
        $request = Request::create($uri);
        if ($route !== null) {
            $request->setRouteResolver(fn () => (new Route('GET', '/x', []))->name($route));
        }

        return $request;
    };
    $this->listing = [
        'total' => 45, 'per_page' => 20, 'page' => 2, 'sortable_by_price' => true, 'items' => [],
        'filters' => [
            ['key' => 'brand', 'param' => 'brand', 'label' => 'Thương hiệu', 'display' => 'list', 'options' => [['value' => 'a', 'label' => 'A', 'count' => 3], ['value' => 'b', 'label' => 'B', 'count' => 1]]],
            ['key' => 'price', 'param' => 'price', 'label' => 'Giá', 'display' => 'range', 'range' => ['min' => 100_000, 'max' => 900_000]],
        ],
    ];
});

it('danh sách: link bật/tắt lọc giữ tham số khác, bỏ trang; form giá giữ bộ lọc khác + link bỏ lọc giá; sắp xếp; phân trang', function () {
    $request = ($this->request)('/tim-kiem?brand=a&price_min=200000&sort=price_asc&page=2');
    $view = ($this->compose)(new ListingComposer($request, app(MoneyFormatter::class)), 'theme::partials.listing', ['listing' => $this->listing])['listingView'];

    [$brand, $price] = $view['filters'];
    expect(array_column($brand['options'], 'active'))->toBe([true, false])
        ->and(urldecode($brand['options'][1]['url']))->toContain('brand=a,b')->not->toContain('page=')
        ->and($price['range']['hidden_fields'])->toBe(['brand' => 'a', 'sort' => 'price_asc'])
        ->and($price['range']['min_value'])->toBe('200000')
        ->and($price['range']['min_placeholder'])->toBe(app(MoneyFormatter::class)->formatAmount(100_000, (string) config('vanishop.currency')))
        ->and(urldecode((string) $price['range']['clear_url']))->toEndWith('/tim-kiem?brand=a&sort=price_asc')
        ->and($view['sort'])->toMatchArray(['current' => 'price_asc', 'by_price' => true, 'hidden_fields' => ['brand' => 'a', 'price_min' => '200000']])
        ->and(array_map(fn ($page) => [$page['number'], $page['current'], $page['url'] !== null], $view['pages']))->toBe([[1, false, true], [2, true, false], [3, false, true]])
        ->and($view['context'])->toBe(['type' => 'search', 'category' => null, 'brand' => null]);

    // Chưa lọc giá: không có link bỏ lọc; một trang: không phân trang.
    $view = ($this->compose)(new ListingComposer(($this->request)('/tim-kiem'), app(MoneyFormatter::class)), 'theme::partials.listing', ['listing' => [...$this->listing, 'total' => 5]])['listingView'];
    expect($view['filters'][1]['range']['clear_url'])->toBeNull()->and($view['pages'])->toBe([])->and($view['sort']['current'])->toBe('newest');
});

it('danh sách: trang thương hiệu không có bộ lọc thương hiệu', function () {
    $view = ($this->compose)(new ListingComposer(($this->request)('/thuong-hieu/a', 'storefront.brand'), app(MoneyFormatter::class)), 'theme::partials.listing', ['listing' => $this->listing])['listingView'];

    expect(array_column($view['filters'], 'key'))->toBe(['price']);
});

it('ảnh: sizes dựng từ layout khi có srcset; không srcset → không sizes', function () {
    expect(($this->compose)(new ImageComposer, 'theme::partials.image', ['src' => '/a.jpg', 'srcset' => '/a-300.jpg 300w', 'layout' => [1152, 4, 2]])['sizes'])->toContain('(min-width: 768px)')
        ->and(($this->compose)(new ImageComposer, 'theme::partials.image', ['src' => '/a.jpg', 'srcset' => null])['sizes'])->toBeNull();
});

it('thông báo: mỗi mục một id riêng; trang cache CDN không đọc flash', function () {
    session()->flash('status', 'Đã lưu.');
    session()->flash('error', 'Lỗi.');
    $items = ($this->compose)(app(NotificationComposer::class), 'theme::partials.notification', [])['notifications'];

    expect(array_column($items, 'id'))->toBe(['vani-notification-0', 'vani-notification-1'])
        ->and(array_column($items, 'role'))->toBe(['status', 'alert'])
        ->and(($this->compose)(app(NotificationComposer::class), 'theme::partials.notification', ['pageCache' => true])['notifications'])->toBe([]);
});

it('layout: chân trang (liên hệ, mạng xã hội, phương thức thanh toán, bản quyền mặc định), link tài khoản theo khách', function () {
    config(['app.name' => 'Vani']);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff')
        ->put('/admin/storefront/theme-settings', ['settings' => ['footer' => ['contact_phone' => '0901 234 567', 'facebook_url' => 'https://facebook.com/vani', 'payment_methods' => "COD\n\n Chuyển khoản "]]])
        ->assertSessionHasNoErrors();

    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('href="tel:0901234567"')
        ->toContain('https://facebook.com/vani')
        ->toContain('>Chuyển khoản</li>')
        ->toContain('© '.date('Y').' Vani')
        ->toContain('href="'.url('/tai-khoan/dang-nhap').'"');
});

it('template vani-base không gọi lớp Core, không đọc request/session, không tự tính tỉ lệ ảnh', function () {
    $offenders = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('custom/theme/vani-base/views'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $source = (string) file_get_contents($file->getPathname());
        foreach (['\\Modules\\', 'request()', 'session(', 'vani_product_ratio', 'url()->current'] as $needle) {
            if (str_contains($source, $needle)) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).": {$needle}";
            }
        }
    }

    expect($offenders)->toBe([]);
});
