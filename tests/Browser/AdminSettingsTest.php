<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Application\LowStockThreshold;
use Modules\Tenancy\Contracts\Settings;

/*
| Admin → Cài đặt trên trình duyệt thật: ô tìm lọc thẻ theo tên mục cấu hình (không dấu); mở nhóm, chuyển nhóm ở cột
| trái — form mang giá trị đã lưu của đúng nhóm đang mở.
*/

beforeEach(function () {
    T::seed(function (): void {
        app(Settings::class)->set('core', LowStockThreshold::SETTING, 7);
        app(Settings::class)->set('core', 'storefront.per_page', 30);
    });
    $this->actingAs(T::staff(['admin.access', 'settings.manage']), 'staff');
});

it('ô tìm lọc thẻ theo tên mục cấu hình (gõ không dấu), mở nhóm rồi chuyển nhóm ở cột trái: form mang giá trị của nhóm mới', function () {
    $value = fn ($page, string $key) => $page->script("document.querySelector('[data-setting=\"{$key}\"] input')?.value ?? null");
    $page = visit('/admin/settings')
        ->assertSee('Cài đặt hệ thống')
        ->assertSee('Cài đặt Plugin')
        ->type('[data-settings-search]', 'sap het hang')
        ->assertPresent('[data-settings-card="system:inventory"]')
        ->assertMissing('[data-settings-card="system:sales"]');

    $page->click('[data-settings-card="system:inventory"]')->assertPresent('[data-setting="inventory.low_stock_threshold"]')->assertDontSee('Cách tính thuế');
    expect($value($page, LowStockThreshold::SETTING))->toBe('7');

    $page->click('[data-settings-nav] a[href$="/admin/settings/system/store"]')->assertPresent('[data-setting="storefront.per_page"]');
    expect($value($page, 'storefront.per_page'))->toBe('30');
    $page->assertNoJavaScriptErrors();
});
