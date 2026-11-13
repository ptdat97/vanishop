<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

/*
| Dialog dùng chung trên trình duyệt thật — Thư viện ảnh (MediaPicker) mở từ form danh mục.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'media.view', 'media.manage']), 'staff');
    $category = T::seed(fn () => Category::factory()->create(['slug' => 'dam']));
    $this->edit = "/admin/catalog/categories/{$category->id}/edit";
});

it('popup: giữ Tab/Shift+Tab trong popup, khoá cuộn nền, Esc đóng và trả focus về nút đã mở', function () {
    $page = visit($this->edit)->click('Chọn từ thư viện')->assertSee('Chọn ảnh danh mục');
    $insideDialog = 'document.querySelector(\'[role="dialog"]\').contains(document.activeElement)';

    expect($page->script('document.body.style.overflow'))->toBe('hidden');

    $page->keys('[role="dialog"]', array_fill(0, 30, 'Tab'));
    expect($page->script($insideDialog))->toBeTrue();
    $page->keys('[role="dialog"]', array_fill(0, 30, 'Shift+Tab'));
    expect($page->script($insideDialog))->toBeTrue();

    $page->keys('[role="dialog"]', ['Escape'])->assertDontSee('Chọn ảnh danh mục');
    expect($page->script('document.activeElement?.textContent?.trim()'))->toBe('Chọn từ thư viện')
        ->and($page->script('document.body.style.overflow'))->toBe('');
    $page->assertNoJavaScriptErrors();
});
