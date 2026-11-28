<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('Admin mở danh sách và form tạo chiến dịch giá không lỗi JS', function () {
    ['s' => $variant] = C::store();
    T::seed(function () {
        $plugins = app(PluginManager::class);
        $plugins->install(PricingCampaignServiceProvider::ID);
        $plugins->enable(PricingCampaignServiceProvider::ID);
    });
    app()->register(PricingCampaignServiceProvider::class);
    app(PluginActivation::class)->flush();

    $this->actingAs(T::staff(['admin.access', 'pricing_campaign.view', 'pricing_campaign.manage', 'media.view']), 'staff');

    $page = visit('/admin/plugins/vani-pricing-campaign')
        ->assertSee('Chiến dịch giá')
        ->assertNoJavaScriptErrors();

    $page->click('Thêm chiến dịch')
        ->assertPathIs('/admin/plugins/vani-pricing-campaign/create')
        ->assertSee('Thêm chiến dịch giá')
        ->assertNoJavaScriptErrors();

    // Điền tên nội bộ, tìm & thêm sản phẩm qua Combobox, kiểm tra giá sau giảm hiển thị.
    $page->fill('input[maxlength="120"] >> nth=0', 'Flash sale test')
        ->type('input[aria-label="Thêm sản phẩm theo tên…"]', $variant->product->product_code)
        ->click('[role="option"] >> text='.$variant->product->product_code)
        ->assertSee('Đầm lụa')
        ->assertNoJavaScriptErrors();

    // Tạo chiến dịch qua POST rồi mở trang sửa + lưu lại + xoá trên trình duyệt.
    $this->post('/admin/plugins/vani-pricing-campaign', [
        'template' => 'flash_sale',
        'name' => 'Flash sale browser',
        'label' => 'Flash sale',
        'badge' => 'Flash sale',
        'content' => '',
        'badge_style' => 'danger',
        'accent_color' => null,
        'banner_media_id' => null,
        'show_countdown' => true,
        'show_stock' => true,
        'priority' => 40,
        'customer_group_id' => null,
        'starts_at' => now()->subHour()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDay()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'recurrence' => null,
        'quota_total' => null,
        'per_customer_total' => null,
        'status' => 'active',
        'items' => [['product_id' => $variant->product_id, 'strategy' => 'percent', 'value' => 20, 'quota' => 10, 'per_customer' => 2]],
    ])->assertSessionHasNoErrors();

    $campaign = PricingCampaign::query()->sole();
    $page->navigate("/admin/plugins/vani-pricing-campaign/{$campaign->id}")
        ->assertSee('Flash sale browser')
        ->assertSee('Đầm lụa')
        ->fill('input[maxlength="120"] >> nth=0', 'Flash sale browser v2')
        ->press('Lưu')
        ->waitForText('Đã lưu chiến dịch giá.')
        ->assertNoJavaScriptErrors();
});
