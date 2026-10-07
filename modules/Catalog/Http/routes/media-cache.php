<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Http\Controllers\ImageCacheController;

// Không qua nhóm 'web' (không session/cookie): chỉ là ảnh tĩnh tạo lần đầu. Khớp đúng định dạng ImageCache::relativePath.
Route::get('cache/media/{shard}/{checksum}-w{width}.{extension}', ImageCacheController::class)
    ->where(['shard' => '[0-9a-f]{2}', 'checksum' => '[0-9a-f]{64}', 'width' => '[0-9]{1,4}', 'extension' => implode('|', ImageCache::EXTENSIONS)])
    ->name('media.cache');
