<?php

use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Tenancy\Contracts\Settings;
use Plugin\StorefrontExtras\StorefrontExtrasServiceProvider;

require_once __DIR__.'/../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';

it('sửa repeater cũ, xem trước trên viewport desktop và nhập bố cục vào bản nháp', function () {
    $this->actingAs(Catalog::staff(['admin.access', 'storefront.manage']), 'staff');
    Catalog::seed(function () {
        $id = StorefrontExtrasServiceProvider::ID;
        app(PluginManager::class)->install($id);
        app(PluginManager::class)->enable($id);
        app()->register(StorefrontExtrasServiceProvider::class);
        app(Settings::class)->set('core', 'storefront.builder.home', [
            ['type' => 'extras_features', 'config' => ['item_1_title' => 'Giao nhanh']],
        ]);
    });

    $page = visit(route('admin.storefront.builder.edit', absolute: false));
    $page->click('[data-block-toggle="0"]')
        ->click('Thêm mục')
        ->type('[data-builder-repeater] input[type="text"] >> nth=2', 'Cam kết trong bản nháp')
        ->click('Nhân bản')
        ->assertPresent('[data-block-toggle="1"]')
        ->click('Xem trước responsive')
        ->assertVisible('[data-builder-preview-frame]')
        ->assertScript('document.querySelector("[data-builder-preview-frame]").style.width', '390px')
        ->assertScript('document.querySelector("[data-builder-preview-frame]").srcdoc.includes("Cam kết trong bản nháp")')
        ->click('Desktop')
        ->assertScript('document.querySelector("[data-builder-preview-frame]").style.width', '1280px')
        ->keys('[role="dialog"]', 'Escape');

    $disk = Storage::fake('local');
    $disk->put('layout.json', json_encode(['version' => 1, 'blocks' => [
        ['type' => 'rich_text', 'config' => ['body' => '<p>Bố cục tái sử dụng</p>']],
    ]], JSON_THROW_ON_ERROR));
    $page->attach('input[type="file"]', $disk->path('layout.json'))
        ->assertSee('Thay các block trong bản nháp hiện tại')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertVisible('[data-block="rich_text"]')
        ->assertMissing('[data-block="extras_features"]')
        ->assertNoJavaScriptErrors();

    expect(app(BuilderEngine::class)->blocks()[0]['type'])->toBe('extras_features');
});
