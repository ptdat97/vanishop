<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\PageBlocks;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

function installCms(): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CmsServiceProvider::ID);
        app(PluginManager::class)->enable(CmsServiceProvider::ID);
    });
    app()->register(CmsServiceProvider::class);
}

function cmsPage(array $attributes = []): Page
{
    return Page::query()->create([
        'slug' => 'gioi-thieu', 'title' => 'Giới thiệu', 'body' => "## Về chúng tôi\n\nCửa hàng **thời trang**.", 'status' => 'published',
        'published_at' => now()->subHour(), ...$attributes,
    ]);
}

function cmsPost(array $attributes = []): Post
{
    return Post::query()->create([
        'slug' => 'bo-suu-tap-thu', 'title' => 'Bộ sưu tập thu', 'body' => 'Nội dung bài viết mùa thu.', 'status' => 'published',
        'published_at' => now()->subDay(), ...$attributes,
    ]);
}

beforeEach(function () {
    installCms();
    Cache::flush();
    $this->editor = StaffUser::factory()->withPermissions(['admin.access', 'cms.view', 'cms.manage'])->create();
    $this->admin = '/admin/plugins/vani-cms';
});

it('trang nội dung: Markdown → HTML, bỏ HTML thô và link javascript:, SEO; nháp/hẹn giờ → 404', function () {
    cmsPage(['body' => "## Về chúng tôi\n\n<script>alert(1)</script>\n\n[bấm](javascript:alert(1)) và [đúng](https://vanishop.dev)", 'meta_description' => 'Mô tả SEO']);
    cmsPage(['slug' => 'nhap', 'status' => 'draft', 'published_at' => null]);
    cmsPage(['slug' => 'sap-dang', 'published_at' => now()->addDay()]);

    $html = $this->get('/trang/gioi-thieu')->assertOk()
        ->assertSee('<h2>Về chúng tôi</h2>', false)
        ->assertSee('<meta name="description" content="Mô tả SEO">', false)
        ->assertSee('<link rel="canonical" href="'.url('/trang/gioi-thieu').'">', false)
        ->getContent();
    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('javascript:alert')->toContain('href="https://vanishop.dev"');

    $this->get('/trang/nhap')->assertNotFound();
    $this->get('/trang/sap-dang')->assertNotFound();
    $this->get('/trang/khong-co')->assertNotFound();
});

it('xem trước bản nháp bằng link ký (noindex), link sai chữ ký → 404', function () {
    cmsPage(['slug' => 'nhap', 'status' => 'draft', 'published_at' => null, 'title' => 'Bản nháp']);

    $this->get(URL::temporarySignedRoute('storefront.p.vani-cms.page', now()->addMinutes(30), ['slug' => 'nhap']))->assertOk()
        ->assertSee('Bản xem trước')->assertSee('<meta name="robots" content="noindex">', false);
    $this->get('/trang/nhap?signature=sai&expires='.now()->addHour()->timestamp)->assertNotFound();
});

it('tin tức: danh sách mới nhất trước, phân trang, bài chi tiết; link header/footer; sitemap', function () {
    cmsPage(['show_in_footer' => true]);
    cmsPage(['slug' => 'chinh-sach-doi-tra', 'title' => 'Chính sách đổi trả', 'show_in_header' => true, 'show_in_footer' => true, 'sort_order' => 1]);
    cmsPost(['slug' => 'cu', 'title' => 'Bài cũ', 'published_at' => now()->subDays(3)]);
    cmsPost(['title' => 'Bài mới']);
    cmsPost(['slug' => 'tuong-lai', 'title' => 'Bài tương lai', 'published_at' => now()->addDay()]);

    $this->get('/tin-tuc')->assertOk()->assertSeeInOrder(['Bài mới', 'Bài cũ'])->assertDontSee('Bài tương lai');
    $this->get('/tin-tuc/bo-suu-tap-thu')->assertOk()->assertSee('Nội dung bài viết mùa thu.')->assertSee('og:type', false);
    $this->get('/tin-tuc/tuong-lai')->assertNotFound();

    $home = $this->get('/')->assertOk();
    $home->assertSee('href="'.url('/trang/chinh-sach-doi-tra').'"', false)->assertSee('href="'.url('/tin-tuc').'"', false)
        ->assertSee('Thông tin')->assertSee('href="'.url('/trang/gioi-thieu').'"', false);

    $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($sitemap)->toContain(url('/trang/gioi-thieu'))->toContain(url('/tin-tuc/bo-suu-tap-thu'))->toContain(url('/tin-tuc').'</loc>')
        ->not->toContain('tuong-lai');
});

it('Storefront API (headless): trang, danh sách và chi tiết bài viết', function () {
    cmsPage();
    cmsPost();
    cmsPost(['slug' => 'nhap', 'status' => 'draft', 'published_at' => null]);

    $this->getJson('/api/storefront/v1/x/vani-cms/pages/gioi-thieu')->assertOk()->assertJsonPath('data.title', 'Giới thiệu')
        ->assertJsonPath('data.html', "<h2>Về chúng tôi</h2>\n<p>Cửa hàng <strong>thời trang</strong>.</p>\n");
    $this->getJson('/api/storefront/v1/x/vani-cms/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);
    $this->getJson('/api/storefront/v1/x/vani-cms/posts/nhap')->assertNotFound();
    $this->getJson('/api/storefront/v1/x/vani-cms/posts/bo-suu-tap-thu')->assertOk()->assertJsonPath('data.excerpt', 'Nội dung bài viết mùa thu.');
});

it('Admin: tạo trang (slug từ tiêu đề có dấu), slug trùng → lỗi, sửa, hẹn giờ theo giờ VN, xoá', function () {
    Carbon::setTestNow('2026-10-03T03:00:00Z');
    $this->actingAs($this->editor, 'staff');

    $this->get("{$this->admin}/pages")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Cms::Content/Index')->where('kind', 'pages'));
    $this->post("{$this->admin}/pages", ['title' => 'Chính sách bảo mật', 'body' => 'Nội dung', 'status' => 'published', 'show_in_footer' => '1'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $page = Page::query()->sole();
    expect($page)->slug->toBe('chinh-sach-bao-mat')->show_in_footer->toBeTrue()->and($page->published_at->equalTo(now()))->toBeTrue();

    $this->post("{$this->admin}/pages", ['title' => 'Khác', 'slug' => 'chinh-sach-bao-mat', 'body' => 'x', 'status' => 'draft'])->assertSessionHasErrors('slug');

    $this->put("{$this->admin}/pages/{$page->id}", ['title' => 'Bảo mật', 'slug' => 'bao-mat', 'body' => 'Mới', 'status' => 'published', 'published_at' => '2026-10-05T08:00'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($page->fresh())->slug->toBe('bao-mat')->and($page->fresh()->published_at->toIso8601String())->toBe('2026-10-05T01:00:00+00:00');

    $this->get("{$this->admin}/pages")->assertInertia(fn (Assert $p) => $p->where('items.data.0.status', 'scheduled'));
    $this->get("{$this->admin}/pages/{$page->id}")->assertInertia(fn (Assert $p) => $p->component('Cms::Content/Form')
        ->where('item.published_at', '2026-10-05T08:00')->where('item.preview_url', fn (string $url) => str_contains($url, 'signature=')));

    $this->delete("{$this->admin}/pages/{$page->id}")->assertRedirect("{$this->admin}/pages");
    expect(Page::query()->count())->toBe(0);
    Carbon::setTestNow();
});

it('Admin: bài viết có ảnh bìa tải lên; xem trước Markdown; quyền xem không sửa được; plugin tắt → 404', function () {
    Storage::fake('public');
    $this->actingAs($this->editor, 'staff');

    $upload = $this->post("{$this->admin}/uploads", ['image' => UploadedFile::fake()->image('bia.jpg', 800, 450)])->assertOk();
    Storage::disk('public')->assertExists($upload->json('path'));
    $this->post("{$this->admin}/uploads", ['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');

    $this->post("{$this->admin}/posts", ['title' => 'Mặc gì đi biển', 'body' => 'Gợi ý...', 'status' => 'published', 'cover_path' => $upload->json('path')])->assertRedirect()->assertSessionHasNoErrors();
    $this->post("{$this->admin}/posts", ['title' => 'Ảnh lạ', 'body' => 'x', 'status' => 'draft', 'cover_path' => '../../.env'])->assertSessionHasErrors('cover_path');
    expect(Post::query()->sole()->cover_path)->toBe($upload->json('path'));
    $this->get('/tin-tuc/mac-gi-di-bien')->assertOk()->assertSee(Storage::disk('public')->url($upload->json('path')));

    $this->postJson("{$this->admin}/preview", ['body' => '**đậm** <b>thô</b>'])->assertOk()->assertJsonPath('html', "<p><strong>đậm</strong> thô</p>\n");

    $viewer = StaffUser::factory()->withPermissions(['admin.access', 'cms.view'])->create();
    $this->actingAs($viewer, 'staff')->get("{$this->admin}/posts")->assertOk();
    $this->actingAs($viewer, 'staff')->post("{$this->admin}/posts", ['title' => 'x', 'body' => 'x', 'status' => 'draft'])->assertForbidden();

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->disable(CmsServiceProvider::ID));
    $this->get('/tin-tuc/mac-gi-di-bien')->assertNotFound();
    $this->actingAs($this->editor, 'staff')->get("{$this->admin}/posts")->assertNotFound();
});

it('khối "Bài viết mới" cho page builder trang chủ', function () {
    cmsPost();
    $blocks = app(PageBlocks::class);
    expect(array_keys($blocks->types()))->toContain('cms_latest_posts');

    $blocks->saveHome([['type' => 'cms_latest_posts', 'config' => ['title' => 'Góc thời trang', 'limit' => 3]]]);
    $this->get('/')->assertOk()->assertSee('Góc thời trang')->assertSee('Bộ sưu tập thu');
});
