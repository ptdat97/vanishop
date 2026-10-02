<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Extension\Domain\Hooks\HookReturnTypeMismatch;
use Modules\Identity\Persistence\Models\StaffUser;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->slug = $this->s->style->slug;
});

it('helper ngắn: vani_filter/vani_add_filter dùng ở mọi nơi, không cần khai báo số tham số; hook phải được khai báo', function () {
    vani_add_filter('vani.storefront.view.pages.product', fn (array $data, string $view): array => [...$data, 'note' => $view], 20);

    expect(vani_filter('vani.storefront.view.pages.product', ['a' => 1], 'theme::pages.product'))->toBe(['a' => 1, 'note' => 'theme::pages.product'])
        ->and(fn () => vani_filter('vani.khong.khai.bao', 1))->toThrow(HookNotDeclared::class);
});

it('khai báo theo mẫu bao mọi hook cùng tiền tố, giữ loại/ổn định/quy tắc lỗi', function () {
    $definition = app(HookRegistry::class)->get('vani.admin.page.ordering.orders.show');

    expect($definition->name)->toBe('vani.admin.page.ordering.orders.show')
        ->and($definition->stability)->toBe('experimental')
        ->and($definition->onError)->toBe('skip')
        ->and(app(HookRegistry::class)->get('vani.admin.page.'))->toBeNull();
});

it('điểm tự động Storefront API: thêm khoá vào phản hồi; listener lỗi bị bỏ; trả sai kiểu bị chặn (strict)', function () {
    vani_add_filter('vani.api.storefront.products.show', fn (array $body): array => [...$body, 'meta' => ['badge' => 'Mới']]);
    vani_add_filter('vani.api.storefront.products.show', fn (array $body): array => throw new RuntimeException('plugin hỏng'), 30);

    $this->getJson("/api/storefront/v1/products/{$this->slug}")->assertOk()
        ->assertJsonPath('data.slug', $this->slug)->assertJsonPath('meta.badge', 'Mới');

    vani_add_filter('vani.api.storefront.products.index', fn (array $body): string => 'sai kiểu');
    $this->withoutExceptionHandling();
    expect(fn () => $this->getJson('/api/storefront/v1/products'))->toThrow(HookReturnTypeMismatch::class);
});

it('điểm tự động trang Admin (Inertia) và view storefront', function () {
    vani_add_filter('vani.admin.page.catalog.products.index', fn (array $props): array => [...$props, 'helloBanner' => 'Xin chào']);
    vani_add_filter('vani.storefront.view.pages.product', function (array $data): array {
        $data['product']['name'] .= ' (bản giới hạn)';

        return $data;
    });

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view'])->create(), 'staff')
        ->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page->where('helloBanner', 'Xin chào')->has('products'));
    $this->get("/san-pham/{$this->slug}")->assertOk()->assertSee('Đầm lụa (bản giới hạn)');
});

it('listener của plugin chưa bật không chạy; plugin bật thì chạy (sở hữu suy ra từ vị trí gọi)', function () {
    app(HookManager::class)->onFilter('vani.api.storefront.products.show', fn (array $body): array => [...$body, 'off' => true], 10, 'vani.not-enabled');
    app(HookManager::class)->onFilter('vani.api.storefront.products.show', fn (array $body): array => [...$body, 'on' => true], 10, 'vani.cod');

    $body = $this->getJson("/api/storefront/v1/products/{$this->slug}")->json();
    expect($body)->toHaveKey('on')->not->toHaveKey('off');
});
