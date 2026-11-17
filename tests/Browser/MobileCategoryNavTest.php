<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';

/*
| Menu di động (Split Category Navigation): mở drawer → tải cây danh mục từ Storefront API, nhóm theo cấu trúc cây, bấm
| danh mục cấp 1 đổi nội dung bên phải; màn 375px không tràn ngang, không lỗi JS.
*/

beforeEach(function () {
    T::seed(function () {
        $beauty = Category::factory()->create(['slug' => 'my-pham', 'position' => 1]);
        $beauty->syncTranslations(['vi' => ['name' => 'Mỹ phẩm']]);
        $skincare = Category::factory()->childOf($beauty)->create(['slug' => 'cham-soc-da', 'position' => 1]);
        $skincare->syncTranslations(['vi' => ['name' => 'Chăm sóc da']]);
        Category::factory()->childOf($skincare)->create(['slug' => 'serum'])->syncTranslations(['vi' => ['name' => 'Serum']]);
        Category::factory()->childOf($beauty)->create(['slug' => 'nuoc-hoa', 'position' => 2])->syncTranslations(['vi' => ['name' => 'Nước hoa']]);

        $home = Category::factory()->create(['slug' => 'gia-dung', 'position' => 2]);
        $home->syncTranslations(['vi' => ['name' => 'Gia dụng']]);
        Category::factory()->childOf($home)->create(['slug' => 'noi-chao'])->syncTranslations(['vi' => ['name' => 'Nồi chảo']]);

        Category::factory()->create(['slug' => 'qua-tang', 'position' => 3])->syncTranslations(['vi' => ['name' => 'Quà tặng']]);
    });
});

it('mở menu di động, nhóm danh mục theo cây và đổi danh mục cấp 1 không tải lại trang', function () {
    $page = visit('/')->resize(375, 812);
    $page->click('[data-vani-mobile-menu-open]')
        ->assertVisible('#vani-cnav-tab-'.mobileNavCategoryId('my-pham'))
        ->assertAttribute('#vani-cnav-tab-'.mobileNavCategoryId('my-pham'), 'aria-selected', 'true')
        // Nhóm đầu: cấp 2 không con (Nước hoa) dưới tên cấp 1; nhóm sau: Chăm sóc da → Serum.
        ->assertSeeIn('#vani-cnav-tab-'.mobileNavCategoryId('my-pham').'-panel', 'Nước hoa')
        ->assertSeeIn('#vani-cnav-tab-'.mobileNavCategoryId('my-pham').'-panel', 'Chăm sóc da')
        ->assertSeeIn('#vani-cnav-tab-'.mobileNavCategoryId('my-pham').'-panel', 'Serum');

    $page->click('#vani-cnav-tab-'.mobileNavCategoryId('qua-tang'))
        ->assertAttribute('#vani-cnav-tab-'.mobileNavCategoryId('qua-tang'), 'aria-selected', 'true')
        ->assertSeeIn('#vani-cnav-tab-'.mobileNavCategoryId('qua-tang').'-panel', 'Danh mục này chưa có danh mục con.')
        ->assertMissing('#vani-cnav-tab-'.mobileNavCategoryId('my-pham').'-panel')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertVisible('[data-vani-bottom-nav]')
        ->assertNoJavaScriptErrors();
});

function mobileNavCategoryId(string $slug): int
{
    return (int) Category::query()->where('slug', $slug)->value('id');
}
