<?php

use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    Cache::flush();
    C::store();
    $this->style = Style::query()->sole();
    $this->vi = url("/san-pham/{$this->style->slug}");
    $this->en = url('/en/products/silk-dress');
});

function translateStyle(Style $style): void
{
    T::seed(fn () => $style->syncTranslations(['vi' => ['name' => 'Đầm lụa'], 'en' => ['name' => 'Silk dress', 'slug' => 'silk-dress']]));
}

it('sản phẩm đã dịch: canonical tự trỏ, hreflang vi/en/x-default, nút chuyển ngôn ngữ trỏ URL tương ứng', function () {
    translateStyle($this->style);

    $vi = $this->get($this->vi)->assertOk()->getContent();
    expect($vi)->toContain('<link rel="canonical" href="'.$this->vi.'">')
        ->toContain('hreflang="vi" href="'.$this->vi.'"')->toContain('hreflang="en" href="'.$this->en.'"')->toContain('hreflang="x-default" href="'.$this->vi.'"')
        ->not->toContain('name="robots"');

    $en = $this->get($this->en)->assertOk()->getContent();
    expect($en)->toContain('<link rel="canonical" href="'.$this->en.'">')
        ->toContain('<a href="'.$this->vi.'" hreflang="vi"')
        ->not->toContain('name="robots"');
});

it('bản tiếng Anh chưa dịch: vẫn 200, noindex,follow, canonical về bản tiếng Việt, không hreflang', function () {
    $html = $this->get("/en/products/{$this->style->slug}")->assertOk()->getContent();

    expect($html)->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="'.$this->vi.'">')
        ->not->toContain('hreflang="en" href');
    expect($this->get($this->vi)->getContent())->not->toContain('hreflang="en" href')->not->toContain('name="robots"');
});

it('trang giao dịch noindex ở mọi ngôn ngữ; trang chủ có hreflang', function () {
    $this->get('/gio-hang')->assertSee('<meta name="robots" content="noindex">', false);
    $this->get('/en/cart')->assertSee('<meta name="robots" content="noindex">', false);
    $this->get('/tra-cuu-don')->assertSee('<meta name="robots" content="noindex">', false);

    $this->get('/en')->assertSee('hreflang="vi" href="'.url('/').'"', false)->assertSee('<link rel="canonical" href="'.url('/en').'">', false);
});

it('sitemap: bản đã dịch có URL riêng + xhtml:link; bản chưa dịch không có', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($xml)->toContain('xmlns:xhtml')->toContain('<loc>'.$this->vi.'</loc>')->not->toContain('/en/products/')
        ->toContain('<loc>'.url('/en').'</loc>');

    Cache::flush();
    translateStyle($this->style);
    $xml = $this->get('/sitemap.xml')->getContent();
    expect($xml)->toContain('<loc>'.$this->en.'</loc>')
        ->toContain('<xhtml:link rel="alternate" hreflang="en" href="'.$this->en.'"/>')
        ->toContain('<xhtml:link rel="alternate" hreflang="x-default" href="'.$this->vi.'"/>');
});

it('cửa hàng một ngôn ngữ (cấu hình mặc định): không hreflang, không nút chuyển ngôn ngữ, sitemap không có bản khác', function () {
    config(['vanishop.locale.supported' => [config('vanishop.locale.default')]]);
    translateStyle($this->style);

    expect($this->get($this->vi)->assertOk()->getContent())->not->toContain('hreflang=')
        ->and($this->get('/')->assertOk()->getContent())->not->toContain('hreflang=');
    expect($this->get('/sitemap.xml')->assertOk()->getContent())->toContain('<loc>'.$this->vi.'</loc>')->not->toContain('/en');
});
