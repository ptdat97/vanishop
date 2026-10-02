<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Admin\HomeBlocksController;

Route::get('storefront', [HomeBlocksController::class, 'home'])->name('storefront.home');
