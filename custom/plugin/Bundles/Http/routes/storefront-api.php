<?php

use Illuminate\Support\Facades\Route;
use Plugin\Bundles\Http\Controllers\BundlePageController;

Route::get('bundles', [BundlePageController::class, 'apiIndex'])->name('index');
