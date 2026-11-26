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
    $page = visit($this->edit)->click('Chọn Ảnh')->assertSee('Chọn Ảnh từ thư viện');
    $insideDialog = 'document.querySelector(\'[role="dialog"]\').contains(document.activeElement)';

    expect($page->script('document.body.style.overflow'))->toBe('hidden');

    $page->keys('[role="dialog"]', array_fill(0, 30, 'Tab'));
    expect($page->script($insideDialog))->toBeTrue();
    $page->keys('[role="dialog"]', array_fill(0, 30, 'Shift+Tab'));
    $page->keys('[role="dialog"]', array_fill(0, 30, 'Shift+Tab'));
    expect($page->script($insideDialog))->toBeTrue();

    $page->keys('[role="dialog"]', ['Escape'])->assertDontSee('Chọn Ảnh từ thư viện');
    expect($page->script('document.activeElement?.textContent?.trim()'))->toBe('Chọn Ảnh')
        ->and($page->script('document.body.style.overflow'))->toBe('');
    $page->assertNoJavaScriptErrors();
});

it('thư viện ảnh: chặn tệp sai loại ngay trên trình duyệt, nhận ảnh đúng vào hàng chờ tải; chọn ảnh có sẵn gắn vào ô ảnh', function () {
    $fixtures = Storage::fake('local');
    Storage::fake('public');
    $fixtures->put('sai.txt', 'not an image');
    $fixtures->put('banner.png', UploadedFile::fake()->image('banner.png')->getContent());
    T::seed(fn () => app(MediaLibrary::class)->store(UploadedFile::fake()->image('anh-co-san.jpg', 400, 400)));

    // Phần gửi lên server (trường `images[]`) không kiểm ở đây: server thử của Pest không đọc tên trường dạng mảng — đã có
    // feature test MediaLibraryTest.
    visit($this->edit)
        ->click('Chọn Ảnh')
        ->attach('[role="dialog"] input[type="file"]', $fixtures->path('sai.txt'))
        ->assertSee('sai.txt: loại tệp không được hỗ trợ.')
        ->attach('[role="dialog"] input[type="file"]', $fixtures->path('banner.png'))
        ->assertSeeIn('[data-upload-queue]', 'banner.png')
        ->click('[role="dialog"] [title="anh-co-san.jpg"]')
        ->click('[role="dialog"] button:has-text("Chọn 1 ảnh")')
        ->assertDontSee('Chọn Ảnh từ thư viện')
        ->assertSee('Đổi Ảnh');
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
