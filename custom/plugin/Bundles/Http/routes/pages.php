<?php

use Illuminate\Support\Facades\Route;
use Plugin\Bundles\Http\Controllers\BundlePageController;

Route::get('/', BundlePageController::class)->name('index');
