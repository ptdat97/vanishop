<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Admin\BuilderController;

Route::get('storefront', [BuilderController::class, 'home'])->name('storefront.home');
