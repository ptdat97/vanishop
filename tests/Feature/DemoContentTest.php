<?php

use Database\Seeders\Demo\ContentSeeder;
use Database\Seeders\Demo\StorefrontSeeder;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Storefront\Application\Menus\MenuRepository;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Persistence\Page;

require_once __DIR__.'/../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';

it('demo seed hoạt động khi thiếu CMS, và giữ menu theo id trang khi CMS được bật', function () {
    T::seed(fn () => $this->seed(ContentSeeder::class));
    expect(array_column(app(StorefrontSeeder::class)->mobileMenu(), 'link'))->not->toContain(['type' => 'url', 'value' => '/tin-tuc']);

    T::seed(function () {
        app(PluginManager::class)->install(CmsServiceProvider::ID);
        app(PluginManager::class)->enable(CmsServiceProvider::ID);
        app()->register(CmsServiceProvider::class);
        $this->seed(ContentSeeder::class);
        $this->seed(ContentSeeder::class);
        $this->seed(StorefrontSeeder::class);
    });

    $returns = Page::query()->where('slug', 'chinh-sach-doi-tra')->sole();
    $footer = app(MenuRepository::class)->published('footer_1');
    expect($footer[1]['link'])->toMatchArray(['type' => 'page', 'value' => (string) $returns->id]);
    expect(array_column(app(StorefrontSeeder::class)->mobileMenu(), 'link'))->toContain(['type' => 'url', 'value' => '/tin-tuc']);
});
