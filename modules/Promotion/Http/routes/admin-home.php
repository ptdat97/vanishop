<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Http\Controllers\Admin\PromotionController;

Route::get('promotion', [PromotionController::class, 'home'])->name('promotion.home');
