<?php

use Illuminate\Support\Facades\Route;
use Plugin\Cms\Http\Controllers\Admin\CategoryController;
use Plugin\Cms\Http\Controllers\Admin\ContentController;

Route::redirect('/', 'pages')->name('index');
foreach (['pages', 'posts'] as $kind) {
    Route::get($kind, [ContentController::class, 'index'])->defaults('kind', $kind)->name("{$kind}.index");
    Route::get("{$kind}/create", [ContentController::class, 'create'])->defaults('kind', $kind)->name("{$kind}.create");
    Route::post($kind, [ContentController::class, 'store'])->defaults('kind', $kind)->name("{$kind}.store");
    Route::get("{$kind}/{id}", [ContentController::class, 'edit'])->defaults('kind', $kind)->whereNumber('id')->name("{$kind}.edit");
    Route::put("{$kind}/{id}", [ContentController::class, 'update'])->defaults('kind', $kind)->whereNumber('id')->name("{$kind}.update");
    Route::delete("{$kind}/{id}", [ContentController::class, 'destroy'])->defaults('kind', $kind)->whereNumber('id')->name("{$kind}.destroy");
}
Route::post('preview', [ContentController::class, 'preview'])->name('preview');
Route::get('products', [ContentController::class, 'products'])->name('products');
// Chuyên mục tin tức (1.4.0).
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category')->name('categories.destroy');
