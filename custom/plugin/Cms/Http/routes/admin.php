<?php

use Illuminate\Support\Facades\Route;
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
