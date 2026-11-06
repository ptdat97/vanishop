<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Admin\ImageSizeController;
use Modules\Catalog\Http\Controllers\Admin\MediaLibraryController;

// Prefix: /{admin}/media — tên route: admin.media.* (Thư viện ảnh dùng chung). Thao tác ghi đều là POST JSON.
Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
Route::get('browse', [MediaLibraryController::class, 'browse'])->name('browse');
Route::get('items', [MediaLibraryController::class, 'items'])->name('items');
Route::post('folders', [MediaLibraryController::class, 'createFolder'])->name('folders.store');
Route::post('folders/rename', [MediaLibraryController::class, 'renameFolder'])->name('folders.rename');
Route::post('folders/delete', [MediaLibraryController::class, 'deleteFolder'])->name('folders.destroy');
Route::post('upload', [MediaLibraryController::class, 'upload'])->name('upload');
Route::post('items/move', [MediaLibraryController::class, 'move'])->name('items.move');
Route::post('items/delete', [MediaLibraryController::class, 'destroy'])->name('items.destroy');
Route::post('items/{media}/rename', [MediaLibraryController::class, 'rename'])->name('items.rename');
// Kích thước ảnh thu nhỏ (0.3.87).
Route::get('sizes', [ImageSizeController::class, 'index'])->name('sizes.index');
Route::put('sizes', [ImageSizeController::class, 'update'])->name('sizes.update');
Route::post('sizes/flush', [ImageSizeController::class, 'flush'])->name('sizes.flush');
