<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Admin\CatalogHomeController;

Route::get('catalog', CatalogHomeController::class)->name('catalog.home');
