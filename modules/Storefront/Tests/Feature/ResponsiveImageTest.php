<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\View\ResponsiveImage;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('sizes theo bố cục: cột cố định px khi màn hình rộng hơn vùng chứa, theo vw khi hẹp hơn, tràn màn hình', function () {
    expect(ResponsiveImage::sizes(1152, 4, 2))->toBe('(min-width: 1152px) 288px, (min-width: 768px) 25vw, 50vw')
        ->and(ResponsiveImage::sizes(896, 3, 2))->toBe('(min-width: 896px) 299px, (min-width: 768px) 34vw, 50vw')
        ->and(ResponsiveImage::sizes(null, 1, 1))->toBe('(min-width: 768px) 100vw, 100vw')
        ->and(ResponsiveImage::sizes(1152, 0, 0))->toBe('(min-width: 1152px) 1152px, (min-width: 768px) 100vw, 100vw');
});

it('thẻ sản phẩm có srcset từ bản thu nhỏ (theo kích thước đã lưu) + sizes theo lưới; ảnh nhỏ không có bản thu nhỏ thì chỉ src', function () {
    Storage::fake('public');
    ['brand' => $brand, 's' => $variant] = C::store();
    $product = $variant->style;
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StyleColorService::class)->addImages($product, $variant->styleColor, [
        UploadedFile::fake()->image('lon.jpg', 1000, 1333),
    ]));
    $media = $variant->styleColor->gallery()->with('media')->first()->media;

    $html = $this->get('/thuong-hieu/'.$brand->slug)->assertOk()->getContent();
    expect($media->srcset())->toBe($media->url(160).' 160w, '.$media->url(400).' 400w, '.$media->url(800).' 800w, '.$media->url().' 1000w')
        ->and($html)->toContain('srcset="'.e($media->srcset()).'"')
        ->and($html)->toContain('sizes="(min-width: 896px) 299px, (min-width: 768px) 34vw, 50vw"');

    $this->getJson('/api/storefront/v1/products')->assertOk()->assertJsonPath('data.0.image_srcset', $media->srcset());
});

it('ảnh nhỏ hơn mọi bề rộng cấu hình: không có srcset', function () {
    Storage::fake('public');
    ['brand' => $brand, 's' => $variant] = C::store();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StyleColorService::class)->addImages($variant->style, $variant->styleColor, [
        UploadedFile::fake()->image('nho.jpg', 120, 160),
    ]));

    expect($this->get('/thuong-hieu/'.$brand->slug)->assertOk()->getContent())->not->toContain('srcset=');
});
