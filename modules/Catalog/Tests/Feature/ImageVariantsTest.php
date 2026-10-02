<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\ImageVariants;
use Modules\Catalog\Application\Media\MediaLibrary;

it('tải ảnh lên → tạo bản WebP thu nhỏ (không phóng to), url(width) trả bản phù hợp; ảnh nhỏ dùng gốc', function () {
    Storage::fake('public');
    $library = app(MediaLibrary::class);

    $large = $library->store(UploadedFile::fake()->image('dam.jpg', 1200, 1500));
    Storage::disk('public')->assertExists(ImageVariants::path($large->checksum, 400));
    Storage::disk('public')->assertExists(ImageVariants::path($large->checksum, 800));
    Storage::disk('public')->assertMissing(ImageVariants::path($large->checksum, 1600));

    expect($large->url(400))->toEndWith("{$large->checksum}-w400.webp")
        ->and($large->url(500))->toEndWith("{$large->checksum}-w800.webp")
        ->and($large->url(1600))->toBe($large->url())
        ->and(getimagesizefromstring(Storage::disk('public')->get(ImageVariants::path($large->checksum, 400)))[0])->toBe(400);

    $small = $library->store(UploadedFile::fake()->image('logo.png', 300, 100));
    expect($small->url(400))->toBe($small->url());
});
