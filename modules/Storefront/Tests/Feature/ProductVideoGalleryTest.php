<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Media\MediaManager;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Video Thư viện trong gallery sản phẩm (reference-parity L24): API trả `kind` + poster, thẻ sản phẩm dùng ảnh (không dùng file
| video), PDP hiện <video> có poster.
*/

it('gallery có video: API trả kind/poster, thẻ sản phẩm lấy ảnh, PDP hiện video có poster', function () {
    Storage::fake('public');
    ['s' => $variant] = C::store();
    $product = $variant->product;
    $box = fn (string $type, string $body): string => pack('N', 8 + strlen($body)).$type.$body;
    $tkhd = "\0\0\0\x03".str_repeat("\0", 52).pack('N', 720 * 65536).pack('N', 1280 * 65536);
    $mp4 = UploadedFile::fake()->createWithContent('demo.mp4', $box('ftyp', 'isom'."\0\0\x02\0".'isomiso2mp41').$box('moov', $box('trak', $box('tkhd', $tkhd))).str_repeat("\0", 2048));

    [$video, $image] = app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($mp4, $product, $variant): array {
        $video = app(MediaLibrary::class)->store($mp4, '');
        app(MediaManager::class)->setPoster($video, UploadedFile::fake()->image('demo-poster.jpg', 720, 1280));
        $image = app(MediaLibrary::class)->store(UploadedFile::fake()->image('anh.jpg', 600, 900), '');
        app(VariantImages::class)->attachMedia($product, $variant, [$video->id, $image->id]); // video đứng trước ảnh

        return [$video->refresh(), $image];
    });

    $gallery = collect($this->getJson("/api/storefront/v1/products/{$product->slug}")->assertOk()->json('data.media.gallery'))->keyBy('id');
    expect($gallery[$video->id])->toMatchArray(['kind' => 'video', 'url' => $video->url()])
        ->and($gallery[$video->id]['poster_url'])->not->toBeNull()
        ->and($gallery[$image->id]['kind'])->toBe('image');

    $card = $this->getJson("/api/storefront/v1/products?ids={$product->id}")->assertOk()->json('data.0');
    expect($card['image_url'])->not->toBe($video->url())->and($card['image_url'])->not->toContain('.mp4');

    $this->get("/san-pham/{$product->slug}")->assertOk()
        ->assertSee('data-gallery-video', false)
        ->assertSee('poster="'.$gallery[$video->id]['poster_url'].'"', false);
});
