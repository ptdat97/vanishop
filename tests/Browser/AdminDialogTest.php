<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Persistence\CategoryBanner;

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

it('ô tải ảnh chặn tệp sai loại và chấp nhận ảnh khi chọn lại', function () {
    $fixtures = Storage::fake('local');
    Storage::fake('public');
    $fixtures->put('sai.txt', 'not an image');
    $fixtures->put('banner.png', UploadedFile::fake()->image('banner.png')->getContent());

    visit($this->edit)
        ->attach('[data-file-input] input', $fixtures->path('sai.txt'))
        ->assertSee('sai.txt: loại tệp không được hỗ trợ.')
        ->assertScript("Array.from(document.querySelectorAll('button')).find(button => button.textContent.trim() === 'Tải lên').disabled", true)
        ->attach('[data-file-input] input', $fixtures->path('banner.png'))
        ->assertDontSee('sai.txt: loại tệp không được hỗ trợ.')
        ->click('Tải lên')
        ->assertSee('Gỡ ảnh')
        ->assertNoJavaScriptErrors();
});

it('chọn ảnh banner trong form danh mục và lưu cùng nội dung banner', function () {
    Storage::fake('public');
    $media = T::seed(function () {
        app(PluginManager::class)->install(CmsServiceProvider::ID);
        app(PluginManager::class)->enable(CmsServiceProvider::ID);
        Category::query()->where('slug', 'dam')->sole()->syncTranslations(['vi' => ['name' => 'Đầm']]);

        return app(MediaLibrary::class)->store(UploadedFile::fake()->image('banner-danh-muc.jpg', 800, 400));
    });
    app()->register(CmsServiceProvider::class);
    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'cms.manage', 'media.view']), 'staff');
    $section = '[data-extension="vani.cms:banner"]';

    visit($this->edit)
        ->click($section.' button:has-text("Chọn ảnh desktop")')
        ->click('[role="dialog"] button[title="banner-danh-muc.jpg"]')
        ->click('[role="dialog"] button:has-text("Chọn 1 ảnh")')
        ->assertVisible($section.' img[alt="banner-danh-muc.jpg"]')
        ->fill('#ext-vani-cms-banner-title-vi', 'Đầm cuối tuần')
        ->click('#ext-vani-cms-banner-active')
        ->click('form button[type="submit"]')
        ->assertSee(__('catalog::messages.saved'))
        ->assertNoJavaScriptErrors();

    expect(CategoryBanner::query()->sole())->desktop_media_id->toBe($media->id)->active->toBeTrue();
});

it('đặt điểm lấy nét bằng bàn phím trong Thư viện ảnh và mở lại giá trị đã lưu', function () {
    Storage::fake('public');
    $media = T::seed(fn () => app(MediaLibrary::class)->store(UploadedFile::fake()->image('anh-lay-net.png', 800, 500)));

    visit('/admin/media')
        ->click('button[title="anh-lay-net.png"]')
        ->click('[data-edit-focal-point]')
        ->keys('[data-focal-x]', 'End')
        ->keys('[data-focal-y]', 'Home')
        ->click('[data-save-focal-point]')
        ->assertSee('Đã lưu điểm lấy nét.')
        ->click('[data-edit-focal-point]')
        ->assertValue('[data-focal-x]', '100')
        ->assertValue('[data-focal-y]', '0')
        ->assertNoJavaScriptErrors();

    expect($media->refresh()->focalPoint())->toBe(['x' => 100, 'y' => 0]);
});
