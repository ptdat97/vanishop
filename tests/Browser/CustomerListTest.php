<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;

require_once __DIR__.'/../../modules/Customer/Tests/Feature/CustomerTestHelpers.php';

it('sắp xếp cột khách hàng qua máy chủ, giữ bộ lọc và cập nhật aria-sort', function () {
    H::account('0912345678')->update(['full_name' => 'Minh Nhóm']);
    H::account('0912345679')->update(['full_name' => 'An Nhóm']);
    H::account('0912345680')->update(['full_name' => 'Khách khác']);
    $this->actingAs(T::staff(['admin.access', 'customers.view']), 'staff');

    $sort = 'th button[title="Sắp xếp theo Khách"]';
    $firstName = "document.querySelector('tbody tr td span.font-medium')?.textContent.trim()";
    visit('/admin/customers')
        ->fill('input[aria-label="Tìm khách hàng"]', 'Nhóm')
        ->press('Áp dụng')
        ->assertDontSee('Khách khác')
        ->click($sort)
        ->assertPresent('th[aria-sort="ascending"]')
        ->assertScript($firstName, 'A. Nhóm')
        ->click($sort)
        ->assertPresent('th[aria-sort="descending"]')
        ->assertScript($firstName, 'M. Nhóm')
        ->assertValue('input[aria-label="Tìm khách hàng"]', 'Nhóm')
        ->assertDontSee('Khách khác')
        ->assertNoJavaScriptErrors();
});
