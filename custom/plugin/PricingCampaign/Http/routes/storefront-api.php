<?php

use Illuminate\Support\Facades\Route;
use Plugin\PricingCampaign\Http\Controllers\CampaignPageController;

// /api/storefront/v1/x/vani-pricing-campaign/… (openapi.json). Số suất còn lại đọc trực tiếp (không cache).
Route::get('campaigns', [CampaignPageController::class, 'apiIndex'])->name('index');
Route::get('products/{product}', [CampaignPageController::class, 'product'])->whereNumber('product')->name('product');
