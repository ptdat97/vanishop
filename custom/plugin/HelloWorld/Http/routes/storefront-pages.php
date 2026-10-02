<?php

use Illuminate\Support\Facades\Route;

// GET /p/vani-hello-world — trang của plugin trong layout theme đang hoạt động.
Route::get('/', fn () => view('vani-hello-world::pages.hello'))->name('index');
